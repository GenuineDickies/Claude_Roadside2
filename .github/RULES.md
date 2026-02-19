# RoadRunner Admin — Compact Design Rules
<!-- ~200 lines. Read THIS first. Only read DESIGN.md for deep reference. -->

## Stack
PHP 8 + MySQL/PDO | Bootstrap 5.1.3 | React 18 CDN (catalog only) | Font Awesome 6

## Fonts
- **UI text:** DM Sans (labels, headings, nav, body)
- **Data values:** JetBrains Mono (IDs, prices, dates, codes, phone numbers)

## Document Numbering — PREFIX-YYMMDD-SEQ-VER

All document IDs follow a **4-part canonical format**: `PREFIX-YYMMDD-SEQ-VER`

| Part | Width | Meaning |
|------|-------|---------|
| PREFIX | 2-3 chars | Document type: `RR` (ticket), `EST` (estimate), `WO` (work order), `CO` (change order), `INV` (invoice), `RCT` (receipt) |
| YYMMDD | 6 digits | Date created (2-digit year) |
| SEQ | 3 digits | Sequential number for that day, zero-padded |
| VER | 2 digits | Version of that specific document, starts at `01` |

**Examples:** `RR-260213-001-01`, `EST-260213-003-02`, `WO-260213-001-01`

**Rules:**
- New documents always start at version `-01`
- Editing creates a new version (old version archived as read-only snapshot)
- Version bumps increment VER only, SEQ stays the same: `RR-260213-001-01` → `RR-260213-001-02`
- Generator: `includes/id_helpers.php` — `generate_ticket_number()`, `generate_doc_id()`, `bump_ticket_number_version()`
- Display: `includes/functions.php` — `format_ticket_number()` normalizes legacy formats
- Versioned documents are **immutable once archived** — only the current version is editable

## Color Rules

**Navy = UI chrome ONLY** (buttons, links, nav, focus, headers). NEVER for status.
**Signal colors = Status ONLY** (badges, indicators). NEVER for buttons/nav.

```
Navy:    #2B5EA7 (primary action), #3B7DD8 (hover/focus), #5A9AE6 (light text/headers)
Amber:   #F59E0B — pending, en-route, payments due
Green:   #22C55E — completed, active, available, paid
Red:     #EF4444 — urgent, error, overdue, cancelled
Blue:    #3B82F6 — in-progress, dispatched, info
Purple:  #A855F7 — SMS, after-hours, premium
```

## Dark Theme Backgrounds
```
Page:     #0C0E12  (--bg-primary)
Chrome:   #12151B  (--bg-secondary)
Cards:    #181C24  (--bg-surface)
Hover:    #1E2330  (--bg-surface-hover)
```

## Borders
```
Subtle:  rgba(255,255,255,0.06)  — dividers
Medium:  rgba(255,255,255,0.10)  — card borders
Strong:  rgba(255,255,255,0.16)  — focus/emphasis
```

## Text Colors
```
Primary:   #E8ECF4  — headings, main content
Secondary: #8A92A6  — descriptions, subtitles
Tertiary:  #5C6478  — labels, timestamps
```

## Typography Scale
```
Page title:     24px, 700 weight, -0.5px spacing
Stat value:     32px, 700 weight, JetBrains Mono
Body:           13px, 400 weight
Table header:   11px, 600 weight, UPPERCASE, 0.5px spacing
Badge:          12px, 500 weight, UPPERCASE
Subtitle:       13px, secondary color
```

## Border Radius
`6px` small (buttons, inputs) | `10px` medium (cards) | `14px` large (modals)

## Page Template — EVERY page uses this pattern

```php
<?php /* DB queries, logic */ ?>

<style>
/* Scope styles: .pagename-header, .pagename-card, etc. */
.pagename-header {
    background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 24px 28px;
    margin: -1rem -1rem 2rem -1rem;
}
</style>

<!-- 1. Gradient Header (REQUIRED on every page) -->
<div class="pagename-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-icon" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1 style="font-size:24px;font-weight:700;color:var(--navy-300);letter-spacing:-0.5px;margin:0">Title</h1>
            <p style="font-size:13px;color:var(--text-secondary);margin:2px 0 0">Subtitle</p>
        </div>
    </div>
</div>

<!-- 2. Stat Cards (if applicable) -->
<!-- 3. Data Table or Content -->
<!-- 4. Modals (if needed) -->
```

