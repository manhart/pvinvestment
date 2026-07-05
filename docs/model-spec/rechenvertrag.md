# Rechenvertrag Domain-Prototyp

Dieses Dokument beschreibt die aktuell implementierten Einheiten, Vorzeichen und Ergebnisregeln. Es ist der Vertrag fuer Tests und naechste Domain-Erweiterungen.

## Einheiten

| Feldgruppe | Felder | Einheit |
| --- | --- | --- |
| Geldwerte | `annualRevenue`, `annualOperatingCosts`, `annualInterest`, `annualRepayment`, `annualTaxPayment`, `acquisitionCost`, `capitalizableAncillaryCosts`, `immediatelyDeductibleCosts`, `iabAmount`, `iabEligibleAcquisitionCost`, `iabEligibleCapitalizableAncillaryCosts`, `batteryCapex`, `batteryReplacementCost`, Ergebnis-Geldwerte | Euro als Dezimalzahl |
| Prozent/Shares | `incomeTaxRate`, `taxRateByYear`, `iabRate`, AfA-Saetze, `decliningBalanceRate`, Sonder-AfA-Verteilung, `investorRevenueShare`, `operatorRevenueShare`, `investorCostShare`, `operatorCostShare`, `investorCapexShare`, `operatorCapexShare`, `investorReplacementCostShare`, `operatorReplacementCostShare`, `batteryDegradationRatePerYear`, `positiveCashflowReinvestmentRate` | Dezimalzahl zwischen 0 und 1 |
| Jahre | `calculationYear`, IAB-Jahre, AfA-Jahre, Timing-Jahre, `taxPaymentYear` | Kalenderjahr als Integer |
| Monate | Timing-Monate, `depreciationStartMonth`, `annualSavingsPlanContributionMonth`, `capexPaymentMonth`, `batteryReplacementMonth`, `taxPaymentDelayMonths`, `taxCashflowMonth`, `usefulLifeMonths`, `depreciationMonths` | Kalendermonat 1 bis 12 bzw. Monatsanzahl als Integer |
| Monatsfaktoren | `revenueMonthFactor`, `interestMonthFactor`, `repaymentMonthFactor`, `savingsPlanContributionMonthFactor` | Dezimalfaktor zwischen 0 und 1 |
| Monatszeilen | `MonthResult::year`, `MonthResult::month` | Kalenderjahr und Kalendermonat |

## Vorzeichen

- Eingabe-Erloese werden positiv erfasst.
- Eingabe-Kosten, Zinsen, Tilgung und Steuerzahlungen werden positiv erfasst und im Rechner subtrahiert.
- `taxAmount > 0` bedeutet Steuerzahlung.
- `taxAmount < 0` bedeutet Steuererstattung.
- `cashflowTaxPayment` ist der im aktuellen Cashflow-Jahr wirkende Steuerbetrag. Bei Zahlungsversatz kann er 0 sein, obwohl `taxAmount` ungleich 0 ist.
- `taxableResultBeforeLoss < 0` bedeutet steuerlicher Verlust vor Verlustnutzung.
- `lossUsed` ist ein positiv ausgewiesener steuerlich genutzter Verlustbetrag.
- `lossCreated` ist ein positiv ausgewiesener nicht sofort genutzter Verlustbetrag.
- `lossCarriedForward` ist der positive Stand des Verlustvortrags nach dem Steuerjahr.
- Sparplan-Einzahlungen sind positive Beitraege. Negative PV-Cashflows werden nicht automatisch aus dem Sparplan gedeckt.

## Geld und Rundung

- Bestehende Assumption- und Result-Klassen nehmen aus Kompatibilitaetsgruenden weiter Euro-`float` entgegen.
- `MoneyAmount` speichert Geld intern als Cent-Integer und ist der Einstiegspunkt fuer centgenaue Addition, Subtraktion und finale Rundung.
- `PercentageRate` macht Dezimalrate, Prozentwert und Basispunkte explizit.
- `TaxCalculator` rundet `taxAmount` und `cashflowTaxPayment` auf Cent.
- `ScenarioCalculator` rundet aggregierte Scenario-Kennzahlen am Ergebnisrand auf Cent. Die Monatswerte selbst bleiben ungerundet und werden erst fuer Jahres-/Scenario-Kennzahlen summiert.
- Tests mit Monatsfaktoren verwenden Deltas.
- Andere Zwischenwerte bleiben im Prototyp ungerundet, bis die mehrjaehrige Monatsrechnung feststeht.

