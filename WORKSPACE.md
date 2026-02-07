# Workspace Organization Guide

## Directory Structure (After Cleanup)

```
claude_admin2/
├── pages/              ✅ All page files (dashboard, customers, etc.)
├── config/             ✅ Database configuration
├── includes/           ✅ Helper functions
├── api/                ✅ API endpoints (assign_technician, update_status)
├── assets/             ✅ Static assets (CSS, JS, fonts, images)
│   ├── css/
│   └── js/
├── _working/           🔧 Active development files
│   ├── index.php       (Main router - deploy when modified)
│   ├── services.php    (Service catalog - deploy when modified)
│   └── style.css       (Global styles - deploy when modified)
├── _archive_reference/ 📦 Original design reference files
│   ├── dash.jsx        (User's original React design)
│   └── service-catalog.html (Standalone catalog implementation)
├── .github/            ⚙️ GitHub configuration & Copilot instructions
├── DESIGN.md           📖 Design system documentation (v3.0)
├── WORKSPACE.md        📋 This file
├── README.md           📝 Project notes
├── reset_db.php        🔄 Database reset script
├── test.php            🧪 Test script
└── cleanup-redundant-files.ps1  🧹 Cleanup script (can be deleted)
```

## What Was Removed

### ❌ Deleted (Redundant)
- **B3/** directory - Complete duplicate of the entire application structure
- **Dashboard.php, customers.php, etc.** - Root-level duplicates (kept in pages/ only)

### 📦 Archived (Reference)
- **dash.jsx** - Original React service catalog design from user
- **service-catalog.html** - Standalone implementation (replaced by services.php)

### 🔧 Moved to _working/
- **index.php** - Main application router
- **services.php** - Integrated service catalog (React embedded in PHP)
- **style.css** - RoadRunner CSS overrides for Bootstrap

## Development Workflow

### 1. Edit Files in Place

**For existing pages:**
```bash
# Edit directly in pages/ directory
code pages/dashboard.php
code pages/customers.php
```

**For core files:**
```bash
# Edit in _working/ directory
code _working/index.php
code _working/services.php
code _working/style.css
```

**For global styles:**
```bash
# Edit in assets/
code assets/css/style.css
```

### 2. Deploy to Production

**PowerShell commands:**

```powershell
# Deploy main router
Copy-Item '_working\index.php' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\index.php' -Force

# Deploy service catalog
Copy-Item '_working\services.php' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\services.php' -Force

# Deploy global styles
Copy-Item 'assets\css\style.css' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\assets\css\style.css' -Force

# Deploy individual page
Copy-Item 'pages\dashboard.php' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\pages\dashboard.php' -Force

# Deploy DESIGN.md
Copy-Item 'DESIGN.md' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\DESIGN.md' -Force
```

**Quick Deploy Script:**
```powershell
# Deploy all modified files at once
$files = @(
    @{src='_working\index.php'; dest='index.php'},
    @{src='_working\services.php'; dest='services.php'},
    @{src='assets\css\style.css'; dest='assets\css\style.css'},
    @{src='pages\dashboard.php'; dest='pages\dashboard.php'}
)

foreach ($file in $files) {
    Copy-Item $file.src -Destination "\\wsl.localhost\Ubuntu\var\www\html\public\B3\$($file.dest)" -Force
    Write-Host "Deployed: $($file.dest)" -ForegroundColor Green
}
```

### 3. Create New Pages

**Follow the pattern in DESIGN.md:**

1. Create file in `pages/` directory
2. Copy gradient header pattern from `dashboard.php`
3. Use Bootstrap classes with RoadRunner CSS overrides
4. Add page to router in `_working/index.php`
5. Deploy both files to production

**Example:**
```php
<?php
// pages/new-page.php
?>

<style>
/* Component-scoped styles */
.newpage-header { /* gradient header pattern */ }
/* Copy patterns from dashboard.php */
</style>

<!-- Gradient Header -->
<div class="newpage-header">
    <div class="header-content">
        <i class="fas fa-icon header-icon"></i>
        <div class="header-title-group">
            <h1>Page Title</h1>
            <p class="header-subtitle">Description</p>
        </div>
    </div>
</div>

<!-- Page Content -->
<!-- Use Bootstrap grid + RoadRunner CSS variables -->
```

## Production Environment

**Location:** `/var/www/html/public/B3/`  
**URL:** `http://localhost/B3/`  
**Apache DocumentRoot:** `/var/www/html/public/`

**Database:**
- Host: localhost
- User: root
- Password: pass
- Database: roadside_assistance

## Important Files

| File | Purpose | Deploy After Changes |
|------|---------|---------------------|
| `_working/index.php` | Main router, navigation, session | ✅ Yes |
| `_working/services.php` | Service catalog (React) | ✅ Yes |
| `_working/style.css` | RoadRunner CSS overrides | ✅ Yes (to assets/css/) |
| `pages/*.php` | Individual page files | ✅ Yes |
| `config/database.php` | Database connection | ✅ Yes |
| `DESIGN.md` | Design documentation | ✅ Yes |
| `assets/css/style.css` | Global styles | ✅ Yes |

## Quick Commands

**Start XAMPP & Open Browser:**
```powershell
Start-Process 'C:\xampp\xampp-control.exe'
Start-Process 'http://localhost/B3/'
Start-Process 'http://localhost/phpmyadmin/'
```

**Deploy Everything:**
```powershell
# Deploy all core files
Copy-Item '_working\*' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\' -Force
Copy-Item 'pages\*' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\pages\' -Force -Recurse
Copy-Item 'assets\*' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\assets\' -Force -Recurse
Copy-Item 'config\*' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\config\' -Force
Copy-Item 'DESIGN.md' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\DESIGN.md' -Force
```

## Next Steps

1. **Transform remaining pages** to match service catalog aesthetic:
   - pages/service-requests.php
   - pages/customers.php
   - pages/technicians.php
   - pages/invoices.php
   - pages/login.php

2. **Backend integration:**
   - Connect services.php to MySQL
   - Create database tables for services/labor_rates
   - Implement CRUD API endpoints

3. **Additional features:**
   - Dispatch board
   - Live map
   - Messaging system
   - Payment processing

## Reference

- **Design System:** See DESIGN.md (v3.0)
- **Canonical Reference:** `_working/services.php` and `pages/dashboard.php`
- **Original Design:** `_archive_reference/dash.jsx`, `_archive_reference/service-catalog.html`

---

**Last Updated:** February 5, 2026  
**Workspace Version:** 1.0 (Post-Cleanup)
