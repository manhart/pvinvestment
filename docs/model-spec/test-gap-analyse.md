# Test-Gap-Analyse

## Im Audit zusaetzlich abgesicherte Kanten

- Batterieanteil 0 Prozent und 100 Prozent.
- Profit-Sharing 65/35 auf `gross_revenue`.
- Profit-Sharing 65/35 auf `net_revenue`.
- Profit-Sharing 65/35 auf `net_margin`.
- Negative `net_margin` ohne Kappung auf 0.
- ScenarioComparison fuer Vollerwerb vs. 65/35 `gross_revenue`.
- ScenarioComparison fuer 65/35 `gross_revenue` vs. 65/35 `net_revenue`.
- ScenarioComparison fuer 65/35 `net_revenue` vs. 65/35 `net_margin`.
- Kaufnebenkosten vollstaendig aktivierungspflichtig.
- Kaufnebenkosten vollstaendig sofort abzugsfaehig.
- Gemischte Kaufnebenkosten.
- IAB aus / Sonder-AfA aus.
- IAB an / Sonder-AfA aus.
- IAB an / Sonder-AfA an.
- Steuererstattung mit Zahlungsversatz +1 Jahr.
- Verlust sofort nutzbar.
- Verlust nicht sofort nutzbar.
- Verlustvortrag gegen positives Folgejahr und ueber mehrere Jahre.
- Verlustruecktrag gegen Vorjahresgewinn.
- Manuell nutzbarer Verlust.
- Keine Verlustnutzung mit `none`.
- Szenariovergleich `immediate` gegen `carry_forward`.
- Sparplan mit Startkapital 0.
- Sparplan mit frei gesetztem Startkapital groesser 0.
- Zinsen mit abweichendem Zinsbeginn bleiben zwischen Steuer und Cashflow konsistent.
- Lineare AfA ueber volle Nutzungsdauer und mit Start im September.
- Degressive AfA mit Start im September, mehrjaehriger Fortschreibung und automatischem Wechsel zu linear.
- Sonder-AfA im ersten Jahr und verteilt ueber mehrere Jahre.
- Steuerliches Anlageverzeichnis fuer PV-Asset, Batterie-Asset und Batterieersatzinvestition.

## Bewusst dokumentierte TODOs statt Schnellimplementierung

- IAB-Rueckgaengigmachung bei ausbleibender Investition fehlt.
- Manueller Wechselzeitpunkt von degressiver zu linearer AfA ist noch nicht modelliert; `manual` verhindert aktuell nur den automatischen Wechsel.
- Gesetzliche Detailgrenzen fuer Verlustvortrag und Verlustruecktrag sind nicht hart kodiert; sie muessen parametriert werden.
- Vollstaendige centgenaue Modellierung aller Geld-Zwischenwerte fehlt; `MoneyAmount` deckt bisher gezielte Rundungs- und Prozentrechnungen ab.
- Monatliche Ergebnisreihen fehlen; der aktuelle Prototyp arbeitet mit Jahreswerten und Monatsfaktoren.

## Bereits vorhandene Testabdeckung

- Grundstruktur und Autoloading.
- Full-Ownership-Batterie.
- Profit-Sharing-Batterie mit Investor-/Operator-Erloesanteilen.
- Frei gesetztes Sparplan-Startkapital.
- Einfacher jaehrlicher Investor-Cashflow.
- IAB-Abzug, Hinzurechnung und AfA-Basisminderung.
- Sonder-AfA.
- Degressive AfA mit Monatsfaktor.
- Mehrjaehrige AfA-Fortschreibung mit Restbuchwerten.
- Automatischer Wechsel von degressiver zu linearer AfA.
- Profit-Sharing-Batterie in Steuer und Cashflow.
- Explizite Batterie-Sharing-Basen `gross_revenue`, `net_revenue` und `net_margin`.
- Getrennte Projektzeitpunkte und Monatsfaktoren.
