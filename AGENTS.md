# RoadRunner Admin — AI Agent Roles

## Overview

This document defines the AI agent roles used during development of the RoadRunner Admin platform. Each agent has a specific scope, responsibilities, and guardrails.

---

## Agent: Architect

**Scope:** System design, database schema, workflow patterns, API structure

**Responsibilities:**
- Define and maintain the database schema (tables, relationships, indexes)
- Design API contracts and endpoint structure (`api/*.php`)
- Establish workflow patterns (Service Request → Estimate → Work Order → Invoice)
- Maintain the document chain and data inheritance model
- Ensure prepared statements and security patterns are followed

**Guardrails:**
- Never modify UI/CSS styling decisions
- Always use the existing PDO connection from `config/database.php`
- Follow the established naming conventions (snake_case tables, camelCase JS)

---

## Agent: Frontend Engineer

**Scope:** Page UI, CSS styling, JavaScript interactivity, UX flows

**Responsibilities:**
- Build and maintain page files (`pages/*.php`) following the design system
- Implement scoped CSS with page prefixes (`.intake-*`, `.est-*`, `.comp-*`)
- Write client-side JavaScript for forms, modals, AJAX calls, and filtering
- Follow the dark command center aesthetic defined in `RULES.md`
- Use CSS variables — never hardcode colors

**Guardrails:**
- Every page must have a gradient header with navy border
- Use Bootstrap 5.1.3 for structure, RoadRunner CSS for styling
- Use DM Sans for UI text, JetBrains Mono for data values
- Navy = UI chrome only; signal colors = status only

---

## Agent: Backend Engineer

**Scope:** PHP logic, API endpoints, data processing, form handlers

**Responsibilities:**
- Implement API endpoints in `api/*.php`
- Handle form submissions and POST processing in page files
- Write SQL queries using prepared statements (no raw interpolation)
- Implement business logic (ticket numbering, cost calculation, status transitions)
- Sanitize all user input with `htmlspecialchars()` / `sanitize_input()`

**Guardrails:**
- All SQL must use PDO prepared statements with parameter binding
- Never expose database errors to the client in production
- Always validate required fields before INSERT/UPDATE
- Return consistent JSON: `{ success: bool, data?: any, error?: string }`

---

## Agent: QA / Reviewer

**Scope:** Code review, error checking, consistency verification

**Responsibilities:**
- Verify PHP syntax (`php -l`) after file creation or modification
- Check for SQL injection vulnerabilities
- Ensure XSS protection on all user-rendered output
- Validate that new pages follow the template pattern from `RULES.md`
- Confirm CSS scoping (no global style leaks)
- Test API endpoints return correct JSON responses

**Guardrails:**
- Do not introduce new dependencies without justification
- Flag any hardcoded colors, credentials, or magic numbers
- Verify all form fields match database column names and types

---

## Agent: DevOps / Environment

**Scope:** Server configuration, file deployment, database migrations

**Responsibilities:**
- Manage the PHP/MySQL/Apache stack on WSL Ubuntu
- Handle database table creation and schema migrations
- Verify file permissions and paths
- Manage the `.env` configuration file
- Run syntax checks and validation scripts

**Guardrails:**
- Never commit credentials (use `.env` file)
- App runs directly from workspace — no separate deploy step
- Database: MySQL on localhost, root/pass, database `roadside_assistance`
- PHP 8 minimum required

---

## Workflow Between Agents

```
User Request
    │
    ├── Architect → designs schema/API contract
    │       │
    ├── Backend Engineer → implements API + PHP logic
    │       │
    ├── Frontend Engineer → builds page UI + JS
    │       │
    └── QA / Reviewer → validates all changes
            │
        Deployed (live in workspace)
```

## Document Chain (Business Workflow)

```
Service Request (intake)
    → Estimate (diagnosis + line items)
        → Work Order (authorized work)
            → Invoice (billing)
                → Receipt (payment confirmation)
```

Each downstream document inherits customer, vehicle, and location data from its parent.
