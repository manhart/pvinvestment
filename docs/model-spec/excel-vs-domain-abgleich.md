# Excel vs. Domain-Abgleich

Dieses Dokument beschreibt den fachlichen Abstand zwischen den Excel-Beispielen und dem aktuellen Domain-Prototyp.

## Grundsatz

Die Excel-Dateien sind Referenzmaterial. Der Domain-Rechner soll nachvollziehbare, parameterisierte Fachlogik liefern und bekannte oder vermutete Excel-Probleme nicht reproduzieren.

Der konkrete Abweichungsreport fuer die drei Domain-Referenzszenarien steht in `domain-referenzwerte.md`. Die zugehoerigen maschinenlesbaren Domain-Erwartungswerte stehen in `tests/fixtures/domain_expected_values.json`.

## Doppelte Kaufnebenkostenverwertung

Excel:

- Kaufnebenkosten werden als Prozentsatz und in aggregierten Anschaffungskosten ausgewiesen.
- In den Cashflow-/Steuerblaettern ist fachlich zu pruefen, ob dieselben Nebenkosten zugleich aktiviert und sofort liquiditaets-/steuerwirksam beruecksichtigt werden.

Domain:

- `capitalizableAncillaryCosts` erhoehen nur die AfA-Basis.
- `immediatelyDeductibleCosts` werden nur sofort steuerlich abgezogen.
- Eine doppelte steuerliche Verwertung ist im aktuellen Domain-Prototyp nicht vorgesehen.

## Unterschiedliche Zinsbehandlung

Excel:

- Zinsmonate, tilgungsfreie Monate, Darlehensauszahlung und Tilgungsbeginn sind in Annahmen und Finanzierungsblaettern verteilt.
- Zinsen werden in Cashflows per Verweis aus Finanzierungsblaettern gezogen.

Domain:

- Zinsen kommen aus `FinancingAssumptions`.
- Der gleiche Zinsbetrag und derselbe Zins-Monatsfaktor gehen in Steuer und Cashflow ein.
- Damit wird die Zinsbehandlung konsistent gehalten.

## AfA-Beginn vs. Ertragsbeginn

Excel:

- Beispiel A: EEG-Inbetriebnahme und Netzanschluss koennen in unterschiedlichen Monaten liegen.
- Beispiel B: AfA-Monate und Ertragsmonate koennen fachlich voneinander abweichen.

Domain:

- Investitionsdatum, AfA-Beginn, EEG-Inbetriebnahme, Netzanschluss und Ertragsbeginn sind getrennte Zeitpunkte.
- AfA wird nicht aus dem Ertragsbeginn abgeleitet.
- Ertragsmonate werden nicht als AfA-Monate interpretiert.

## Batterie-Vollerwerb vs. Profit-Sharing

Excel:

- Beispiel A beschreibt Batterie-Vollerwerb.
- Beispiel B beschreibt ein Rev-Share-/Batterieszenario.

Domain:

- Vollerwerb: Investor traegt 100 Prozent Batterieerloese und Batteriekosten.
- Profit-Sharing: Investor-/Betreiberanteile werden explizit modelliert.
- Die Domain unterstuetzt `gross_revenue`, `net_revenue` und `net_margin` als explizite Sharing-Basen.
- Excel liefert keine eindeutig benannte `sharing_base`; eine Zuordnung zu den Domain-Basen muss pro Referenzszenario fachlich dokumentiert werden.

## Steuererstattungs-Timing

Excel:

- Steuerwirkungen erscheinen in Cashflow- und Steuerblaettern teilweise als Jahreswerte.
- Es muss fachlich geprueft werden, ob Erstattungen im selben Jahr liquiditaetswirksam angenommen werden.

Domain:

- `taxAmount` beschreibt die Steuerentstehung.
- `cashflowTaxPayment` wirkt nur im Zahlungsjahr.
- Zahlungsversatz wird explizit ueber `taxPaymentYear` bzw. `taxPaymentDelayYears` modelliert.

## Sparplan-Startkapital

Excel:

- In den Annahmen gibt es ETF-/Sparplanwerte wie `ETF_STARTKAPITAL`; einzelne Werte koennen Ergebnischarakter haben und muessen vor Uebernahme fachlich eingeordnet werden.

Domain:

- `SavingsPlanAssumptions::startingCapital` ist frei setzbar.
- Positive PV-Cashflows werden nur nach konfigurierter Reinvestitionsrate uebernommen.
- Negative PV-Cashflows werden nicht automatisch aus dem Sparplan gedeckt.

## Noch offene Abgleichpunkte

- Monatliche Cashflow-Reihen aus Excel gegen spaetere Monatsdomain vergleichen.
- Excel-IAB und Sonder-AfA gegen mehrjaehrige Domain-Fortschreibung pruefen.
- Weitere Rev-Share-Basisvarianten `net_revenue` und `net_margin` gegen Excel-nahe Szenarien abgleichen, falls Excel eine entsprechende fachliche Basis erkennen laesst.
- Rundung je Zahlungsstrom und Jahresaggregation gegen Excel dokumentieren.
- Sparplanwerte aus Excel nur nach Klaerung von Startkapital, Einzahlungen, Kapitalertragsteuer und Ergebnis-/Eingabecharakter uebernehmen.
