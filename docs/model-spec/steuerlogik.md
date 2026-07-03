# Steuerlogik

Die Steuerlogik soll modular und parametergetrieben aufgebaut werden. Der Rechner ist ein Transparenz- und Planungswerkzeug, keine Steuerberatung.

## Grundsaetze

- Steuerliche Effekte werden nachvollziehbar je Jahr und, wo noetig, je Monat hergeleitet.
- Cashflow und Steuerberechnung verwenden konsistente Zinswerte.
- Kapitalisierbare Anschaffungs- und Anschaffungsnebenkosten erhoehen die AfA-Basis und werden nicht zusaetzlich sofort abgezogen.
- Sofort abzugsfaehige Kosten werden getrennt von aktivierungspflichtigen Kosten erfasst.
- Maklercourtage oder Vermittlungsprovision wird im Standard als aktivierungspflichtige Anschaffungsnebenkosten behandelt, wenn sie dem Erwerb des beguenstigten Wirtschaftsguts zuzuordnen ist.
- Aktivierungspflichtige Anschaffungsnebenkosten gehoeren im Standard auch zur IAB-relevanten Investitionsbasis.
- Steuerzahlungen und Steuererstattungen haben einen konfigurierbaren Zeitversatz.

## Parameter

- Einkommensteuersatz je Jahr oder Phase
- Steuersatz vor Ruhestand
- Steuersatz nach Ruhestand
- IAB aktiv/inaktiv
- IAB-Prozentsatz
- IAB-Basis aus beguenstigten Anschaffungs-/Herstellungskosten
- manuell abweichende IAB-beguenstigte Anschaffungskosten oder Nebenkosten
- IAB-Jahr
- Sonder-AfA aktiv/inaktiv
- Sonder-AfA-Prozentsatz
- Sonder-AfA-Verteilung
- lineare AfA
- degressive AfA
- automatischer oder manueller Wechsel von degressiver zu linearer AfA
- AfA-Start je Wirtschaftsgut
- Zinsabzug aktiv/inaktiv oder anteilig
- Verlustbehandlung: Sofortnutzung, Verlustvortrag, Verlustruecktrag, manuell
- Steuerzahlungs- und Erstattungsverzug
- Steuerzahlungsversatz in Jahren und Monaten
- Steuersatz je Jahr ueber `tax_rate_by_year`

## AfA-Konzept

Die AfA wird je Wirtschaftsgut separat modelliert:

- PV-Anlage
- Batteriespeicher
- Ersatzinvestitionen
- aktivierbare Nebenkosten
- sonstige abnutzbare Wirtschaftsgueter

Der Abschreibungsstart ist je Wirtschaftsgut konfigurierbar und darf nicht automatisch aus Kaufdatum, Netzanschluss oder Erloesstart abgeleitet werden.

## Implementierungsstand Anlageverzeichnis

Vor Aufgabe 10 berechnete der Domain-Prototyp die regulaere AfA direkt im `TaxCalculator` als Jahreswert aus AfA-Basis, Satz und Monatsfaktor. Dadurch gab es noch keine Fortschreibung des Restbuchwerts ueber mehrere Jahre und keine getrennten Wirtschaftsgueter.

Das steuerliche Anlageverzeichnis trennt diese Verantwortung:

- `TaxAsset` beschreibt ein einzelnes Wirtschaftsgut mit Anschaffungskosten, aktivierungspflichtigen Nebenkosten, IAB-Minderung, AfA-Start, Nutzungsdauer, AfA-Methode und Sonder-AfA-Verteilung.
- `DepreciationCalculator` erzeugt daraus einen mehrjaehrigen AfA-Plan.
- `TaxAssetLedger` fasst mehrere Wirtschaftsgueter je Steuerjahr zusammen.
- `TaxCalculator` bezieht regulaere AfA und Sonder-AfA aus diesem Modul.

## Implementierte AfA-Regeln