## Steuervertrag

- `capitalizableAncillaryCosts` erhoehen nur die AfA-Basis.
- `immediatelyDeductibleCosts` werden nur im steuerlichen Einkommen abgezogen.
- Maklercourtage/Vermittlungsprovision wird im Standard als `capitalizableAncillaryCosts` modelliert, wenn sie dem Erwerb des beguenstigten Wirtschaftsguts zuzuordnen ist. Damit gehoert sie zur AfA-Basis und zur IAB-Basis.
- Die IAB-Basis ist standardmaessig `acquisitionCost + capitalizableAncillaryCosts`. Sofort abzugsfaehige Kosten gehoeren nicht dazu.
- Fuer abweichende steuerliche Wuerdigungen kann die IAB-Basis ueber `iabEligibleAcquisitionCost` und `iabEligibleCapitalizableAncillaryCosts` manuell enger gefasst werden, ohne die AfA-Basis zu veraendern.
- `iabDeduction` reduziert das steuerliche Einkommen im IAB-Abzugsjahr.
- `iabAddition` erhoeht das steuerliche Einkommen im Hinzurechnungsjahr.
- `iabAcquisitionCostReduction` mindert die AfA-Basis ab dem Hinzurechnungsjahr.
- Die AfA-Basis je Wirtschaftsgut ist `acquisitionCost + capitalizableAncillaryCosts - iabReductionAmount`.
- Normale AfA und Sonder-AfA werden aus `TaxAsset`, `TaxAssetLedger` und `DepreciationCalculator` abgeleitet.
- Der Legacy-Pfad im `TaxCalculator` erzeugt aus `TaxAssumptions` ein einzelnes PV-Asset, damit bestehende Jahresrechner weiter funktionieren.
- Lineare AfA wird monatsgenau auf Basis des Restbuchwerts und der verbleibenden Nutzungsdauer berechnet.
- Degressive AfA wird auf den Restbuchwert zu Jahresbeginn angewendet; im Startjahr anteilig nach AfA-Monaten.
- `switchToLinear = auto` wechselt zur linearen AfA, sobald diese auf die verbleibende Nutzungsdauer hoeher ist als die degressive AfA.
- Sonder-AfA ist eine getrennte Komponente pro Jahr. Die Verteilung erfolgt ueber Kalenderjahr-Raten und darf den Restbuchwert nicht unter 0 senken.
- `TaxAssetLedger` kann mehrere Assets zusammenfassen, zum Beispiel PV-Anlage, Batteriespeicher und spaetere Batterieersatzinvestitionen.
- Verlustnutzung ist ueber `lossHandlingStrategy` parametriert: `immediate`, `carry_forward`, `carry_back`, `manual`, `none`.
- `TaxLossLedger` haelt Verlustvortraege und Vorjahresgewinn-Kapazitaeten fuer mehrjaehrige Rechnungen.
- Der Standard bleibt `immediate`, damit bestehende Jahresrechner negative Ergebnisse weiterhin als sofort nutzbare Steuererstattung behandeln.
- `taxRateByYear` ueberschreibt den allgemeinen `incomeTaxRate` fuer einzelne Steuerjahre.
- Zinsen werden steuerlich aus derselben `FinancingAssumptions` und demselben Zins-Monatsfaktor abgeleitet wie im Cashflow.

## PV-Ertragsvertrag

- `PvAssumptions` enthaelt technische und preisliche PV-Annahmen: `installedCapacityKwp`, `specificYieldKwhPerKwp`, `pvDegradationRatePerYear`, `pvAvailabilityRate`, `pvCurtailmentRate`, `electricityPriceCentsPerKwh`, `electricityPriceEscalationRatePerYear`, `directMarketingCostCentsPerKwh`, `otherRevenueDeductionRate`.
- `PvRevenueCalculator` berechnet je Kalenderjahr:
  - `yearIndex = jahr - ertragsstartjahr`
  - `degradationFactor = (1 - pvDegradationRatePerYear) ^ max(0, yearIndex)`
  - `priceFactor = (1 + electricityPriceEscalationRatePerYear) ^ max(0, yearIndex)`
  - `annualProductionKwh = installedCapacityKwp * specificYieldKwhPerKwp * degradationFactor * pvAvailabilityRate * (1 - pvCurtailmentRate)`
  - `grossRevenue = annualProductionKwh * electricityPriceCentsPerKwh / 100 * priceFactor`
  - `directMarketingCosts = annualProductionKwh * directMarketingCostCentsPerKwh / 100`
  - `netRevenue = (grossRevenue - directMarketingCosts) * (1 - otherRevenueDeductionRate)`
