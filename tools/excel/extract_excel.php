<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$appRoot = dirname(__DIR__, 2);
$referenceDir = $appRoot.'/docs/reference-excel';
$modelSpecDir = $appRoot.'/docs/model-spec';
$fixturesDir = $appRoot.'/tests/fixtures';

loadComposerAutoload($appRoot);

if(!class_exists(IOFactory::class)) {
    fwrite(STDERR, "PhpSpreadsheet ist nicht installiert.\n\n");
    fwrite(STDERR, "Installation lokal in /virtualweb/manhart/pvinvestment:\n");
    fwrite(STDERR, "  cd /virtualweb/manhart/pvinvestment\n");
    fwrite(STDERR, "  composer install\n\n");
    fwrite(STDERR, "PhpSpreadsheet ist als require-dev Dependency in composer.json gepflegt.\n");
    fwrite(STDERR, "Dieses Tool erfindet keinen XLSX-Parser und erzeugt ohne PhpSpreadsheet keine Excel-Auswertung.\n");
    exit(2);
}

ensureDirectory($modelSpecDir);
ensureDirectory($fixturesDir);

$excelFiles = findExcelFiles($referenceDir);
if(!$excelFiles) {
    fwrite(STDERR, "Keine Excel-Dateien in $referenceDir gefunden.\n");
    exit(1);
}

$inventory = [
    'generatedAt' => gmdate('c'),
    'sourceDirectory' => relativePath($appRoot, $referenceDir),
    'files' => [],
];
$inputs = [];
$results = [];
$deviations = [];
$fixtureValues = [
    'metadata' => [
        'generatedAt' => $inventory['generatedAt'],
        'sourceDirectory' => relativePath($appRoot, $referenceDir),
        'note' => 'Excel-Werte sind Beispiel- und Referenzwerte, keine verbindliche Businesslogik.',
    ],
    'files' => [],
];

foreach($excelFiles as $file) {
    $fileInventory = analyzeWorkbook($file, $appRoot);
    $inventory['files'][] = $fileInventory['inventory'];
    $inputs[] = $fileInventory['inputs'];
    $results[] = $fileInventory['results'];
    $deviations[] = $fileInventory['deviations'];
    $fixtureValues['files'][] = $fileInventory['fixture'];
}

