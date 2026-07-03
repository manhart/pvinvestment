# PV-Investitionsrechner

Der PV-Investitionsrechner ist eine POOL-PHP-Anwendung fuer die modellhafte Bewertung von Photovoltaik-Freiflaechenanlagen mit Batterie, Finanzierung, Steuern und Sparplan/Reinvestition.

Die Anwendung soll investororientierte Szenarien transparenter und flexibler abbilden als die vorhandenen Excel-Dateien. Die Excel-Dateien in `docs/reference-excel/` sind Referenzmaterial und Beispiele, aber nicht die Produktions-Rechenengine.

## Pfade

- Anwendung: `/virtualweb/manhart/pvinvestment`
- Browser: `http://pofolio.local/manhart/pvinvestment/`
- Framework: `/virtualweb/manhart/pool`

## Entwicklungsstand

Diese Version enthaelt eine POOL-kompatible Anwendungsstruktur und einen getesteten Domain-Prototyp ohne produktive GUI und ohne Datenbank.

Implementiert sind:

- PV- und Batterieannahmen als Domain-Objekte.
- Batteriemodelle fuer keine Batterie, Vollerwerb und Profit-Sharing.
- Explizite Batterie-Sharing-Basen `gross_revenue`, `net_revenue` und `net_margin`.
- Ein steuerliches Anlageverzeichnis mit mehrjaehriger AfA-Fortschreibung.
- Steuerlogik mit IAB-Grundmodell, Sonder-AfA, Verlustnutzung und Steuer-Cashflow-Timing.
- Szenariovergleich fuer mehrere Investorenszenarien.
- Monatsengine mit Aggregation zu Jahreswerten.
- Geld-/Prozent-Value-Objects fuer zentrale Rundungs- und Prozentlogik.
- Anonymisierte Domain-Referenzwerte und Excel-Abweichungsdokumentation.

Noch nicht enthalten:

- produktive Bedienoberflaechen fuer Eingabe und Auswertung
- Persistenz von Szenarien
- vollstaendige PV-Ertragsmodellierung mit saisonalen Profilen
- gesetzliche Steuerberatung oder automatische steuerliche Einzelfallbewertung

## POOL-Konventionen fuer diese Anwendung

Aus `realestateinvestment` und `pofolio` werden folgende Konventionen uebernommen:

- Einstieg ueber `index.php`.
- Bootstrap ueber `config/bootstrap.php` und `/virtualweb/manhart/pool/pool.lib.php`.
- Eine App-Klasse unter `classes/`, die `pool\classes\Core\Weblication` erweitert.
- GUI-Module unter `guis/GUI_Name/`.
- GUI-Klassen mit `GUI_*`-Namen.
- Templates mit Namen `tpl_*.html`.
- JavaScript und CSS werden ueber die POOL-Ressourcensuche bzw. POOL-Resource-Klassen geladen.
- App-weite Ressourcen liegen in `css/app.css` und `js/app.js`.
- Fachliche Logik gehoert nicht in GUI-Klassen oder Templates, sondern spaeter in Domain-/Serviceklassen.

## Domain-Module

Die Berechnungslogik ist in klar getrennten Klassen aufgebaut:

- PV-Produktion
- Batterieerloese
- Revenue Sharing
- Finanzierung
- Steuer
- Cashflow
- Sparplan / Reinvestition
- Szenariovergleich

Interne Berechnungen erfolgen monatlich, wo Zeitpunkte relevant sind. Jahreswerte werden daraus aggregiert.

Der Rechner ist ein Planungs- und Transparenzwerkzeug. Er ist keine Steuerberatung.

## Dokumentation

Die fachliche Spezifikation liegt unter `docs/model-spec/`:

- `parameter-katalog.md`
- `rechenlogik.md`
- `steuerlogik.md`
- `batteriemodelle.md`
- `sparplan.md`
- `excel-abweichungen.md`
- `domain-referenzwerte.md`

## Excel-Extraktion

Das Tool `tools/excel/extract_excel.php` liest lokale Excel-Dateien aus `docs/reference-excel/` und erzeugt daraus Inventar-, Eingabe-, Ergebnis- und Fixture-Dateien.

Wichtig: Das GitHub-Repository ist oeffentlich. Original-Excel-Dateien, Exposes und lokale Extrakte werden standardmaessig ignoriert. Oeffentlich committete Fixtures sind anonymisiert.

```bash
php8.4 tools/excel/extract_excel.php
```

Das Tool nutzt PhpSpreadsheet ueber Composer. Falls PhpSpreadsheet nicht installiert ist, bricht das Tool bewusst ab und erzeugt keine Auswertung:

```bash
cd /virtualweb/manhart/pvinvestment
composer install
```

## Tests

Die Repository-Vorgabe nennt PHPUnit 13 als Zielbaseline. In dieser VM ist lokal aktuell `/usr/local/bin/phpunit` in Version 9.6.10 verfuegbar. Bis die lokale Projekt-Toolchain bewusst aktualisiert wird, werden Tests in `pvinvestment` kompatibel mit PHPUnit 9.6.10 geschrieben.

Keine globale PHPUnit-Aktualisierung fuer dieses Projekt durchfuehren. Falls spaeter PHPUnit 13 projektlokal eingefuehrt wird, soll das ueber eine explizite Projekt-Dependency und eine separate Migration der Tests erfolgen.

```bash
php8.4 /usr/local/bin/phpunit --do-not-cache-result --configuration phpunit.xml
```
