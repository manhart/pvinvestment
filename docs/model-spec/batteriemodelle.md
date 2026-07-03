# Batteriemodelle

Der PV-Investitionsrechner muss mehrere Batteriemodelle abbilden. Die Modelle unterscheiden Eigentuemerstellung, Erloesverteilung, Kostenverteilung und Sharing-Basis.

## Modell: keine Batterie

`battery_model = none`

- keine Batterieinvestition
- keine Batterieerloese
- keine batteriebezogenen Betriebskosten
- PV-Berechnung bleibt unabhaengig nutzbar

## Modell: Volleigentuemer

`battery_model = full_ownership`

- Investor traegt Batterie-Capex und Batterie-Opex vollstaendig.
- Investor erhaelt Batterieerloese vollstaendig.
- Betreiberanteile sind 0 Prozent.
- Degradation und Ersatzinvestitionen wirken voll beim Investor.
- Die Sharing-Basis ist technisch `gross_revenue`, weil kein Betreiberanteil existiert.

## Modell: Gewinnbeteiligung

`battery_model = profit_sharing`

- Investor und Betreiber teilen Batterieergebnisse nach vereinbarten Anteilen.
- Die Sharing-Basis ist konfigurierbar:
  - `gross_revenue`
  - `net_revenue`
  - `net_margin`
- Optimizer Fee und Market Access Fee werden vor oder nach Sharing nur gemaess Parameterlogik beruecksichtigt.

## Sharing-Basen

Die Batterie-Oekonomie verwendet keine Excel-Zelllogik, sondern drei explizite Sharing-Basen. Alle Werte sind Jahreswerte in Euro. Negative Margen werden nicht stillschweigend auf 0 gekappt.

### `gross_revenue`

- `investor_battery_revenue = battery_gross_revenue * investor_revenue_share`
- `operator_battery_revenue = battery_gross_revenue * operator_revenue_share`
- `investor_battery_costs = (market_access_fee + optimizer_fee + battery_opex) * investor_cost_share`
- `operator_battery_costs = (market_access_fee + optimizer_fee + battery_opex) * operator_cost_share`

### `net_revenue`

- `battery_net_revenue = battery_gross_revenue - market_access_fee - optimizer_fee`
- `investor_battery_revenue = battery_net_revenue * investor_revenue_share`
- `operator_battery_revenue = battery_net_revenue * operator_revenue_share`
- `investor_battery_costs = battery_opex * investor_cost_share`
- `operator_battery_costs = battery_opex * operator_cost_share`

### `net_margin`

- `battery_net_margin = battery_gross_revenue - market_access_fee - optimizer_fee - battery_opex`
- `investor_battery_revenue = battery_net_margin * investor_revenue_share`
- `operator_battery_revenue = battery_net_margin * operator_revenue_share`
- `investor_battery_costs = 0`
- `operator_battery_costs = 0`
- Market Access Fee, Optimizer Fee und Batterie-Opex sind in der Nettomarge bereits enthalten und duerfen nicht nochmals abgezogen werden.

## Capex

Batterie-Capex bleibt separat von der laufenden Erloes- und Kostenverteilung:

- `investor_battery_capex = battery_capex * investor_capex_share`
- `operator_battery_capex = battery_capex * operator_capex_share`

Der aktuelle Jahresprototyp weist Batterie-Capex transparent aus, verbucht ihn aber noch nicht automatisch als Investitions-Cashflow. Der Zeitpunkt der Capex-Zahlung wird in der spaeteren Monatsrechnung festgelegt.

## Modell: Individueller Split

`battery_model = custom`

- Umsatzanteile und Kostenanteile koennen getrennt eingestellt werden.
- Investor- und Betreiberanteile muessen transparent ausgewiesen werden.
- Das Modell ist fuer Sonderfaelle gedacht, bei denen Eigentum, Betrieb und Vermarktung nicht identisch verteilt sind.

## Kernparameter

- `investor_battery_revenue_share`
- `operator_battery_revenue_share`
- `investor_battery_cost_share`
- `operator_battery_cost_share`
- `investor_battery_capex_share`
- `operator_battery_capex_share`
- `sharing_base`
- `battery_capex`
- `battery_opex`
- `battery_degradation_rate`
- `battery_replacement_year`
- `battery_replacement_cost`
- `optimizer_fee`
- `market_access_fee`

## Ergebnisdarstellung

Die spaetere Berechnung soll mindestens ausweisen:

- Batterie-Bruttoerloes
- Batterie-Nettoerloes
- Batterie-Nettomarge
- Investoranteil Erloese
- Betreiberanteil Erloese
- Investoranteil Kosten
- Betreiberanteil Kosten
- Ersatzinvestitionen
- steuerlich relevante Batteriewerte
