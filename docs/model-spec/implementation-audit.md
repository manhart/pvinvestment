# Implementation-Audit Domain-Prototyp

Stand: aktueller Domain-Prototyp ohne GUI und ohne Datenbank.

## Gepruefte Dateien

- `classes/Calculators/TaxCalculator.php`
- `classes/Calculators/AnnualInvestorCashflowCalculator.php`
- `classes/Domain/TaxAssumptions.php`
- `classes/Domain/TaxCalculationResult.php`
- `classes/Domain/BatteryModel.php`
- `classes/Domain/RevenueSharingModel.php`
- `classes/Domain/SavingsPlanAssumptions.php`
- `classes/Domain/CalculationResult.php`
- `classes/Domain/Tax/TaxAsset.php`
- `classes/Domain/Tax/TaxAssetLedger.php`
- `classes/Domain/Tax/DepreciationSchedule.php`
- `classes/Domain/Tax/DepreciationYear.php`
- `classes/Domain/Time/YearMonth.php`
- `classes/Domain/Results/MonthResult.php`
- `classes/Domain/Results/YearResult.php`
- `classes/Calculators/DepreciationCalculator.php`
- `classes/Calculators/MonthlyScenarioCalculator.php`
- `classes/Calculators/YearlyAggregationCalculator.php`
- `tests/Unit/TaxCalculatorTest.php`
- `tests/Unit/AnnualInvestorCashflowCalculatorTest.php`

## Implementierte Parameter

- PV: `annualRevenue`, `annualOperatingCosts`.
- Batterie: Modell `none`, `full_ownership`, `profit_sharing`, `annualRevenue` als Batterie-Bruttoerloes, `annualOperatingCosts` als Batterie-Opex, `marketAccessFee`, `optimizerFee`, `batteryCapex`.
- Revenue Sharing: `investorRevenueShare`, `operatorRevenueShare`, `investorCostShare`, `operatorCostShare`, `sharingBase` mit `gross_revenue`, `net_revenue`, `net_margin`, `investorCapexShare`, `operatorCapexShare`.
- Finanzierung: `annualInterest`, `annualRepayment`.
- Steuer: `annualTaxPayment`, `calculationYear`, `incomeTaxRate`, `taxRateByYear`, `acquisitionCost`, `capitalizableAncillaryCosts`, `immediatelyDeductibleCosts`, `depreciationStartYear`, `depreciationStartMonth`, `depreciationMethod`, `linearDepreciationRate`, `decliningDepreciationRate`, `iabEnabled`, `iabAmount`, `iabDeductionYear`, `iabAdditionYear`, `specialDepreciationEnabled`, `specialDepreciationRate`, `specialDepreciationStartYear`, `specialDepreciationYears`, `taxPaymentDelayYears`, `taxPaymentDelayMonths`.
- Steuerliche Verlustnutzung: `lossHandlingStrategy`, `maxLossCarryBackAmount`, `maxLossCarryBackYears`, `manualUsableLossByYear`, `TaxLossLedger`.
- Steuerliches Anlageverzeichnis: `assetName`, `acquisitionCost`, `capitalizableAncillaryCosts`, `acquisitionDate`, `depreciationStart`, `usefulLifeMonths`, `depreciationMethod`, `decliningBalanceRate`, `switchToLinear`, `iabReductionAmount`, `specialDepreciationEnabled`, `specialDepreciationTotalRate`, `specialDepreciationDistributionByYear`, `residualBookValue`.
- Timing: `investmentYear/month`, `depreciationStartYear/month`, `eegCommissioningYear/month`, `gridConnectionYear/month`, `revenueStartYear/month`, `interestStartYear/month`, `repaymentStartYear/month`, `taxPaymentYear`, `savingsPlanContributionStartYear/month`, `annualSavingsPlanContributionMonth`.
- Sparplan: `startingCapital`, `monthlyContribution`, `annualContribution`, `positiveCashflowReinvestmentRate`.
- Monatsengine: `MonthResult` mit monatlichen PV-/Batterieerloesen, Kosten, Zins, Tilgung, AfA, Steuer-Cashflow, Investor-Cashflow, Sparplanbeitrag und Sparplan-Endwert.
- Jahresaggregation: `YearResult` wird aus `MonthResult`-Zeilen aggregiert.
- Szenario: `ScenarioCalculator` nutzt `MonthlyScenarioCalculator` und `YearlyAggregationCalculator` als Primaerpfad. `ScenarioResult` enthaelt Monatswerte und aggregierte Jahreswerte; `ScenarioComparison` summiert die Kennzahlen aus diesen Jahreswerten.