- `manualPvAnnualRevenueOverride` ersetzt nur den berechneten PV-Nettoerloes. Produktionsmenge, Bruttoerloes, Direktvermarktungskosten, Degradationsfaktor und Preisfaktor bleiben sichtbar. `manualPvRevenueOverrideUsed` markiert Monats- und Jahresergebnisse mit aktivem Override.
- `annualRevenue` bleibt aus Kompatibilitaetsgruenden fuer bestehende Tests und Legacy-Pfade erhalten. Wenn keine technischen PV-Parameter gesetzt sind, behandelt `PvRevenueCalculator` diesen Wert als Legacy-Override.
- Monatswerte buchen PV-Produktion und PV-Erloese nur ab `revenueStartYear/month`. Das Startjahr wird dadurch automatisch anteilig, zum Beispiel November/Dezember mit 2/12, erfasst.

## Batterie- und Sharing-Vertrag

- `full_ownership`: Investor erhaelt 100 Prozent Batterieerloes und traegt 100 Prozent Batteriekosten.
- `profit_sharing`: Batterieerloese werden anhand der expliziten `sharingBase` verteilt. Die Factory-Methode `profitSharing()` laesst Cost- und Capex-Shares konfigurieren; ohne Angabe liegen laufende Batteriekosten und Batterie-Capex beim Investor.
- `gross_revenue`: Investor- und Betreibererloes werden aus dem Batterie-Bruttoerloes berechnet. Market Access Fee, Optimizer Fee und Batterie-Opex werden danach gemaess Cost-Shares als Kosten verteilt.
- `net_revenue`: Market Access Fee und Optimizer Fee werden vor dem Revenue Share abgezogen. Batterie-Opex wird danach gemaess Cost-Shares verteilt.
- `net_margin`: Market Access Fee, Optimizer Fee und Batterie-Opex werden vor dem Revenue Share abgezogen. Diese Kosten werden danach nicht nochmals als Investor- oder Betreiberkosten angesetzt.
- Negative `net_margin` wird nicht auf 0 gekappt. Der negative Anteil laeuft proportional in Investor- und Betreibererloes.
- Batterie-Capex wird separat als `investorBatteryCapex` und `operatorBatteryCapex` ausgewiesen. Die Monatsengine zieht den Investoranteil im Zahlungsmonat vom Investor-Cashflow ab.
- Wenn `capexPaymentYear/month` nicht gesetzt ist, wird Batterie-Capex im Investitionsmonat aus `ProjectTimingAssumptions` gebucht.
- Batterie-Degradation reduziert im Monatsengine-Pfad den Batterie-Bruttoerloes auf Jahresbasis. Das Ertragsstartjahr hat Faktor 1, Folgejahre verwenden `(1 - batteryDegradationRatePerYear) ^ jahre_seit_ertragsstartjahr`.
- `batteryRevenueBeforeDegradation` und `batteryRevenueAfterDegradation` werden in `MonthResult` und `YearResult` ausgewiesen.
- Batterie-Ersatzinvestitionen werden als `batteryReplacementCapexInvestor` im konfigurierten Ersatzmonat gebucht. Ohne eigenen `investorReplacementCostShare` gilt der Investor-Capex-Anteil aus dem Sharing-Modell.
- Steuer und Cashflow verwenden im Monatsengine-Pfad dieselbe degradierte Batterie-Allokation fuer das jeweilige Jahr. Batterie-Capex und Ersatz-Capex sind nicht sofort steuerlich abzugsfaehig; steuerliche Wirkung entsteht nur ueber ein modelliertes `TaxAsset`.

## Timing-Vertrag

