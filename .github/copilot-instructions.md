# Copilot Instructions for RoadRunner Admin Project

## ⚡ Token-Efficient Workflow
1. **Read `.github/RULES.md` FIRST** — compact design rules (~150 lines, ~1K tokens)
2. **Only read `DESIGN.md` if you need** deep reference (module specs, full component library, navigation structure)
3. **Study `_working/services.php` and `pages/dashboard.php`** as canonical visual references

## Project Overview
RoadRunner Admin — PHP-based roadside assistance & mobile mechanic operations platform.

## Environment
- **Stack:** PHP 8 + MySQL + Bootstrap 5.1.3 + React 18 (CDN, catalog only)
- **Workspace:** `\\wsl.localhost\Ubuntu\var\www\html\claude_admin2`
- **URL:** `http://localhost/claude_admin2/`
- **DB:** MySQL — root/pass — database: roadside_assistance

## File Locations
| What | Where |
|------|-------|
| Design rules (compact) | `.github/RULES.md` |
| Design system (full) | `DESIGN.md` |
| Active dev files | `_working/` (index.php, services.php, style.css) |
| Page files | `pages/` |
| Config | `config/database.php` |
| Helpers | `includes/functions.php` |
| API endpoints | `api/` |
| Static assets | `assets/css/`, `assets/js/` |
| Reference designs | `_archive_reference/` |

## Core Design (details in RULES.md)
- **Dark command center aesthetic** — #0C0E12 background
- **Navy blue (#2B5EA7)** = UI chrome ONLY (buttons, links, nav)
- **Signal colors** = Status ONLY (amber=pending, green=success, red=urgent, blue=in-progress)
- **Fonts:** DM Sans (UI), JetBrains Mono (data values)
- **Every page:** gradient header + navy border, scoped CSS prefixes

## No Deploy Needed
App runs directly from workspace — edits are live immediately.

## Key Rules
- Use CSS variables, never hardcode colors
- Use Bootstrap classes for structure, RoadRunner CSS for styling
- Scope page styles with prefixes (.customers-*, .invoices-*)
- Prepared statements for all SQL queries
- XSS protection with htmlspecialchars()
- **Phone numbers:** Format as (xxx) xxx-xxxx — use `class="phone-masked"` on inputs, `format_phone()` for display