## Fehlende Parameter aus dem Parameterkatalog

- Szenario: Name, Gruppe, Betrachtungszeitraum, Waehrung, Inflation, Diskontierungszins.
- PV: Anlagenname, Standort, kWp, spezifischer Ertrag, Degradation, Investitionskosten als getrenntes PV-Wirtschaftsgut, Wartung, Versicherung, Pacht, Direktvermarktungskosten, EEG-Verguetung, Marktwert Solar, Strompreisannahme.
- Batterie: `custom`, Degradation, Ersatzjahr, Ersatzkosten, zeitliche Capex-Zahlung, Batterie-Capex-Fortschreibung.
- Finanzierung: Darlehensbetrag, Auszahlung, Zinssatz, Tilgungssatz, Annuitaet, Zinsbindung, Laufzeit, Sondertilgung, tilgungsfreie Monate, Finanzierungsnebenkosten.
- Steuer: Ruhestandssaetze als eigene Phase, IAB-Prozentsatz statt Betrag, steuerliche Rueckgaengigmachung des IAB, manueller Wechsel von degressiv zu linear mit Wechseljahr, asset-spezifische Zinsabzugs-Konfiguration, detaillierte gesetzliche Grenzen fuer Verlustvortrag/-ruecktrag.
- Sparplan: erwartete Rendite, Kostenquote, Kapitalertragsteuer, Teilfreistellung, Sparer-Pauschbetrag, Entnahmephase.
- Ergebnisse: Restschuldverlauf, Buchwerte ueber mehrere Jahre als Scenario-Ausgabe, NPV/IRR.

## Fachliche Bewertung

- Kaufnebenkosten werden aktuell getrennt in aktivierungspflichtige `capitalizableAncillaryCosts` und sofort abzugsfaehige `immediatelyDeductibleCosts` gefuehrt. Aktivierungspflichtige Nebenkosten erhoehen nur die AfA-Basis; sofort abzugsfaehige Kosten reduzieren nur das steuerliche Einkommen. Eine doppelte Verwertung ist im aktuellen Rechenpfad nicht vorhanden.
- IAB wird explizit ueber Abzug im `iabDeductionYear`, Hinzurechnung im `iabAdditionYear` und Minderung der Anschaffungskosten ab dem Hinzurechnungsjahr modelliert. Im Anlageverzeichnis wirkt die Minderung als `iabReductionAmount`. Es fehlt noch die steuerliche Rueckgaengigmachung bei ausbleibender Investition.
- Sonder-AfA ist im Anlageverzeichnis als getrennte Komponente mit Verteilung nach Kalenderjahr modelliert und darf den Restbuchwert nicht unter 0 senken.
- Normale AfA ist monatsgenau. Lineare AfA wird auf Restbuchwert und verbleibende Nutzungsdauer gerechnet. Degressive AfA wird ueber mehrere Jahre auf den Restbuchwert fortgeschrieben; automatischer Wechsel zu linear ist implementiert.
- Mehrere Wirtschaftsgueter sind ueber `TaxAssetLedger` moeglich, inklusive PV-Anlage, Batterie und Batterieersatzinvestition.
- Zinsen sind zwischen Cashflow und Steuer konsistent, weil beide aus `FinancingAssumptions` und `ProjectTimingAssumptions::interestMonthFactor()` abgeleitet werden.
- Profit-Sharing wird steuerlich und cashflowseitig aus derselben Investor-Perspektive berechnet. Die Investor-Erloese und Investor-Kosten aus `BatteryModel::annualAllocation()` gehen in beide Rechenwege ein.
- Profit-Sharing unterstuetzt explizit `gross_revenue`, `net_revenue` und `net_margin`. Bei `net_margin` werden Market Access Fee, Optimizer Fee und Batterie-Opex nicht nochmals als Kosten abgezogen.
- Negative Nettomargen werden proportional verteilt und nicht automatisch auf 0 gekappt.
- Batterie-Capex wird nach Capex-Shares als Investor-/Betreiberanteil ausgewiesen, aber im Jahresprototyp noch nicht automatisch als laufender Cashflow abgezogen.
- Sparplan-Startkapital ist frei setzbar. Rendite, Steuern und Kosten des Sparplans fehlen noch.
- Steuerzahlungsjahr und Steuerentstehungsjahr sind getrennt: `taxAmount` entsteht im Rechenjahr, die Monatsengine bucht den `taxCashflow` im berechneten Zahlungsmonat. Der Legacy-Jahresrechner weist `cashflowTaxPayment` nur aus, wenn `taxPaymentYear === calculationYear`.
- Steuerliche Verlustnutzung ist parametrisierbar ueber `immediate`, `carry_forward`, `carry_back`, `manual` und `none`. `TaxLossLedger` haelt Verlustvortraege und Vorjahresgewinn-Kapazitaeten fuer mehrjaehrige Rechnungen.
- Die Monatsengine bucht Steuerzahlungen oder Erstattungen im berechneten `taxCashflowYear/month`. Ohne Monatsversatz ist das Dezember des `taxPaymentYear`.
- `ScenarioCalculator` ist jetzt der Monatsengine-Pfad fuer `ScenarioComparison`. Der alte `AnnualInvestorCashflowCalculator` bleibt separat testbar, erzeugt aber keine ScenarioComparison-Kennzahlen mehr.

