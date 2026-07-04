# Rechenlogik

Der PV-Investitionsrechner wird als Domainmodell aufgebaut und nicht als Excel-Zellklon. Fachlogik gehoert in Service- und Value-Object-Klassen, nicht in GUI-Module oder Templates.

## Grundprinzip

Die interne Berechnung erfolgt monatlich, wenn Zeitpunkte relevant sind. Jahreswerte werden aus Monatswerten aggregiert.

Monatliche Logik ist erforderlich fuer:

- Investitionsdatum
- Erloesstart
- Netzanschluss
- EEG-Inbetriebnahme
- Abschreibungsstart
- Zinsstart
- Tilgungsstart
- Steuerzahlung und Steuererstattung
- Sparplaneinzahlungen

Der Domain-Prototyp fuehrt diese Zeitpunkte in `ProjectTimingAssumptions` getrennt. Jahresrechnungen verwenden daraus eigene Monatsfaktoren fuer Erloese, Zinsen, Tilgung, AfA und Sparplanbeitraege. Das Investitionsdatum, EEG-Inbetriebnahme und Netzanschluss bleiben separate Felder und werden nicht implizit mit dem Ertragsbeginn gleichgesetzt.

## Implementierte Monatsengine

`MonthlyScenarioCalculator` erzeugt eine Ergebniszeile je Kalendermonat. `YearlyAggregationCalculator` aggregiert Jahreswerte ausschliesslich aus diesen Monatszeilen. `ScenarioCalculator` nutzt diese Monatsengine als Primaerpfad und leitet `ScenarioResult`, `ScenarioComparison` und Break-even-Jahr aus den aggregierten `YearResult`-Werten ab.

Aktuelle Regeln:

- PV- und Batterieerloese fallen erst ab `revenueStartYear/month` an.
- Laufende PV-Kosten und Batterie-Investor-Kosten fallen im Prototyp ebenfalls ab Ertragsbeginn an.
- Batterie-Capex wird im konfigurierten `capexPaymentYear/month` gebucht; ohne eigene Angabe gilt der Investitionsmonat.
- Batterie-Ersatzinvestitionen werden als separater Investor-Capex im konfigurierten Ersatzmonat gebucht.
- Batterie-Degradation reduziert im Prototyp den Batterie-Bruttoerloes je Kalenderjahr. Das Ertragsstartjahr hat Faktor 1, Folgejahre verwenden `(1 - batteryDegradationRatePerYear) ^ jahre_seit_ertragsstartjahr`.
- Zinsen fallen ab `interestStartYear/month` an.
- Tilgung faellt ab `repaymentStartYear/month` an.
- AfA wird ab `depreciationStartYear/month` monatlich aus der Jahres-AfA verteilt.
- Sparplanbeitraege fallen ab `savingsPlanContributionStartYear/month` an.
- Monatliche Sparplanbeitraege werden monatlich gebucht.
- Jaehrliche Sparplanbeitraege werden im `annualSavingsPlanContributionMonth` gebucht.
- Reinvestition aus positivem freiem Cashflow wird monatlich auf Basis des positiven Monats-Cashflows berechnet.
- Steuerzahlungen und Steuererstattungen werden dem Steuerzahlungsjahr zugeordnet. Weil `ProjectTimingAssumptions` aktuell nur ein Steuerzahlungsjahr und keinen Steuerzahlungsmonat enthaelt, bucht die Monatsengine sie vorerst im Dezember des Zahlungsjahres.

Investitionsdatum, EEG-Inbetriebnahme und Netzanschluss werden in der Monatsengine als getrennte Zeitpunkte mitgefuehrt. Batterie-Capex verwendet den Investitionsmonat als Default-Zahlungsmonat. Der aktuelle Cashflow-Prototyp bucht noch keinen PV-Capex-Zahlungsstrom und verwendet EEG-/Netzanschluss nicht als automatische Sperre fuer den Ertragsbeginn.

Der alte `AnnualInvestorCashflowCalculator` bleibt als separat testbarer Legacy-/Kompatibilitaetspfad bestehen. Er erzeugt keine `ScenarioComparison`-Kennzahlen mehr.

## Geplante Module

## PV-Produktion

Berechnet erwartete Produktion aus Anlagenleistung, spezifischem Ertrag, saisonaler Verteilung und Degradation.

## Batterieerloese

Berechnet Batterieerloese, Kosten, Degradation und Ersatzinvestitionen. Das Modul liefert Bruttoerloese, Nettoerloese und Nettomarge als moegliche Sharing-Basis.

## Revenue Sharing

Verteilt Batterieerloese und Batteriekosten zwischen Investor und Betreiber. Die Verteilung wird parameterbasiert und nicht fest in ein Modell eingebaut.

## Finanzierung

Berechnet Auszahlungen, Zinsen, Tilgung, Restschuld, Sondertilgungen und laufende Annuitaeten. Zinsen werden konsistent fuer Cashflow und Steuerlogik bereitgestellt.

## Steuer

Berechnet steuerliche Bemessungsgrundlagen, AfA, IAB, Sonder-AfA, Zinsabzug, Verlustverrechnung und zeitversetzte Steuerzahlungen oder Erstattungen.

## Cashflow

Fuehrt operative Erloese, Kosten, Finanzierung, Steuern und Investitionen zusammen. Kapitalisierbare Anschaffungskosten werden nicht zugleich als sofortige Ausgabe steuerlich abgezogen.

## Sparplan / Reinvestition

Berechnet Startkapital, laufende Einzahlungen, Reinvestition positiver Cashflows, Kosten, Steuern und Entnahmen.

## Szenariovergleich

Vergleicht mehrere Parametersaetze auf Basis identischer Kennzahlen. Excel-Abweichungen sind zulaessig, wenn sie fachlich begruendet und dokumentiert sind.

## Read-only UI-Pfad

Die erste POOL-GUI ist ein read-only Dashboard. `GUI_PvInvestment` baut keine eigene Rechenlogik auf, sondern laedt anonymisierte Szenarien aus `DemoScenarioFactory`, berechnet sie mit `ScenarioCalculator` und rendert Kennzahlen, Szenariovergleich, Jahreswerte und optionale Monatswerte.

Es gibt in diesem UI-Pfad keine Persistenz, keine Datenbank und keine produktiven Angebots- oder Investorendaten.

## Nicht-Ziele der Initialstruktur

- keine produktive Berechnung
- keine Excel-Auswertung als Laufzeit-Engine
- keine steuerliche Beratung
- keine Persistenzentscheidung
