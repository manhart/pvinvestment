# Sparplan

Der Sparplan ist ein eigenstaendiges Modul und darf nicht automatisch aus der PV-Berechnung abgeleitet werden. Positive PV-Cashflows koennen als Beitrag genutzt werden, wenn dies konfiguriert ist.

## Eingaben

- frei eingegebenes Startkapital
- monatlicher Beitrag
- jaehrlicher Beitrag
- Beitrag aus positivem PV-Cashflow
- Reinvestitionsrate aus freiem Cashflow
- erwartete Rendite pro Jahr
- Kostenquote
- Kapitalertragsteuer
- Teilfreistellung
- Sparer-Pauschbetrag
- Start der Entnahmephase
- Entnahmebetrag oder Entnahmerate

## Zeitlogik

Einzahlungen und Entnahmen werden monatlich modelliert. Jaehrliche Werte werden auf die vorgesehenen Monate gelegt und anschliessend aggregiert.

## Reinvestition aus PV-Cashflow

Der positive freie Cashflow der PV-Anlage kann ganz oder anteilig in den Sparplan fliessen:

- 0 Prozent: keine Reinvestition
- 100 Prozent: vollstaendige Reinvestition
- individueller Prozentsatz: anteilige Reinvestition

Negative PV-Cashflows duerfen nicht automatisch aus dem Sparplan gedeckt werden, ausser ein spaeterer Parameter sieht dies ausdruecklich vor.

## Steuern und Kosten

Die Sparplanberechnung soll Kosten und Steuern getrennt ausweisen:

- Kostenquote als laufende Belastung
- Kapitalertragsteuer auf steuerpflichtige Gewinne
- Teilfreistellung, falls anwendbar
- Sparer-Pauschbetrag, falls anwendbar

## Ergebnisse

- Sparplanwert vor Steuer
- Sparplanwert nach Steuer
- Summe der Einzahlungen
- Summe der aus PV-Cashflow reinvestierten Betraege
- Kosten
- Steuerbelastung
- Entnahmen
- Endwert

