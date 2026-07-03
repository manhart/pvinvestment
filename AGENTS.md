# AGENTS.md — pvinvestment

## Project

This is the PV investment calculator application.

Application folder:
- /virtualweb/manhart/pvinvestment

Application title:
- PV-Investitionsrechner

The application is built for the existing POOL PHP framework in the manhart repository.

## Core goal

Build an investor-focused calculator for photovoltaic ground-mounted systems, batteries, financing, taxes, and reinvestment/savings plans.

The calculator must be more flexible and more accurate than the provided Excel files.

The Excel files are reference material and examples. They are not the source of truth.

## Important rule

Do not blindly reproduce Excel errors.

Known issues to avoid:
- Do not double-count purchase ancillary costs.
- Capitalizable acquisition costs must not also be deducted immediately as expenses.
- Interest treatment must be consistent between cashflow and tax calculation.
- Revenue start, EEG commissioning, grid connection, depreciation start, loan start, and repayment start must be independently configurable.
- Tax refunds must not automatically be assumed to arrive in the same year unless configured.

## Battery models

The calculator must support at least:

1. No battery
2. Battery full ownership
3. Battery profit-sharing
4. Custom investor/operator split

Important parameters:
- battery_model: none | full_ownership | profit_sharing | custom
- investor_battery_revenue_share
- operator_battery_revenue_share
- investor_battery_cost_share
- operator_battery_cost_share
- sharing_base: gross_revenue | net_revenue | net_margin
- battery_capex
- battery_opex
- battery_degradation_rate
- battery_replacement_year
- battery_replacement_cost
- optimizer_fee
- market_access_fee

## Calculation model

Do not build an Excel-cell clone.

Build a domain model with clear services/value objects:
- PV production
- Battery revenue
- Revenue sharing
- Financing
- Tax
- Cashflow
- Savings plan / reinvestment
- Scenario comparison

Business logic belongs in domain/service classes, not in GUI modules or templates.

## Time granularity

The internal calculation should be monthly where timing matters.

Annual values should be aggregated from monthly values.

This is required for:
- depreciation start
- revenue start
- grid connection
- interest start
- repayment start
- tax refund/payment timing
- savings plan deposits

## Tax model

The tax logic must be modular and parameter-driven.

Support at least:
- income tax rate by year or phase
- tax rate before/after retirement
- IAB enabled/disabled
- IAB percentage and year
- Sonder-AfA enabled/disabled
- Sonder-AfA percentage and distribution
- linear depreciation
- declining-balance depreciation
- switch from declining-balance to linear depreciation
- asset-specific depreciation start
- capitalizable costs separated from immediately deductible costs
- interest deductibility configuration
- tax loss handling: immediate use | carry-forward | carry-back | manual
- tax payment/refund delay

The application is a calculator, not tax advice. Results must be transparent and auditable.

## Savings plan

The savings plan must be independently configurable.

Support at least:
- freely entered starting capital
- monthly contribution
- annual contribution
- contribution from positive PV cashflow
- reinvestment rate from free cashflow
- expected return p.a.
- cost ratio
- capital gains tax
- partial exemption if applicable
- saver allowance if applicable
- withdrawal phase if applicable

## Excel references

Reference Excel files belong in:
- docs/reference-excel/

Codex may create tools to extract:
- sheet names
- hidden sheets
- named ranges
- cell values
- formulas
- input assumptions
- annual result series
- known inconsistencies

Generated analysis belongs in:
- docs/model-spec/excel-inventory.md
- docs/model-spec/excel-abweichungen.md
- tests/fixtures/

Do not use Excel as the production calculation engine.

## Testing

Every calculation module needs tests.

Required tests:
- full ownership battery model
- profit-sharing battery model
- configurable investor/operator revenue split
- start capital and savings plan
- purchase ancillary costs without double-counting
- consistent interest treatment
- IAB on/off
- Sonder-AfA on/off
- linear depreciation
- declining-balance depreciation
- tax payment/refund delay

Golden-master tests against Excel examples are allowed, but deviations from Excel are acceptable when documented and fachlich justified.

## Work area

During initial development:
- Read pool/, classes/, pofolio/, and realestateinvestment/ as references.
- Write only inside /virtualweb/manhart/pvinvestment unless explicitly instructed otherwise.
- Do not modify pool/ without explicit permission.
- Do not modify existing applications without explicit permission.

## Git / Repository

This folder is its own Git repository.

Remote:
- git@github.com:manhart/pvinvestment.git

Do not commit or push proprietary Excel source files, investor-specific data, credentials, vendor/, cache files, SQL dumps, or local environment files.

The public repository should contain:
- application code
- tests
- general documentation
- anonymized fixtures
- model specifications

Original Excel reference files belong in docs/reference-excel/ locally, but are ignored by Git unless the user explicitly approves publishing them.

Before committing:
- run PHP syntax checks
- run the PHPUnit suite
- inspect git status