## Stat Card Pattern
4px left border (navy/amber/green/blue). Hover: translateY(-2px), box-shadow.
```html
<div class="stat-card" style="background:var(--bg-surface);border:1px solid var(--border-medium);border-left:4px solid var(--navy-500);border-radius:10px;padding:20px">
    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-tertiary)">LABEL</div>
    <div style="font-size:32px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--text-primary)">1,234</div>
</div>
```

## Table Pattern
Uppercase 11px headers. Monospace first column (IDs). Hover rows.
```html
<table class="table table-hover">
    <thead>
        <tr><th>ID</th><th>NAME</th><th>STATUS</th></tr>
        <!-- th: bg:var(--bg-secondary), 11px, uppercase, 0.5px spacing -->
    </thead>
    <tbody>
        <tr>
            <td style="font-family:'JetBrains Mono';font-size:12px;color:var(--text-tertiary)">#001</td>
            <td>Name</td>
            <td><span class="badge bg-success">Active</span></td>
        </tr>
    </tbody>
</table>
```

## Badge Pattern
Glow background + text color + subtle border. NEVER solid fill.
```css
.badge.bg-success { background: rgba(34,197,94,0.12)!important; color:#22C55E; border:1px solid rgba(34,197,94,0.3); }
.badge.bg-warning { background: rgba(245,158,11,0.12)!important; color:#FBBF24; border:1px solid rgba(245,158,11,0.3); }
.badge.bg-danger  { background: rgba(239,68,68,0.12)!important; color:#EF4444; border:1px solid rgba(239,68,68,0.3); }
.badge.bg-info    { background: rgba(59,130,246,0.12)!important; color:#3B82F6; border:1px solid rgba(59,130,246,0.3); }
```

## Buttons
Primary: navy gradient. Secondary: surface bg + border. Danger: red (destructive only).
```css
.btn-primary { background: linear-gradient(135deg, #234B78, #2B5EA7)!important; border:none!important; }
.btn-primary:hover { background: linear-gradient(135deg, #2B5EA7, #3B7DD8)!important; transform:translateY(-1px); }
```

## Modal Pattern
Overlay: `rgba(0,0,0,0.75)` + `backdrop-filter:blur(4px)`. Surface: `var(--bg-surface)`. Border: `var(--border-medium)`. Radius: `14px`.

## File Locations

| What | Where |
|------|-------|
| Pages | `pages/*.php` |
| Router | `_working/index.php` |
| Catalog | `_working/services.php` |
| CSS | `assets/css/style.css` |
| Config | `config/database.php` |
| Helpers | `includes/functions.php` |
| API | `api/*.php` |

## URL
`http://localhost/claude_admin2/`

App runs directly from workspace — no separate deploy step needed.

## Database
Host: `localhost` | User: `root` | Pass: `pass` | DB: `roadside_assistance`

## Reference Files
- **Canonical pages:** `_working/services.php`, `pages/dashboard.php` — match these exactly
- **Deep reference:** `DESIGN.md` (full spec, read only when needed)
- **Original designs:** `_archive_reference/dash.jsx`, `_archive_reference/service-catalog.html`

## Hard Rules
1. NEVER hardcode colors — use CSS variables (`var(--navy-500)`)
2. NEVER use navy for status — use signal colors
3. NEVER use signal colors for buttons/nav — use navy
4. NEVER skip the gradient header — every page has one
5. ALWAYS scope page styles with prefix (`.customers-header`, `.invoices-table`)
6. ALWAYS use Bootstrap classes for structure (grid, cards, tables, forms)
7. ALWAYS use JetBrains Mono for data values (IDs, prices, dates)
8. ALWAYS use DM Sans for UI text (labels, headings, body)
9. ALWAYS use prepared statements for SQL queries
10. ALWAYS format phone numbers as `(xxx) xxx-xxxx` — use `class="phone-masked"` on inputs and `format_phone()` for display
10. NEVER dispatch on technician assignment — assigning a technician only sets `technician_id`. Dispatching is a separate explicit action that sets `status='dispatched'` and `dispatched_at`. A technician's status changes to `busy` only upon dispatch, not assignment.
11. ALWAYS prefer simple, obvious solutions over clever ones — straightforward code that's easy to read, debug, and modify. No abstractions without clear need.
12. ALWAYS keep files small (<200 lines) and organized — split large files into focused modules. One clear purpose per file.
13. ALWAYS use canonical document numbering `PREFIX-YYMMDD-SEQ-VER` — never store legacy formats. Use `generate_ticket_number()` / `generate_doc_id()` from `includes/id_helpers.php`.
14. NEVER modify archived document versions — editing creates a new version; past versions are immutable read-only snapshots stored in `service_ticket_versions`.