writeFile($modelSpecDir.'/excel-inventory.md', renderInventory($inventory));
writeFile($modelSpecDir.'/excel-inputs.md', renderInputs($inputs));
writeFile($modelSpecDir.'/excel-results.md', renderResults($results));
writeFile($modelSpecDir.'/excel-abweichungen.md', renderDeviations($deviations));
writeFile($fixturesDir.'/excel_reference_values.json', json_encode($fixtureValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

echo "Excel-Auswertung erzeugt:\n";
echo "- docs/model-spec/excel-inventory.md\n";
echo "- docs/model-spec/excel-inputs.md\n";
echo "- docs/model-spec/excel-results.md\n";
echo "- docs/model-spec/excel-abweichungen.md\n";
echo "- tests/fixtures/excel_reference_values.json\n";

function loadComposerAutoload(string $appRoot): void
{
    $autoload = $appRoot.'/vendor/autoload.php';
    if(file_exists($autoload)) {
        require_once $autoload;
    }
}

function ensureDirectory(string $directory): void
{
    if(!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("Verzeichnis konnte nicht erstellt werden: $directory");
    }
}

/**
 * @return list<string>
 */
function findExcelFiles(string $referenceDir): array
{
    $files = glob($referenceDir.'/*.{xls,xlsx,xlsm}', GLOB_BRACE) ?: [];
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($files);
}

/**
 * @return array{inventory: array<string, mixed>, inputs: array<string, mixed>, results: array<string, mixed>, deviations: array<string, mixed>, fixture: array<string, mixed>}
 */
function analyzeWorkbook(string $file, string $appRoot): array
{
    $reader = IOFactory::createReaderForFile($file);
    $reader->setReadDataOnly(false);
    if(method_exists($reader, 'setReadEmptyCells')) {
        $reader->setReadEmptyCells(false);
    }

    $spreadsheet = $reader->load($file);
    $relativeFile = relativePath($appRoot, $file);

    $workbookInventory = [
        'file' => $relativeFile,
        'sheetNames' => [],
        'hiddenSheets' => [],
        'namedRanges' => extractNamedRanges($spreadsheet),
        'externalLinks' => [],
        'formulaCount' => 0,
        'sheets' => [],
    ];
    $workbookInputs = [
        'file' => $relativeFile,
        'items' => [],
    ];
    $workbookResults = [
        'file' => $relativeFile,
        'annualRows' => [],
    ];
    $workbookFixture = [
        'file' => $relativeFile,
        'sheets' => [],
    ];

    foreach($spreadsheet->getWorksheetIterator() as $sheet) {
        $sheetTitle = $sheet->getTitle();
        $workbookInventory['sheetNames'][] = $sheetTitle;
        if($sheet->getSheetState() !== Worksheet::SHEETSTATE_VISIBLE) {
            $workbookInventory['hiddenSheets'][] = [
                'sheet' => $sheetTitle,
                'state' => $sheet->getSheetState(),
            ];
        }

        $sheetData = analyzeSheet($sheet);
        $workbookInventory['formulaCount'] += count($sheetData['formulas']);
        $workbookInventory['externalLinks'] = array_values(array_unique(array_merge($workbookInventory['externalLinks'], $sheetData['externalLinks'])));
        $workbookInventory['sheets'][] = [
            'name' => $sheetTitle,
            'state' => $sheet->getSheetState(),
            'highestRow' => $sheetData['highestRow'],
            'highestColumn' => $sheetData['highestColumn'],
            'formulaCount' => count($sheetData['formulas']),
            'formulas' => array_slice($sheetData['formulas'], 0, 80),
            'sampleValues' => array_slice($sheetData['sampleValues'], 0, 80),
        ];

        foreach($sheetData['inputRows'] as $row) {
            $workbookInputs['items'][] = [
                'sheet' => $sheetTitle,
                ...$row,
            ];
        }
        foreach($sheetData['annualRows'] as $row) {
            $workbookResults['annualRows'][] = [
                'sheet' => $sheetTitle,
                ...$row,
            ];
        }

        $workbookFixture['sheets'][] = [
            'name' => $sheetTitle,
            'state' => $sheet->getSheetState(),
            'inputs' => $sheetData['inputRows'],
            'annualResults' => $sheetData['annualRows'],
            'formulas' => array_slice($sheetData['formulas'], 0, 250),
        ];
    }

    $workbookInventory['externalLinks'] = array_values(array_unique(array_merge(
        $workbookInventory['externalLinks'],
        extractWorkbookExternalLinks($spreadsheet)
    )));

    $spreadsheet->disconnectWorksheets();

    return [
        'inventory' => $workbookInventory,
        'inputs' => $workbookInputs,
        'results' => $workbookResults,
        'deviations' => [
            'file' => $relativeFile,
            'suspectedIssues' => detectSuspectedIssues($workbookInputs['items'], $workbookResults['annualRows']),
        ],
        'fixture' => $workbookFixture,
    ];
}

/**
 * @return array<string, mixed>
 */
function analyzeSheet(Worksheet $sheet): array
{
    $highestRow = $sheet->getHighestDataRow();
    $highestColumn = $sheet->getHighestDataColumn();
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
    $rows = [];
    $formulas = [];
    $sampleValues = [];
    $externalLinks = [];

    for($row = 1; $row <= $highestRow; $row++) {
        $rowValues = [];
        for($column = 1; $column <= $highestColumnIndex; $column++) {
            $coordinate = Coordinate::stringFromColumnIndex($column).$row;
            $cell = $sheet->getCell($coordinate);
            $rawValue = $cell->getValue();
            if($rawValue === null || $rawValue === '') {
                continue;
            }

            $normalizedValue = normalizeCellValue($rawValue);
            $cachedValue = cachedCellValue($cell);
            $rowValues[$coordinate] = [
                'coordinate' => $coordinate,
                'column' => $column,
                'value' => $normalizedValue,
                'cachedValue' => normalizeCellValue($cachedValue),
                'formattedValue' => safeFormattedValue($cell, $cachedValue),
            ];

            if(count($sampleValues) < 120) {
                $sampleValues[] = [
                    'cell' => $coordinate,
                    'value' => $normalizedValue,
                    'formattedValue' => safeFormattedValue($cell, $cachedValue),
                ];
            }

            if($cell->getDataType() === DataType::TYPE_FORMULA || (is_string($rawValue) && str_starts_with($rawValue, '='))) {
                $formula = (string)$rawValue;
                $formulas[] = [
                    'cell' => $coordinate,
                    'formula' => $formula,
                    'cachedValue' => normalizeCellValue($cachedValue),
                    'formattedValue' => safeFormattedValue($cell, $cachedValue),
                ];
                $externalLinks = array_merge($externalLinks, findExternalReferences($formula));
            }
        }
        if($rowValues) {
            $rows[$row] = $rowValues;
        }
    }

    return [
        'highestRow' => $highestRow,
        'highestColumn' => $highestColumn,
        'formulas' => $formulas,
        'sampleValues' => $sampleValues,
        'inputRows' => detectInputRows($rows),
        'annualRows' => detectAnnualRows($rows),
        'externalLinks' => array_values(array_unique($externalLinks)),
    ];
}

/**
 * @param object $spreadsheet
 * @return list<array<string, mixed>>
 */
function extractNamedRanges(object $spreadsheet): array
{
    $namedRanges = [];
    foreach($spreadsheet->getNamedRanges() as $name => $namedRange) {
        $worksheet = method_exists($namedRange, 'getWorksheet') ? $namedRange->getWorksheet() : null;
        $namedRanges[] = [
            'name' => is_string($name) ? $name : $namedRange->getName(),
            'range' => $namedRange->getRange(),
            'sheet' => $worksheet ? $worksheet->getTitle() : null,
            'localOnly' => method_exists($namedRange, 'isLocalOnly') ? $namedRange->isLocalOnly() : null,
            'scope' => method_exists($namedRange, 'getScope') && $namedRange->getScope() ? $namedRange->getScope()->getTitle() : null,
        ];
    }
    return $namedRanges;
}

/**
 * @param object $spreadsheet
 * @return list<string>
 */
function extractWorkbookExternalLinks(object $spreadsheet): array
{
    $links = [];
    if(method_exists($spreadsheet, 'getExternalSheetNames')) {
        foreach((array)$spreadsheet->getExternalSheetNames() as $externalSheetName) {
            $links[] = (string)$externalSheetName;
        }
    }
    if(method_exists($spreadsheet, 'getExternalDefinedNames')) {
        foreach((array)$spreadsheet->getExternalDefinedNames() as $externalDefinedName) {
            $links[] = json_encode($externalDefinedName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: (string)$externalDefinedName;
        }
    }
    return array_values(array_unique(array_filter($links)));
}

/**
 * @param array<int, array<string, array<string, mixed>>> $rows
 * @return list<array<string, mixed>>
 */
function detectInputRows(array $rows): array
{
    $items = [];
    foreach($rows as $rowNumber => $cells) {
        $text = strtolower(rowText($cells));
        if(!containsAny($text, inputKeywords())) {
            continue;
        }

        $values = extractNumericOrFormulaCells($cells);
        if(!$values) {
            continue;
        }

        $items[] = [
            'row' => $rowNumber,
            'label' => trim(rowLabel($cells)),
            'values' => array_slice($values, 0, 12),
        ];
        if(count($items) >= 120) {
            break;
        }
    }
    return $items;
}

/**
 * @param array<int, array<string, array<string, mixed>>> $rows
 * @return list<array<string, mixed>>
 */
function detectAnnualRows(array $rows): array
{
    $items = [];
    foreach($rows as $rowNumber => $cells) {
        $text = strtolower(rowText($cells));
        if(!containsAny($text, resultKeywords())) {
            continue;
        }

        $values = extractYearValueCells($cells);
        if(count($values) < 2) {
            continue;
        }

        $items[] = [
            'row' => $rowNumber,
            'label' => trim(rowLabel($cells)),
            'values' => array_slice($values, 0, 30),
        ];
        if(count($items) >= 160) {
            break;
        }
    }
    return $items;
}

/**
 * @param array<string, array<string, mixed>> $cells
 */
function rowText(array $cells): string
{
    return implode(' ', array_map(static fn(array $cell): string => (string)($cell['formattedValue'] ?? $cell['value'] ?? ''), $cells));
}

/**
 * @param array<string, array<string, mixed>> $cells
 */
function rowLabel(array $cells): string
{
    $labels = [];
    foreach($cells as $cell) {
        $value = $cell['formattedValue'] ?? $cell['value'] ?? '';
        if(is_string($value) && !is_numeric(str_replace(['.', ',', '%', '€', ' '], '', $value))) {
            $labels[] = $value;
        }
        if(count($labels) >= 3) {
            break;
        }
    }
    return implode(' / ', $labels);
}

/**
 * @param array<string, array<string, mixed>> $cells
 * @return list<array<string, mixed>>
 */
function extractNumericOrFormulaCells(array $cells): array
{
    $values = [];
    foreach($cells as $cell) {
        $value = $cell['value'];
        $formattedValue = $cell['formattedValue'];
        if(is_numeric($value) || (is_string($value) && str_starts_with($value, '=')) || looksNumeric((string)$formattedValue)) {
            $values[] = [
                'cell' => $cell['coordinate'],
                'value' => $value,
                'cachedValue' => $cell['cachedValue'],
                'formattedValue' => $formattedValue,
            ];
        }
    }
    return $values;
}

/**
 * @param array<string, array<string, mixed>> $cells
 * @return list<array<string, mixed>>
 */
function extractYearValueCells(array $cells): array
{
    $values = [];
    foreach($cells as $cell) {
        $value = $cell['value'];
        $formattedValue = $cell['formattedValue'];
        if(is_numeric($value) || (is_string($value) && str_starts_with($value, '=')) || looksNumeric((string)$formattedValue) || looksLikeYear((string)$formattedValue)) {
            $values[] = [
                'cell' => $cell['coordinate'],
                'value' => $value,
                'cachedValue' => $cell['cachedValue'],
                'formattedValue' => $formattedValue,
            ];
        }
    }
    return $values;
}

function containsAny(string $text, array $keywords): bool
{
    foreach($keywords as $keyword) {
        if(str_contains($text, $keyword)) {
            return true;
        }
    }
    return false;
}

/**
 * @return list<string>
 */
function inputKeywords(): array
{
    return [
        'annahme',
        'eingabe',
        'input',
        'kaufpreis',
        'invest',
        'kwp',
        'leistung',
        'ertrag',
        'degradation',
        'zins',
        'tilgung',
        'steuer',
        'afa',
        'sonder',
        'iab',
        'batter',
        'speicher',
        'capex',
        'opex',
        'eeg',
        'verguetung',
        'vergütung',
        'pacht',
        'kosten',
        'eigenkapital',
        'fremdkapital',
        'rendite',
    ];
}

/**
 * @return list<string>
 */
function resultKeywords(): array
{
    return [
        'cashflow',
        'cash-flow',
        'cf',
        'ergebnis',
        'gewinn',
        'steuer',
        'liquid',
        'rendite',
        'irr',
        'npv',
        'barwert',
        'restschuld',
        'tilgung',
        'zins',
        'erlös',
        'erloes',
        'einnahmen',
        'ausgaben',
        'überschuss',
        'ueberschuss',
        'ausschüttung',
        'ausschuettung',
        'jahres',
    ];
}

function looksNumeric(string $value): bool
{
    $normalized = str_replace(['.', ',', '%', '€', ' '], ['', '.', '', '', ''], $value);
    return $normalized !== '' && is_numeric($normalized);
}

function looksLikeYear(string $value): bool
{
    return (bool)preg_match('/\b20[0-9]{2}\b/', $value);
}

function normalizeCellValue(mixed $value): mixed
{
    if($value instanceof DateTimeInterface) {
        return $value->format(DateTimeInterface::ATOM);
    }
    if(is_scalar($value) || $value === null) {
        return $value;
    }
    return (string)$value;
}

function cachedCellValue(object $cell): mixed
{
    if($cell->getDataType() !== DataType::TYPE_FORMULA) {
        return $cell->getValue();
    }
    if(method_exists($cell, 'getOldCalculatedValue')) {
        return $cell->getOldCalculatedValue();
    }
    try {
        return $cell->getCalculatedValue();
    }
    catch(Throwable) {
        return null;
    }
}

function safeFormattedValue(object $cell, mixed $cachedValue = null): string
{
    if($cell->getDataType() === DataType::TYPE_FORMULA) {
        $value = $cachedValue ?? $cell->getOldCalculatedValue();
        return (string)normalizeCellValue($value);
    }
    try {
        return (string)$cell->getFormattedValue();
    }
    catch(Throwable) {
        return (string)normalizeCellValue($cell->getValue());
    }
}

/**
 * @return list<string>
 */
function findExternalReferences(string $formula): array
{
    $references = [];
    if(preg_match_all('/\[[^\]]+\]/', $formula, $matches)) {
        foreach($matches[0] as $match) {
            $references[] = trim($match, '[]');
        }
    }
    if(preg_match_all('/https?:\/\/[^\'"\s)]+/i', $formula, $matches)) {
        foreach($matches[0] as $match) {
            $references[] = $match;
        }
    }
    return array_values(array_unique($references));
}

/**
 * @param list<array<string, mixed>> $inputs
 * @param list<array<string, mixed>> $annualRows
 * @return list<array<string, string>>
 */
function detectSuspectedIssues(array $inputs, array $annualRows): array
{
    $issues = [
        [
            'topic' => 'Excel ist Referenz, nicht Quelllogik',
            'note' => 'Die extrahierten Werte duerfen nicht blind reproduziert werden. Fachliche Regeln werden im Domainmodell definiert.',
        ],
        [
            'topic' => 'Kaufnebenkosten',
            'note' => 'Aktivierbare Anschaffungsnebenkosten duerfen nicht zugleich sofort als Aufwand abgezogen werden.',
        ],
        [
            'topic' => 'Zinsen',
            'note' => 'Zinswerte muessen in Cashflow und Steuerberechnung aus demselben Finanzierungsmodell stammen.',
        ],
        [
            'topic' => 'Startzeitpunkte',
            'note' => 'Kauf, EEG-Inbetriebnahme, Netzanschluss, Erloesstart, AfA-Start, Darlehensstart und Tilgungsstart bleiben unabhaengig konfigurierbar.',
        ],
        [
            'topic' => 'Steuerzeitpunkt',
            'note' => 'Steuererstattungen und Steuerzahlungen werden nicht automatisch im selben Jahr als Liquiditaet behandelt.',
        ],
    ];

    $combinedLabels = strtolower(json_encode([$inputs, $annualRows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    if(str_contains($combinedLabels, 'nebenkosten') && str_contains($combinedLabels, 'afa')) {
        $issues[] = [
            'topic' => 'Pruefhinweis Kaufnebenkosten/AfA',
            'note' => 'Die Datei enthaelt Hinweise auf Nebenkosten und AfA. Bei Modellabgleich explizit pruefen, ob eine Doppelzaehlung vorliegt.',
        ];
    }
    if(str_contains($combinedLabels, 'steuer') && (str_contains($combinedLabels, 'erstattung') || str_contains($combinedLabels, 'rück') || str_contains($combinedLabels, 'rueck'))) {
        $issues[] = [
            'topic' => 'Pruefhinweis Steuererstattung',
            'note' => 'Die Datei enthaelt Steuer-/Erstattungshinweise. Zahlungsjahr und Wirkungsjahr fachlich getrennt pruefen.',
        ];
    }

    return $issues;
}

function renderInventory(array $inventory): string
{
    $markdown = "# Excel-Inventar\n\n";
    $markdown .= "Generiert am: `{$inventory['generatedAt']}`\n\n";
    $markdown .= "Quelle: `{$inventory['sourceDirectory']}`\n\n";
    $markdown .= "> Excel-Werte sind Beispiele und Referenzen. Sie sind keine verbindliche Businesslogik.\n\n";

    foreach($inventory['files'] as $file) {
        $markdown .= "## {$file['file']}\n\n";
        $markdown .= "- Tabellenblaetter: ".implode(', ', array_map('markdownCode', $file['sheetNames']))."\n";
        $markdown .= "- Versteckte Tabellenblaetter: ".($file['hiddenSheets'] ? implode(', ', array_map(static fn(array $sheet): string => markdownCode($sheet['sheet'].' ('.$sheet['state'].')'), $file['hiddenSheets'])) : 'keine gefunden')."\n";
        $markdown .= "- Benannte Bereiche: ".count($file['namedRanges'])."\n";
        $markdown .= "- Formeln: {$file['formulaCount']}\n";
        $markdown .= "- Externe Referenzen: ".($file['externalLinks'] ? implode(', ', array_map('markdownCode', $file['externalLinks'])) : 'keine gefunden')."\n\n";

        if($file['namedRanges']) {
            $markdown .= "### Benannte Bereiche\n\n";
            $markdown .= "| Name | Bereich | Tabelle | Lokal |\n";
            $markdown .= "| --- | --- | --- | --- |\n";
            foreach($file['namedRanges'] as $range) {
                $markdown .= '| '.markdownCell((string)$range['name']).' | '.markdownCell((string)$range['range']).' | '.markdownCell((string)($range['sheet'] ?? '')).' | '.markdownCell(var_export($range['localOnly'], true))." |\n";
            }
            $markdown .= "\n";
        }

        foreach($file['sheets'] as $sheet) {
            $markdown .= "### {$sheet['name']}\n\n";
            $markdown .= "- Status: `{$sheet['state']}`\n";
            $markdown .= "- Datenbereich: `A1:{$sheet['highestColumn']}{$sheet['highestRow']}`\n";
            $markdown .= "- Formeln: {$sheet['formulaCount']}\n\n";
            if($sheet['formulas']) {
                $markdown .= "| Zelle | Formel | Cached/Formatierter Wert |\n";
                $markdown .= "| --- | --- | --- |\n";
                foreach(array_slice($sheet['formulas'], 0, 20) as $formula) {
                    $markdown .= '| '.markdownCell($formula['cell']).' | '.markdownCell($formula['formula']).' | '.markdownCell(valueForMarkdown($formula['cachedValue'], $formula['formattedValue']))." |\n";
                }
                $markdown .= "\n";
            }
        }
    }

    return $markdown;
}

function renderInputs(array $workbooks): string
{
    $markdown = "# Excel-Eingaben und Annahmen\n\n";
    $markdown .= "> Automatisch extrahierte Kandidaten. Die Liste ist heuristisch und muss fachlich validiert werden.\n\n";

    foreach($workbooks as $workbook) {
        $markdown .= "## {$workbook['file']}\n\n";
        if(!$workbook['items']) {
            $markdown .= "Keine relevanten Eingabezeilen erkannt.\n\n";
            continue;
        }
        $markdown .= "| Tabelle | Zeile | Label | Werte |\n";
        $markdown .= "| --- | ---: | --- | --- |\n";
        foreach($workbook['items'] as $item) {
            $markdown .= '| '.markdownCell($item['sheet']).' | '.$item['row'].' | '.markdownCell($item['label']).' | '.markdownCell(renderValueList($item['values']))." |\n";
        }
        $markdown .= "\n";
    }

    return $markdown;
}

function renderResults(array $workbooks): string
{
    $markdown = "# Excel-Ergebnisreihen\n\n";
    $markdown .= "> Automatisch extrahierte Kandidaten fuer jaehrliche Ergebniszeilen. Diese Werte sind Beispiele und keine verbindliche Businesslogik.\n\n";

    foreach($workbooks as $workbook) {
        $markdown .= "## {$workbook['file']}\n\n";
        if(!$workbook['annualRows']) {
            $markdown .= "Keine relevanten jaehrlichen Ergebniszeilen erkannt.\n\n";
            continue;
        }
        $markdown .= "| Tabelle | Zeile | Label | Werte |\n";
        $markdown .= "| --- | ---: | --- | --- |\n";
        foreach($workbook['annualRows'] as $row) {
            $markdown .= '| '.markdownCell($row['sheet']).' | '.$row['row'].' | '.markdownCell($row['label']).' | '.markdownCell(renderValueList($row['values']))." |\n";
        }
        $markdown .= "\n";
    }

    return $markdown;
}

function renderDeviations(array $workbooks): string
{
    $markdown = "# Excel-Abweichungen\n\n";
    $markdown .= "Die Excel-Dateien in `docs/reference-excel/` sind Referenzmaterial. Sie werden nicht blind nachgebaut und sind nicht die Produktions-Rechenengine.\n\n";
    $markdown .= "## Grundsatz\n\n";
    $markdown .= "Abweichungen von Excel sind zulaessig und erwuenscht, wenn sie fachlich richtiger, transparenter oder flexibler sind. Jede fachlich relevante Abweichung soll dokumentiert werden.\n\n";
    $markdown .= "## Bekannte Punkte, die nicht uebernommen werden\n\n";
    $markdown .= "- Kaufnebenkosten duerfen nicht doppelt gezaehlt werden.\n";
    $markdown .= "- Aktivierbare Anschaffungsnebenkosten erhoehen die AfA-Basis und sind nicht zugleich sofort abzugsfaehige Ausgaben.\n";
    $markdown .= "- Zinsen muessen in Cashflow und Steuerberechnung konsistent behandelt werden.\n";
    $markdown .= "- Kauf, EEG-Inbetriebnahme, Netzanschluss, Erloesstart, Abschreibungsstart, Darlehensstart und Tilgungsstart bleiben unabhaengig konfigurierbar.\n";
    $markdown .= "- Steuererstattungen werden nicht automatisch im selben Jahr als Liquiditaet angenommen.\n\n";
    $markdown .= "## Automatisch erzeugte Pruefhinweise\n\n";

    foreach($workbooks as $workbook) {
        $markdown .= "### {$workbook['file']}\n\n";
        foreach($workbook['suspectedIssues'] as $issue) {
            $markdown .= "- **{$issue['topic']}**: {$issue['note']}\n";
        }
        $markdown .= "\n";
    }

    $markdown .= "## Dokumentationsschema fuer spaetere Abweichungen\n\n";
    $markdown .= "- Referenzdatei\n";
    $markdown .= "- Tabellenblatt oder Bereich\n";
    $markdown .= "- Excel-Verhalten\n";
    $markdown .= "- Modellverhalten\n";
    $markdown .= "- fachliche Begruendung\n";
    $markdown .= "- Auswirkung auf Ergebniswerte\n";

    return $markdown;
}

function renderValueList(array $values): string
{
    $parts = [];
    foreach($values as $value) {
        $parts[] = $value['cell'].': '.valueForMarkdown($value['cachedValue'], $value['formattedValue']);
    }
    return implode('; ', $parts);
}

function valueForMarkdown(mixed $cachedValue, mixed $formattedValue): string
{
    $value = $formattedValue !== '' ? $formattedValue : $cachedValue;
    if($value === null) {
        return '';
    }
    if(is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    return (string)$value;
}

function markdownCode(string $value): string
{
    return '`'.str_replace('`', '\`', $value).'`';
}

function markdownCell(string $value): string
{
    $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
    return str_replace('|', '\\|', $value);
}

function relativePath(string $root, string $path): string
{
    $root = rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/').'/';
    $path = str_replace('\\', '/', realpath($path) ?: $path);
    if(str_starts_with($path, $root)) {
        return substr($path, strlen($root));
    }
    return $path;
}

function writeFile(string $file, string $content): void
{
    $directory = dirname($file);
    ensureDirectory($directory);
    if(file_put_contents($file, $content) === false) {
        throw new RuntimeException("Datei konnte nicht geschrieben werden: $file");
    }
}