## Technische Bewertung

- Assumption- und Result-Klassen verwenden weiterhin Euro-`float`. Fuer explizite Cent-Arithmetik existiert `MoneyAmount`, Prozentwerte koennen mit `PercentageRate` erzeugt werden.
- `TaxCalculator` rundet `taxAmount` und `cashflowTaxPayment` auf Cent. `ScenarioCalculator` rundet aggregierte Ergebniskennzahlen auf Cent, damit Monatsaddition keine Float-Drift in ScenarioComparison erzeugt. Andere Zwischenwerte bleiben ungerundet. Tests nutzen bei proportionalen Monatswerten `assertEqualsWithDelta`.
- Vorzeichenkonvention: Erloese und positive Steuerzahlungen sind positive Eingabewerte; Kosten, Zinsen, Tilgung und Steuerzahlungen werden im Rechner subtrahiert. Negative `taxAmount` repraesentiert eine Steuererstattung. Diese Konvention ist brauchbar, aber noch nicht zentral dokumentiert oder typisiert.
- Validierung ist partiell vorhanden: negative Kosten/Erloese werden in einigen Assumptions verboten, Prozentanteile muessen zwischen 0 und 1 liegen, Revenue-/Cost-Shares muessen je 1 ergeben.
- `CalculationResult::toArray()` wandelt auch Jahres-/Monatswerte zu float. Fuer ein spaeteres API kann das sinnvoll sein, fachlich sind es aber Integer-Einheiten.

## Konkrete Audit-Antworten

1. Implementierte Parameter: siehe Abschnitt "Implementierte Parameter".
2. Fehlende Parameter: siehe Abschnitt "Fehlende Parameter aus dem Parameterkatalog".
3. Einheiten: siehe `rechenvertrag.md`.
4. Uneinheitliche Vorzeichen: nicht direkt widerspruechlich, aber negative Steuerwerte als Erstattung sind noch nicht als eigener Typ modelliert.
5. Floating Point: ja, Assumptions und viele Zwischenwerte bleiben `float`; `MoneyAmount` bietet centgenaue Arithmetik fuer finale Geldwerte.
6. Rundung: Steuerbetrag, cashflowwirksame Steuerzahlung und aggregierte Scenario-Kennzahlen werden auf Cent gerundet; fachliche Zwischenwerte bleiben ungerundet.
7. Steuerlogik: AfA, Sonder-AfA, Restbuchwerte und Verlustnutzung sind mehrjaehrig nachvollziehbar; IAB-Rueckgaengigmachung und gesetzliche Detailgrenzen fehlen noch.
8. Doppelte Kaufnebenkosten: im aktuellen Pfad verhindert durch getrennte Felder.
9. Zinsen: konsistent zwischen Steuer und Cashflow.
10. Profit-Sharing: gleiche Investor-Perspektive in Steuer und Cashflow.
11. Sharing-Basis: `gross_revenue`, `net_revenue` und `net_margin` sind explizit parametriert und getestet.
12. Sparplan: frei eingebbares Startkapital ist implementiert.
13. Steuerzahlungsjahr: getrennt vom Steuerentstehungsjahr.
14. Fehlende Edge-Case-Tests: siehe `test-gap-analyse.md`.
