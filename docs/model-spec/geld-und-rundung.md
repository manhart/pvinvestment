# Geld-, Prozent- und Rundungsmodell

## Aktueller Stand

Der Domain-Prototyp verarbeitet Geldbetraege aktuell ueberwiegend als Euro-`float`.

Betroffene Klassen:

- `PvAssumptions`: `annualRevenue`, `annualOperatingCosts`
- `BatteryModel`: `annualRevenue`, `annualOperatingCosts`
- `FinancingAssumptions`: `annualInterest`, `annualRepayment`
- `TaxAssumptions`: `annualTaxPayment`, `acquisitionCost`, `capitalizableAncillaryCosts`, `immediatelyDeductibleCosts`, `iabAmount`
- `SavingsPlanAssumptions`: `startingCapital`, `monthlyContribution`, `annualContribution`
- `TaxCalculationResult`, `CalculationResult`, `MonthResult` und `YearResult`: Ergebnis-Geldwerte

Prozentwerte werden aktuell als Dezimalzahlen zwischen `0.0` und `1.0` erwartet:

- `0.35` bedeutet 35 Prozent.
- `35` ist nicht gueltig.
- Basispunkte wie `3500` werden nicht akzeptiert.
- Betroffene Felder: Steuersatz, AfA-Saetze, Batterieanteile, Kostenanteile, Reinvestitionsrate.

## Rundungsrisiken

- `float` kann bei wiederholter Addition, Subtraktion und Prozentrechnung technische Nachkommadrifts erzeugen.
- Monatsfaktoren wie `7 / 12` erzeugen fachlich gewollte Zwischenwerte mit mehr als zwei Nachkommastellen.
- Monatliche Reihen addieren viele ungerundete Zwischenergebnisse; dadurch koennen technische Nachkommadrifts sichtbar werden.
- Aktuell gibt es keine zentrale Rundung; dadurch ist nicht explizit, wann Ergebniswerte auf Cent fixiert werden.

## Zielmodell

KISS-Entscheidung fuer den naechsten Schritt:

- Eingaben duerfen im Prototyp weiterhin als Euro-`float` angenommen werden.
- Geld-Endergebnisse sollen ueber `MoneyAmount` centgenau gerundet werden koennen.
- Prozentwerte sollen ueber `PercentageRate` eindeutig als Dezimalrate, Prozentwert oder Basispunkte erzeugbar sein.
- Die bestehende Domain wird nicht vollstaendig auf Value Objects umgebaut, bis die mehrjaehrige Monatsrechnung stabil ist.

## Rundungsvertrag

Auf Cent zu runden:

- Steuerbetrag (`taxAmount`)
- cashflowwirksame Steuerzahlung oder Erstattung (`cashflowTaxPayment`)
- spaetere Zahlungsstroeme in Ausgabe-/Persistenzsicht
- spaetere aggregierte Jahreswerte, wenn sie als Berichtswert ausgegeben werden

Nicht vorschnell runden:

- AfA-Basis
- Monatsfaktoren
- anteilige Monatswerte innerhalb einer Jahresrechnung
- technische Zwischenwerte fuer kWh, kWp, Wirkungsgrad, Degradation, Anteilssaetze
- Monatswerte in `MonthResult`, solange sie Grundlage fuer Jahresaggregation sind
- Barwert-/IRR-Zwischenwerte, falls spaeter implementiert

Als Cent-Integer modellieren:

- finale Geldbetraege in Zahlungsstroemen
- Steuerzahlungen und Steuererstattungen
- Sparplan-Einzahlungen und -Endwerte, sobald Rendite/Steuern modelliert werden
- persistierte Ergebniswerte

Als technische Dezimalwerte belassen:

- kWh, kWp, spezifischer Ertrag
- Wirkungsgrad, Degradation, Verfuegbarkeit
- Prozentsaetze und Anteile als `PercentageRate`
- Monatsfaktoren
- Diskontierungsfaktoren

## Value Objects

`MoneyAmount`:

- speichert intern Cent als Integer
- erzeugt Werte aus Euro-Decimal oder Cent
- addiert/subtrahiert centgenau
- multipliziert mit `PercentageRate` und rundet auf Cent
- gibt Euro als Decimal-Float nur fuer bestehende Schnittstellen zurueck

`PercentageRate`:

- speichert intern eine Dezimalrate
- kann aus Dezimalrate, Prozentwert oder Basispunkten erzeugt werden
- validiert gegen negative Werte
- macht die Bedeutung von `0.35`, `35` und `3500` explizit

`YearMonth`:

- kapselt Kalenderjahr und Kalendermonat.
- validiert Monate von 1 bis 12.
- wird fuer Monatsvergleiche in der Monatsengine genutzt.
