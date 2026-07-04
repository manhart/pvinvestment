# Parameterkatalog

Dieses Dokument sammelt die geplanten Eingabeparameter fuer den PV-Investitionsrechner. Es ist eine fachliche Struktur, keine Implementierungsvorgabe fuer eine Excel-Zelllogik.

## Szenario

- Szenarioname
- Szenariogruppe
- Betrachtungsbeginn
- Betrachtungsende
- Auswertungswaehrung
- Inflationsannahme
- Diskontierungszins

## Zeitpunkte

Die folgenden Startzeitpunkte sind unabhaengig voneinander zu erfassen:

- Kaufdatum
- EEG-Inbetriebnahme
- Netzanschluss
- Erloesstart
- Abschreibungsstart je Wirtschaftsgut
- Darlehensstart
- Tilgungsstart
- Steuerzahlung oder Steuererstattung
- Sparplanstart

## PV-Anlage

- Anlagenname
- Standort
- Leistung in kWp
- spezifischer Jahresertrag
- Degradation pro Jahr
- Investitionskosten PV
- aktivierbare Anschaffungsnebenkosten
- Maklercourtage/Vermittlungsprovision als aktivierbare Anschaffungsnebenkosten, sofern dem Erwerb des Wirtschaftsguts zuzuordnen
- sofort abzugsfaehige Kosten
- laufende Betriebskosten
- Wartung
- Versicherung
- Pacht
- Direktvermarktungskosten
- EEG-Verguetung
- Marktwert Solar
- Strompreisannahme

## Batterie

- `battery_model`: `none`, `full_ownership`, `profit_sharing`, `custom`
- `battery_capex`
- `battery_opex`
- `battery_degradation_rate`
- `battery_replacement_year`
- `battery_replacement_cost`
- `optimizer_fee`
- `market_access_fee`
- `sharing_base`: `gross_revenue`, `net_revenue`, `net_margin`
- `investor_battery_revenue_share`
- `operator_battery_revenue_share`
- `investor_battery_cost_share`
- `operator_battery_cost_share`

## Finanzierung

- Darlehensbetrag
- Auszahlungstermin
- Zinsstart
- Tilgungsstart
- Sollzins
- Tilgungssatz
- Annuitaet
- Zinsbindung
- Laufzeit
- Sondertilgungen
- Tilgungsfreie Monate
- Finanzierungsnebenkosten
- steuerliche Behandlung von Zinsen und Finanzierungsnebenkosten

## Steuern

- Einkommensteuersatz je Jahr oder Phase
- Steuersatz vor und nach Ruhestand
- IAB aktiv/inaktiv
- IAB-Prozentsatz
- IAB-Basis
- IAB-beguenstigte Anschaffungskosten
- IAB-beguenstigte aktivierungspflichtige Anschaffungsnebenkosten
- IAB-Jahr
- Sonder-AfA aktiv/inaktiv
- Sonder-AfA-Prozentsatz
- Sonder-AfA-Verteilung
- lineare AfA
- degressive AfA
- Wechsel von degressiver zu linearer AfA
- AfA-Start je Wirtschaftsgut
- Verlustbehandlung: Sofortnutzung, Vortrag, Ruecktrag, manuell
- Steuerzahlungs- und Erstattungsverzug

## Sparplan

- Startkapital
- monatlicher Beitrag
- jaehrlicher Beitrag
- Beitrag aus positivem PV-Cashflow
- Reinvestitionsrate aus freiem Cashflow
- erwartete Rendite pro Jahr
- Kostenquote
- Kapitalertragsteuer
- Teilfreistellung
- Sparer-Pauschbetrag
- Entnahmephase

## Ergebnisse

- monatlicher Cashflow
- jaehrlicher Cashflow
- Steuerwirkung
- Liquiditaetsbedarf
- Restschuld
- Buchwerte
- kumulierter freier Cashflow
- Sparplanwert
- Gesamtvermoegenseffekt
- Renditekennzahlen
- Szenariovergleich

## Formularstatus im POOL-Prototyp

Das serverseitige Eingabeformular nimmt Prozentwerte als Prozentzahl `0..100` entgegen und wandelt sie im `ScenarioFormMapper` an der Domain-Grenze in Dezimalraten `0..1` um. Geldwerte werden als Euro-Dezimalzahlen erwartet. Monate sind Kalendermonate `1..12`, Jahre sind Kalenderjahre.

Aktuell editierbar und produktiv gemappt:

- Projektdaten: Szenarioname, Startjahr, Laufzeit.
- PV: expliziter PV-Jahreserloes und laufende PV-Betriebskosten.
- Batterie: Modell `none`, `full_ownership`, `profit_sharing`, Bruttoerloes, Market Access Fee, Optimizer Fee, OPEX, `sharing_base`, Investor Revenue Share, Investor Cost Share, Capex, Investor Capex Share, Capex-Zahlungsmonat, Degradation, Ersatzinvestition, Ersatzkosten und Investor Replacement Cost Share.
- Finanzierung: Fremdkapitalbetrag, Zinssatz p.a. und jaehrliche Tilgung. Der Mapper bildet daraus `annualInterest = debtAmount * interestRate` und `annualRepayment`.
- Steuer: Einkommensteuersatz, IAB aktiv/Satz, Sonder-AfA aktiv/Satz, AfA-Methode, lineare/degressive AfA-Saetze, Verlustnutzung und Steuerzahlungsversatz in Monaten.
- Kosten: Anschaffungskosten und Maklercourtage mit Behandlung `capitalize`, `immediate` oder `ignore`; Default ist aktivierungspflichtig und IAB-beguenstigt.
- Timing: Investitionsdatum, AfA-Beginn, EEG-Inbetriebnahme, Netzanschluss, Ertragsbeginn, Zinsbeginn, Tilgungsbeginn.
- Sparplan: Startkapital, monatliche Einzahlung, jaehrliche Einzahlung, Reinvestitionsquote aus positivem Cashflow und Einzahlungszeitpunkte.

Aktuell im Formular sichtbar, aber noch nicht als eigene produktive Fachlogik verwendet:

- Anlagenleistung kWp und spezifischer Jahresertrag: Kontextfelder; der Rechner nutzt weiterhin den expliziten PV-Jahreserloes.
- Betriebskostensteigerung: validiert, aber noch nicht in eine Kostenzeitreihe ueberfuehrt.
- Erwartete Sparplanrendite, Kostenquote und Kapitalertragsteuer sind noch nicht Teil des Formulars, weil der Sparplan-Prototyp diese Werte fachlich noch nicht berechnet.