- AfA-Basis: `acquisition_cost + capitalizable_ancillary_costs - iab_reduction_amount`.
- Sofort abzugsfaehige Kosten sind kein Feld des `TaxAsset` und gehoeren nicht in die AfA-Basis.
- Lineare AfA wird aus Restbuchwert und verbleibender Nutzungsdauer berechnet.
- Degressive AfA wird aus dem Restbuchwert zu Jahresbeginn berechnet; im Startjahr nur fuer die AfA-Monate.
- Bei `switch_to_linear = auto` wird zur linearen AfA gewechselt, wenn diese im jeweiligen Jahr hoeher ist.
- Sonder-AfA wird separat ausgewiesen und nach `special_depreciation_distribution_by_year` verteilt.
- Sonder-AfA wird auf den verbleibenden Restbuchwert begrenzt.
- PV-Anlage, Batteriespeicher und spaetere Batterieersatzinvestitionen koennen als getrennte Assets im Ledger gefuehrt werden.

## IAB

Der Investitionsabzugsbetrag ist parametrisierbar:

- aktivierbar oder deaktivierbar
- frei konfigurierbarer Prozentsatz ueber `iabRate`
- expliziter Betrag ueber `iabAmount`, falls ein Betrag statt Prozentsatz vorgegeben werden soll
- eigenes Jahr der steuerlichen Wirkung
- IAB-Basis aus `iabEligibleAcquisitionCost + iabEligibleCapitalizableAncillaryCosts`
- Standard-IAB-Basis: `acquisitionCost + capitalizableAncillaryCosts`
- transparente Rueckgaengigmachung oder Verrechnung in spaeteren Jahren

Sofort abzugsfaehige Kosten gehoeren nicht zur IAB-Basis. Maklercourtage/Vermittlungsprovision wird ohne abweichende manuelle Vorgabe als `capitalizableAncillaryCosts` erfasst und ist damit AfA- und IAB-basisrelevant. Fuer Sonderfaelle kann `iabEligibleCapitalizableAncillaryCosts` niedriger angesetzt werden, zum Beispiel `0.0`, ohne die AfA-Basis zu veraendern.

## Sonder-AfA

Sonder-AfA wird getrennt von linearer oder degressiver AfA gefuehrt:

- aktivierbar oder deaktivierbar
- Prozentsatz und Verteilung konfigurierbar
- Ausweis der steuerlichen Wirkung separat von der regulaeren AfA

## Verlustbehandlung

Steuerliche Verluste werden nicht automatisch mit einem festen Effekt behandelt. `TaxLossLedger` und `TaxLossHandlingStrategy` unterstuetzen aktuell:

- `immediate`: negativer steuerlicher Ergebnisbetrag erzeugt sofort eine Steuererstattung gemaess Steuersatz und Zahlungsversatz.
- `carry_forward`: negativer steuerlicher Ergebnisbetrag wird als Verlustvortrag gespeichert. Positive Folgejahre werden zuerst mit dem Verlustvortrag verrechnet.
- `carry_back`: negativer steuerlicher Ergebnisbetrag kann gegen dokumentierte Vorjahresgewinne verrechnet werden, begrenzt durch `max_loss_carry_back_amount` und `max_loss_carry_back_years`.
- `manual`: pro Jahr kann ein nutzbarer Verlustbetrag vorgegeben werden.
- `none`: negative Ergebnisse erzeugen im Modell keine Steuererstattung und keinen Verlustvortrag.

Das Ergebnis je Steuerjahr weist aus:

- `taxable_result_before_loss`
- `loss_used`
- `loss_created`
- `loss_carried_forward`
- `taxable_result_after_loss`
- `tax_amount`
- `tax_cashflow_year`
- `tax_cashflow_month`

Die Logik bildet keine Steuerberatung ab. Sie ist ein parametrisierter Modellrahmen fuer Szenariovergleiche.

## Transparenz

Jede steuerliche Ergebniszeile soll spaeter erkennen lassen:

- Bemessungsgrundlage
- Abzugsbetrag
- Steuerwirkung
- Zahlungszeitpunkt
- zugrunde liegende Parameter
