# RoadRunner Admin — Development Instructions

## Quick Start

1. **Environment:** PHP 8 + MySQL on WSL Ubuntu. App is live at `http://localhost/claude_admin2/`
2. **Read RULES.md first** (`.github/RULES.md`) — compact design rules, ~150 lines
3. **Study reference pages:** `_working/services.php` and `pages/dashboard.php` for canonical styling
4. **No deploy step needed** — edits to files in the workspace are immediately live

---

## Project Structure

```
claude_admin2/
├── .github/RULES.md        # Design rules (READ FIRST)
├── DESIGN.md                # Full design spec (deep reference)
├── AGENTS.md                # AI agent role definitions
├── config/database.php      # PDO connection + table bootstrap
├── includes/functions.php   # Shared helpers (badges, formatting)
├── _working/
│   ├── index.php            # Main router (loads pages into layout)
│   ├── services.php         # Service catalog (React component)
│   └── style.css            # Global styles + CSS variables
├── pages/                   # Individual page files
│   ├── dashboard.php
│   ├── service-intake.php   # Full intake form (47 fields)
│   ├── service-requests.php # Ticket list + view + assign
│   ├── estimates.php        # Estimate builder
│   ├── work-orders.php      # Work order management
│   ├── invoices.php         # Invoice generation
│   ├── customers.php
│   ├── technicians.php
│   └── ...
├── api/                     # JSON API endpoints
│   ├── service-tickets.php
│   ├── workflow.php
│   └── ...
└── assets/
    ├── css/style.css
    └── js/app.js
```

---

## Database

- **Host:** localhost | **User:** (from `.env`) | **Pass:** (from `.env`) | **DB:** roadside_assistance
- Connection is established in `config/database.php` — all pages receive `$pdo` via the router
- Tables auto-create on first load via `CREATE TABLE IF NOT EXISTS`
- Always use **prepared statements** with parameter binding:

```php
// CORRECT
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);

// WRONG — never do this
$pdo->query("SELECT * FROM customers WHERE id = $id");
```

---

## Creating a New Page

1. Create `pages/your-page.php`
2. Add navigation entry in `_working/index.php` (sidebar nav array)
3. Follow the page template:

```php
<?php
// Database queries and logic here
?>

<style>
/* Scoped styles with page prefix */
.yourpage-header { ... }
</style>

<!-- Gradient header (REQUIRED) -->
<div class="yourpage-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-icon" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Page Title</h1>
            <p class="subtitle">Description</p>
        </div>
    </div>
</div>

<!-- Content -->
```

4. Verify with `php -l pages/your-page.php`

---

## Creating an API Endpoint

1. Create `api/your-endpoint.php`
2. Always return JSON with consistent structure:

```php
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    // Your logic here
    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

---

## Design Rules (Summary)

| Rule | Detail |
|------|--------|
| Background | `#0C0E12` dark command center |
| Navy (`#2B5EA7`) | UI chrome ONLY — buttons, links, nav, headers |
| Signal colors | Status ONLY — amber=pending, green=success, red=urgent, blue=in-progress |
| Fonts | DM Sans (UI text), JetBrains Mono (data values) |
| Scoping | CSS class prefixes per page (`.intake-*`, `.est-*`) |
| Variables | Always use `var(--navy-500)`, never hardcode `#2B5EA7` |
| Headers | Every page has a gradient header with navy bottom border |

---

## Business Workflow

```
Customer calls in
    → Service Request (intake form, ticket created)
        → Estimate (technician diagnoses, proposes cost)
            → Work Order (customer approves, work authorized)
                → Invoice (work complete, billing generated)
                    → Receipt (payment confirmed)
```

**Data inheritance:** Each document in the chain inherits customer, vehicle, and location info from the parent service request. Line items flow from estimate → work order → invoice.

---

## Key Conventions

### PHP
- `sanitize_input()` for user text going into the database
- `htmlspecialchars()` for displaying user content in HTML
- Helper functions in `includes/functions.php` (badges, formatting, currency)
- Session-based auth (login via `?page=login`)

### JavaScript
- Vanilla JS (no frameworks except React for service catalog)
- `fetch()` for API calls, `FormData` for POST requests
- Bootstrap 5 modals via `new bootstrap.Modal()`
- Debounce search inputs (300ms typical)

### CSS
- Bootstrap 5.1.3 for layout (grid, cards, tables, forms)
- Custom dark theme via CSS variables in `_working/style.css`
- Page-specific styles scoped with prefixes inside `<style>` tags
- Badge pattern: glow background + text color + border (never solid fill)

---

## Testing

- **Syntax check:** `php -l pages/your-page.php`
- **Live test:** Open `http://localhost/claude_admin2/?page=your-page` in browser
- **API test:** `curl http://localhost/claude_admin2/api/your-endpoint.php?action=list`
- **Database:** Connect via `mysql -u <DB_USER> -p <DB_NAME>` (credentials from `.env`)

---

## Login

The default admin account is created on first launch. The generated password is written once to the PHP error log — check your server logs after the first page load and change the password immediately.

---

## Common Pitfalls

1. **Forgetting the gradient header** — every page needs one
2. **Using navy for status badges** — navy is for UI chrome only
3. **Hardcoding colors** — always use CSS variables
4. **Missing prepared statements** — never interpolate user input into SQL
5. **Global CSS pollution** — always scope with page prefix
6. **Skipping php -l** — always syntax-check after editing PHP files
