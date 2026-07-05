# Domain-Referenzwerte

Dieses Dokument trennt anonymisierte Excel-Beispielwerte von fachlich korrigierten Domain-Erwartungswerten. Die maschinenlesbare Quelle ist `tests/fixtures/domain_expected_values.json`.

## Abweichungsgruende

- `keine_doppelte_kaufnebenkostenverwertung`: Aktivierungspflichtige Kaufnebenkosten erhoehen nur die AfA-Basis und werden nicht zugleich sofort steuerlich verwertet.
- `konsistente_zinsbehandlung`: Zinsen stammen in Steuer und Cashflow aus derselben Finanzierungsannahme und demselben Timing.
- `separater_afa_beginn_und_ertragsbeginn`: AfA-Beginn, EEG-Inbetriebnahme, Netzanschluss und Ertragsbeginn sind getrennte Zeitpunkte.
- `profit_sharing_sharing_base_explizit`: Batterie-Profit-Sharing verwendet eine explizite Basis wie `gross_revenue`, `net_revenue` oder `net_margin`.
- `steuererstattungs_timing_parametrisierbar`: Steuerentstehung und Steuer-Cashflow sind getrennt; Zahlungsversatz ist parametrisierbar.
- `sparplan_startkapital_frei_eingebbar`: Sparplan-Startkapital ist eine freie Eingabe und wird nicht ungeprueft aus Excel-Ergebniszellen uebernommen.

## PV-Batterie Mid Case

Fixture: `tests/fixtures/scenarios/pv_battery_mid_case.json`

| Kennzahl | Anonymisierter Excel-Vergleich | Domain | Abweichung |
| --- | ---: | ---: | ---: |
| Cashflow nach Schuldendienst und Steuer / Total Investor Result | 64000.00 EUR | 55165.00 EUR | -8835.00 EUR |

Begruendung: `keine_doppelte_kaufnebenkostenverwertung`, `konsistente_zinsbehandlung`, `separater_afa_beginn_und_ertragsbeginn`, `steuererstattungs_timing_parametrisierbar`, `sparplan_startkapital_frei_eingebbar`.

## Batterie Vollerwerb

Fixture: `tests/fixtures/scenarios/battery_full_ownership.json`

| Kennzahl | Anonymisierter Excel-Vergleich | Domain | Abweichung |
| --- | ---: | ---: | ---: |
| Cashflow nach Schuldendienst und Steuer / Total Investor Result | 42000.00 EUR | 38500.00 EUR | -3500.00 EUR |

Begruendung: `profit_sharing_sharing_base_explizit`, `konsistente_zinsbehandlung`, `steuererstattungs_timing_parametrisierbar`.

## Batterie Profit-Sharing 65/35

Fixture: `tests/fixtures/scenarios/battery_profit_sharing_65_35.json`

| Kennzahl | Anonymisierter Excel-Vergleich | Domain | Abweichung |
| --- | ---: | ---: | ---: |
| Cashflow nach Schuldendienst und Steuer / Total Investor Result | 30000.00 EUR | 13965.83 EUR | -16034.17 EUR |

Begruendung: `profit_sharing_sharing_base_explizit`, `konsistente_zinsbehandlung`, `separater_afa_beginn_und_ertragsbeginn`, `steuererstattungs_timing_parametrisierbar`.

## Einordnung

Die Domain-Werte sind reproduzierbare Erwartungswerte fuer den aktuellen Domain-Prototyp. Sie enthalten keine echten Angebots-, Projekt- oder Investorendaten.

Seit Aufgabe 14 werden diese Werte ueber den `ScenarioCalculator` aus Monatswerten berechnet und dann ueber `YearlyAggregationCalculator` zu Jahres- und Scenario-Kennzahlen verdichtet. Die anonymisierten Erwartungswerte mussten bei der Umstellung nicht angepasst werden.

Seit Aufgabe 15 unterstuetzt die Monatsengine Batterie-Capex, Batterie-Degradation und Batterie-Ersatzinvestitionen. Die oeffentlichen Referenzszenarien enthalten dafuer keine nicht-null Werte; deshalb mussten die Domain-Referenzwerte nicht angepasst werden.

Seit Aufgabe 19 berechnet der Standardpfad PV-Erloese aus Produktion, Strompreis und Direktvermarktungskosten. Die bestehenden oeffentlichen Referenzfixtures verwenden weiter den anonymisierten Legacy-Jahreserloes; dieser bleibt als Kompatibilitaets-Override erhalten. Deshalb mussten die Domain-Referenzwerte fuer diese Fixtures nicht angepasst werden.