- Investitionsdatum, AfA-Beginn, EEG-Inbetriebnahme, Netzanschluss, Ertragsbeginn, Zinsbeginn, Tilgungsbeginn, Steuerzahlungsjahr und Sparplan-Einzahlungsstart sind getrennte Felder.
- `YearMonth` ist das Value Object fuer Monatsvergleiche in der Monatsengine.
- `MonthlyScenarioCalculator` erzeugt Monatswerte; `YearlyAggregationCalculator` bildet Jahreswerte ausschliesslich als Summe dieser Monatswerte.
- `ScenarioCalculator` ist der Primaerpfad fuer Szenarien und ScenarioComparison. Er berechnet zuerst Monatswerte, aggregiert daraus Jahreswerte und summiert daraus:
  - kumulierten Investor-Cashflow vor Sparplan
  - kumulierten Steuer-Cashflow
  - kumulierte Batterieerloese Investor
  - Sparplan-Endwert
  - Gesamtergebnis Investor
  - Break-even-Jahr
- Der Monatsprototyp verwendet:
  - Ertragsbeginn fuer PV- und Batterieerloese sowie laufende PV-/Batteriekosten.
  - AfA-Beginn fuer normale AfA und Sonder-AfA.
  - Zinsbeginn fuer steuerliche und cashflowseitige Zinsen.
  - Tilgungsbeginn fuer cashflowseitige Tilgung.
  - Steuerzahlungsjahr fuer cashflowseitige Steuerwirkung.
  - Sparplan-Einzahlungsstart fuer monatliche Sparplanbeitraege.
- EEG-Inbetriebnahme und Netzanschluss werden aktuell nur mitgefuehrt, aber noch nicht als fachliche Sperre fuer Ertragsbeginn verwendet.
- Ohne `taxPaymentDelayMonths` wird `taxCashflow` in Monatsreihen im Dezember des `taxPaymentYear` gebucht. Bei gesetztem Monatsversatz wird der Cashflow-Monat aus Dezember plus `taxPaymentDelayMonths` abgeleitet.
- `ScenarioResult::annualResults` bleibt als Kompatibilitaetsname erhalten und enthaelt dieselben aggregierten `YearResult`-Werte wie `ScenarioResult::yearlyResults`. `ScenarioResult::monthlyResults` enthaelt die zugrunde liegenden `MonthResult`-Zeilen.
- `AnnualInvestorCashflowCalculator` bleibt separat testbar, ist aber kein Hauptpfad fuer `ScenarioCalculator`.

## Formularvertrag

- Das serverseitige Formular ist nicht persistent. Es erzeugt pro Request ein `ScenarioFormData`, validiert dieses mit `ScenarioFormValidator` und baut danach ueber `ScenarioFormMapper` ein `ScenarioInput`.
- Formular-Geldwerte werden als Euro-Dezimalzahl eingegeben und an die Domain weitergegeben.
- Formular-Prozentwerte werden als Prozentzahl `0..100` eingegeben und im Mapper in Domain-Dezimalraten `0..1` umgerechnet.
- Formular-Monate werden als Kalendermonat `1..12` eingegeben.
- Bei Profit-Sharing werden Betreiberanteile aktuell als `1 - investorShare` aus den Investor-Anteilen abgeleitet. Die Domain validiert weiterhin, dass Revenue-, Cost- und Capex-Shares jeweils 1 ergeben.
- Die Maklercourtage hat im Formular den Default `capitalize`: sie erhoeht `capitalizableAncillaryCosts` und, wenn `brokerageIabEligible = 1`, auch `iabEligibleCapitalizableAncillaryCosts`. Sie wird dann nicht zugleich sofort abgezogen.
- Bei Behandlung `immediate` wird Maklercourtage nur in `immediatelyDeductibleCosts` gemappt und nicht in die IAB-Basis aufgenommen.
- Bei Behandlung `ignore` wird Maklercourtage weder aktiviert noch sofort abgezogen.
- Das Formular mappt technische und preisliche PV-Annahmen produktiv. Der manuelle PV-Jahreserloes ist nur noch ein optionaler Experten-Override.
- Betriebskostensteigerung ist im Formular weiter ein validiertes Kontextfeld, aber noch nicht in eine Kostenzeitreihe ueberfuehrt.
