# Referenzszenarien

Die Referenzszenarien basieren auf den Excel-Dateien in `docs/reference-excel/`. Sie dienen dem fachlichen Abgleich und sind keine Produktiv-Rechenlogik.

Fachlich korrigierte Domain-Erwartungswerte liegen getrennt in `tests/fixtures/domain_expected_values.json` und sind in `docs/model-spec/domain-referenzwerte.md` dokumentiert.

## 1. PV-Batterie Mid Case

Fixture: `tests/fixtures/scenarios/pv_battery_mid_case.json`

Quelle:

- Datei: anonymisierte Beispielarbeitsmappe
- Szenario: anonymisierter Mid Case
- Anlagentyp: PV-Park mit Batterie

Wichtige Eingaben:

- PV-Jahreserloes: 24000 EUR
- Batterie-Jahreserloes: 6000 EUR
- Anschaffungskosten: 200000 EUR
- Aktivierungspflichtige Nebenkosten: 8000 EUR
- EEG-Inbetriebnahme: 2026-09
- Netzanschluss: 2026-11
- AfA-wirksame Monate im ersten Jahr: 4
- Ertragswirksame Monate im ersten Jahr: 2
- Zinsbeginn: 2026-08
- Tilgungsbeginn: 2026-10

Wichtige anonymisierte Vergleichswerte:

- nach Schuldendienst und Steuer: 64000 EUR

## 2. Batterie Vollerwerb

Fixture: `tests/fixtures/scenarios/battery_full_ownership.json`

Quelle:

- Datei: anonymisierte Beispielarbeitsmappe
- Anlagentyp: Batterie-Vollerwerb

Domain-Interpretation:

- `batteryModel = full_ownership`
- Investor-Erloesanteil: 100 Prozent
- Betreiber-Erloesanteil: 0 Prozent
- Investor-Kostenanteil: 100 Prozent
- Betreiber-Kostenanteil: 0 Prozent

Hinweis: Das Domain-Modell trennt Batterieerloese und Batteriekosten explizit. Excel aggregiert viele Werte ueber Cashflow-/Steuerblaetter; diese Aggregation wird nicht ungeprueft als Produktivformel uebernommen.

## 3. Batterie Profit-Sharing 65/35

Fixture: `tests/fixtures/scenarios/battery_profit_sharing_65_35.json`

Quelle:

- Datei: anonymisierte Beispielarbeitsmappe
- Szenariozeile: Profit-Sharing 65/35

Domain-Referenzannahme:

- `batteryModel = profit_sharing`
- `sharingBase = gross_revenue`
- Investor-Erloesanteil: 65 Prozent
- Betreiber-Erloesanteil: 35 Prozent

Wichtig: Das Beispiel enthaelt eine explizite Domain-Definition fuer `sharing_base`. Es ist kein 1:1-Excel-Formelklon.

## Umgang mit Excel

- Excel-Werte sind Beispiel- und Vergleichswerte.
- Abweichungen sind erlaubt, wenn das Domain-Modell fachlich konsistenter ist.
- Bekannte Excel-Risiken werden in `excel-vs-domain-abgleich.md` dokumentiert.
- Domain-Erwartungswerte werden nicht aus Excel-Formeln abgeleitet, sondern mit dem Domain-Rechner reproduziert.
- Produktivlogik darf keine Excel-Formeln ungeprueft uebernehmen.
