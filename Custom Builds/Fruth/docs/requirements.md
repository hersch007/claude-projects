# Fruth Sales System
## Requirements & Project Specification

**Project Name:** Fruth Sales System

**Client:** Fruth Custom Packaging

**Platform:** WordPress

**Architecture:** Modular Plugin System

**Status:** Active Development

---

# Project Overview

The Fruth Sales System is a custom-built, database-backed sales quoting application that replaces an existing Excel pricing calculator while preserving its proven business logic.

The system is designed to provide:

- Accurate pricing calculations
- Consistent quote generation
- Professional printed customer quotes
- Centralized pricing management
- Future CRM and ERP integration
- Expandability through modular plugins

The original Excel workbook is the **authoritative source** for all pricing calculations and business rules.

---

# Core Principles

## Accuracy Over Optimization

The pricing engine is mission critical.

The goal is **not** to simplify formulas.

The goal is to preserve existing pricing behavior while modernizing the implementation.

No pricing calculation should change unless explicitly approved.

---

# Current Plugin Architecture

## Quote Builder Core

Plugin:

```
sales-quote-system
```

Responsibilities:

- Pricing engine
- Product database
- Material database
- Pricing calculations
- Margin calculations
- Roll calculations
- Quote Builder interface
- Pricing Editor
- Administration
- Portal navigation
- Branding
- User access

---

## Quote Builder Print Module

Plugin:

```
quote-builder-print
```

Responsibilities:

- Customer information
- Printable quote
- Company branding
- Terms & Conditions
- Professional print layout

The Print Module depends on the Quote Builder Core.

---

# Current URL Structure

These URLs are the official navigation paths.

Portal / Home

```
/fruth/portal/
```

Quote Builder

```
/fruth/quotes/
```

Pricing Editor

```
/fruth/editor/
```

All URLs should be generated using:

```php
home_url('/fruth/portal/');
home_url('/fruth/quotes/');
home_url('/fruth/editor/');
```

Never hardcode a domain name.

---

# Current Shortcodes

Portal

```
[sqs_portal]
```

Quote Builder

```
[sqs_pricing_calculator]
```

Pricing Editor

```
[sqs_pricing_data_admin]
```

---

# Excel Workbook

The Excel workbook is the canonical source of truth.

Every pricing calculation in PHP should match the workbook.

Before modifying pricing logic:

- Understand the workbook
- Understand the business purpose
- Validate PHP against Excel

---

# Formula Validation

Create:

```
FORMULA-VALIDATION.md
```

The report should include:

- Worksheet
- Formula
- Cell location
- Business explanation
- PHP implementation
- File
- Function
- Validation status
- Notes

Validation Status:

- ✅ Matches
- ⚠ Modified
- ❌ Missing
- ❓ Unknown

---

# Regression Testing

Build automated tests that compare PHP output directly against the Excel workbook.

The tests should validate:

- Material pricing
- Roll calculations
- Quantity breaks
- Margin calculations
- Selling price
- Waste calculations
- Setup charges
- Freight
- Rounding
- Edge cases
- Large quantity runs
- Small quantity runs

Future code changes should never silently change pricing.

---

# Development Philosophy

Before changing code:

Understand the business problem.

Avoid rewriting working code.

Prefer small, incremental improvements.

Maintain backwards compatibility whenever possible.

Document every significant architectural decision.

---

# Existing Project Documentation

Claude should maintain:

```
PROJECT-SUMMARY.md
CHANGELOG.md
TODO.md
FORMULA-VALIDATION.md
ARCHITECTURE.md
```

---

# Code Standards

Prefer:

- Readable code
- Small functions
- Consistent naming
- Reusable helper functions
- Modular architecture
- WordPress best practices

Avoid:

- Hardcoded URLs
- Duplicate code
- Large procedural functions
- Hidden business logic

---

# Navigation Rules

Portal buttons:

```
/fruth/portal/
```

Quote Builder buttons:

```
/fruth/quotes/
```

Pricing Editor buttons:

```
/fruth/editor/
```

These paths must remain consistent throughout the project.

---

# Future Roadmap

## Phase 1

Stabilize Core Plugin

Stabilize Print Module

Improve documentation

Improve navigation

Regression testing

---

## Phase 2

Quote Number Generation

Saved Quotes

Quote History

Revision Tracking

Customer Database

Product Templates

PDF Generation

Email Quotes

Reporting Dashboard

Margin Analysis

---

## Phase 3

CRM Integration

HubSpot

Salesforce

Microsoft Dynamics

Zoho CRM

---

## Phase 4

ERP Integration

Order Management

Inventory

Production Scheduling

Shipping

Purchasing

Accounting

---

## Phase 5

AI Features

AI Pricing Assistant

Margin Advisor

Quote Review

Pricing Recommendations

Sales Assistant

Customer Insights

Conversation Assistant

---

# Change Management

Before changing pricing logic:

1. Compare against Excel.
2. Document the difference.
3. Explain the reason.
4. Recommend the change.
5. Wait for approval.

Do not modify business calculations without approval.

---

# Overall Goal

Transform the Fruth Sales System into a modern, modular sales platform while preserving the proven pricing methodology developed over years of business use.

Every enhancement should improve maintainability, usability, and extensibility without changing pricing accuracy.

The guiding principle for this project is:

> Preserve the business logic. Modernize the implementation.