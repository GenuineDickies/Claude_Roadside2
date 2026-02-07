# Workspace Organization Guide

## Directory Structure

```
claude_admin2/
├── pages/              ✅ All page files (dashboard, customers, director, etc.)
├── config/             ✅ Database configuration
├── includes/           ✅ Helper functions
├── api/                ✅ API endpoints
├── assets/             ✅ Static assets (CSS, JS)
│   ├── css/
│   └── js/
├── docs/               📖 Production Director document tree
│   ├── 00-director/
│   ├── 10-product/
│   ├── 20-architecture/
│   ├── 30-delivery/
│   ├── 40-qa/
│   └── 50-ops/
├── _working/           🔧 Active development files
│   ├── index.php       (Main router)
│   ├── services.php    (Service catalog)
│   └── style.css       (Global styles)
├── _archive_reference/ 📦 Original design reference files
│   ├── dash.jsx
│   └── service-catalog.html
├── .github/            ⚙️ GitHub configuration & Copilot instructions
├── DESIGN.md           📖 Design system documentation
├── WORKSPACE.md        📋 This file
├── README.md           📝 Project overview
├── reset_db.php        🔄 Database reset script
└── test.php            🧪 Test script
```

## Development Workflow

App runs directly from workspace — edits are live immediately at `http://localhost/claude_admin2/`.

### Edit Files

- **Pages:** `pages/*.php`
- **Router:** `_working/index.php`
- **Service catalog:** `_working/services.php`
- **Global styles:** `assets/css/style.css`
- **API endpoints:** `api/*.php`
- **Config:** `config/database.php`
- **Helpers:** `includes/functions.php`

### Create New Pages

1. Create file in `pages/` directory
2. Follow gradient header pattern from `pages/dashboard.php`
3. Add route to `_working/index.php`
4. Add nav link in `_working/index.php`

## Production Environment

**URL:** `http://localhost/claude_admin2/`  
**Workspace:** `\\wsl.localhost\Ubuntu\var\www\html\claude_admin2`

**Database:**
- Host: localhost
- User: root
- Password: pass
- Database: roadside_assistance

## Reference

- **Design System:** See DESIGN.md
- **Compact Rules:** `.github/RULES.md`
- **Canonical References:** `_working/services.php` and `pages/dashboard.php`
- **Original Designs:** `_archive_reference/dash.jsx`, `_archive_reference/service-catalog.html`

---

**Last Updated:** February 7, 2026
