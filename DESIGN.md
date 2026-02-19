# RoadRunner Admin — Design System & Implementation Guide

## 1. Project Overview

**Application:** RoadRunner Admin — a PHP-based web application for managing roadside assistance and mobile mechanic operations.

**Business Context:** The operator runs a roadside assistance and mobile mechanic service that dispatches technicians for flat tires, jump starts, towing, lockouts, mobile diagnostics, oil changes, brake jobs, and general automotive repair. The business communicates with customers via SMS (10DLC compliant), generates estimates and invoices, and manages a fleet of service vehicles and technicians.

### Tech Stack
| Layer | Technology |
|-------|------------|
| Backend | PHP 8+ with MySQL via PDO |
| Frontend Framework | Bootstrap 5.1.3 (structure) + Custom RoadRunner CSS (styling) |
| Enhanced Components | React 18 (CDN) for interactive features (service catalog) |
| Fonts | Google Fonts: DM Sans (UI) + JetBrains Mono (data/codes) |
| Icons | Font Awesome 6 |
| Database | MySQL 8+ |

### Development Environment

**Workspace:** `\\wsl.localhost\Ubuntu\var\www\html\claude_admin2`  
**URL:** `http://localhost/claude_admin2/`

App runs directly from workspace — no separate deploy step needed.

**Key Directories:**
- `_working/` — Active development files (index.php, services.php, style.css)
- `pages/` — All application page files
- `assets/` — CSS, JS, and static assets
- `_archive_reference/` — Original design references (dash.jsx, service-catalog.html)

**Workflow:** Edit files in workspace → Deploy to production using PowerShell copy commands

### Design Reference - THE GOLD STANDARD

**⚠️ CRITICAL: The service catalog (`services.php`) is the canonical reference implementation.**

Every new page MUST match this visual design:
- Gradient header with navy bottom border
- Modern stat/info cards with left-colored borders
- Clean data tables with uppercase headers
- Proper typography hierarchy (DM Sans UI, JetBrains Mono data)
- Dark command center aesthetic
- Navy for UI chrome, signal colors for status ONLY

**Before creating ANY new page, study `services.php` and `dashboard.php` thoroughly.**

### Target Users
- **Business owner/administrator** (primary)
- **Dispatchers**
- **Office staff / bookkeepers**
- **Technicians** (limited mobile view, future)

---

## 2. Design Philosophy

### Concept: "Command Center" Aesthetic

The design is inspired by industrial command centers — think air traffic control or fleet logistics dashboards.

**Key Characteristics:**
- **Dark background** (#0C0E12) reduces eye strain during long shifts
- **High-contrast text** (#E8ECF4) ensures readability
- **Signal colors** (amber, green, red, blue, purple) convey status instantly
- **Monospace fonts for data** (IDs, prices, dates) reinforce precision
- **Information density** — show maximum relevant data without clutter
- **Consistent patterns** — every page uses the same visual language

### Visual Hierarchy Rules

| Element | Treatment |
|---------|-----------|
| **Page Headers** | Gradient background (surface → secondary), 2px navy bottom border, 24px title, icon + subtitle |
| **Stat Cards** | Dark surface with 4px left-colored border, hover lift effect, monospace values |
| **Data Tables** | Dark surface, uppercase 11px headers, monospace for IDs/dates/prices, hover row highlight |
| **Forms** | Dark inputs with navy focus rings, proper label hierarchy |
| **Modals** | Dark overlay (75% black + blur), elevated surface with border |
| **Buttons** | Navy gradient for primary, outlined for secondary, proper hover states |

### Color Psychology

| Color | Meaning | Usage |
|-------|---------|-------|
| **Navy Blue** | Trust, professionalism | ALL UI chrome (buttons, links, nav, focus states) |
| **Amber** | Warning, attention | Pending items, en-route status, payments due |
| **Green** | Success, available | Completed jobs, available techs, paid invoices |
| **Red** | Urgent, error | Overdue items, errors, cancelled status |
| **Blue** | Info, in-progress | Active jobs, informational states |
| **Purple** | Special, communication | SMS, after-hours, premium services |

**CRITICAL RULE:** Navy is NEVER used for status. Signal colors are NEVER used for buttons/nav.

---

## 3. Color System

### Background Hierarchy
```css
--bg-primary: #0C0E12;        /* Page background, deepest layer */
--bg-secondary: #12151B;      /* Sidebar, header — structural chrome */
--bg-surface: #181C24;        /* Cards, panels, table rows */
--bg-surface-hover: #1E2330;  /* Interactive hover state */
--bg-surface-active: #252A36; /* Active/pressed state */
--bg-input: #181C24;          /* Form inputs */
```

### Brand Colors (Navy — UI Chrome)
```css
--navy-700: #1E3A5F;          /* Dark navy (gradient end, deep accents) */
--navy-600: #234B78;          /* Mid-dark navy */
--navy-500: #2B5EA7;          /* Primary brand action (buttons, brand icon) */
--navy-400: #3B7DD8;          /* Active nav highlight, focus rings, links */
--navy-300: #5A9AE6;          /* Light navy (text on dark, badges, job IDs) */
--navy-glow: rgba(43,94,167,0.15);  /* Tinted background for navy elements */
```

### Signal Colors (Status ONLY — Never for UI Chrome)
```css
/* AMBER - Warning, En-route, Caution, Payments */
--amber-500: #F59E0B;
--amber-400: #FBBF24;
--amber-glow: rgba(245,158,11,0.12);

/* GREEN - Success, Completed, Available, Positive */
--green-500: #22C55E;
--green-glow: rgba(34,197,94,0.12);

/* RED - Urgent, Negative, Errors */
--red-500: #EF4444;
--red-glow: rgba(239,68,68,0.12);

/* BLUE - Info, In-Progress */
--blue-500: #3B82F6;
--blue-glow: rgba(59,130,246,0.12);

/* PURPLE - Communication, SMS */
--purple-500: #A855F7;
--purple-glow: rgba(168,85,247,0.12);
```

### Text Colors
```css
--text-primary: #E8ECF4;      /* Headings, primary content */
--text-secondary: #8A92A6;    /* Descriptions, secondary info */
--text-tertiary: #5C6478;     /* Labels, timestamps, disabled */
--text-inverse: #0C0E12;      /* Text on bright backgrounds */
```

### Border Colors
```css
--border-subtle: rgba(255,255,255,0.06);   /* Default dividers */
--border-medium: rgba(255,255,255,0.10);   /* Interactive borders */
--border-strong: rgba(255,255,255,0.16);   /* Focused/emphasized */
```

### Status-to-Color Mapping
| Status | Color Variable | Use Cases |
|--------|----------------|-----------|
| `available`, `active`, `completed`, `paid` | `--status-success` | Available techs, completed jobs, paid invoices |
| `pending`, `en-route`, `scheduled`, `draft` | `--status-warning` | Pending jobs, technician en route |
| `urgent`, `overdue`, `cancelled`, `error` | `--status-danger` | Overdue invoices, urgent jobs |
| `dispatched`, `in-progress`, `on-scene` | `--status-info` | Active work states |
| `off-duty`, `inactive`, `closed` | `--status-neutral` | Inactive states |
| `after-hours`, `premium` | `--status-purple` | Special billing states |

---

## 4. Typography

### Font Families
```css
--font-sans: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
--font-mono: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
```

### Font Usage Rules
| Font | Use For |
|------|---------|
| **DM Sans** | All UI text: buttons, labels, headings, navigation, body copy |
| **JetBrains Mono** | Data values: IDs, prices, dates, ETAs, counts, codes, phone numbers, VINs |

### Font Sizes
```css
Page title:      24px, weight 700, letter-spacing -0.5px
Panel title:     14px, weight 600
Nav label:       13.5px, weight 500
Body text:       13px, weight 400
Table header:    11px, weight 600, uppercase, letter-spacing 0.8px
Section title:   10px, weight 600, uppercase, letter-spacing 1.4px
Badge:           10px, weight 600, font-mono
Small label:     11px, weight 500
```

### Font Weights
```css
--font-normal: 400;   /* Body text */
--font-medium: 500;   /* Labels, emphasis */
--font-semibold: 600; /* Buttons, headings */
--font-bold: 700;     /* Stats, strong emphasis */
```

---

## 5. Spacing & Layout

### Spacing Scale (4px base)
```css
Content padding:    28px (desktop), 20px 16px (mobile)
Card padding:       20px
Panel header:       16px 20px
Table cell:         14px 20px
Gap between cards:  16px
Gap between panels: 20px
```

### Border Radius
```css
--radius-sm: 6px   /* buttons, inputs, nav items, small chips */
--radius-md: 10px  /* cards, panels */
--radius-lg: 14px  /* modals, large containers */
Status chips:  20px /* pill shape */
```

### Layout Dimensions
```css
--sidebar-width: 260px;
--sidebar-collapsed: 64px;
--header-height: 56px;
--content-max-width: 1600px;
--modal-width-sm: 400px;
--modal-width-md: 560px;
--modal-width-lg: 800px;
```

### Transitions
```css
--transition-fast:   150ms cubic-bezier(0.4, 0, 0.2, 1)  /* hover, focus */
--transition-smooth: 280ms cubic-bezier(0.4, 0, 0.2, 1)  /* layout shifts, sidebar */
```

---

## 6. Page Pattern Library

### Pattern 1: Dashboard/Overview Page

**Example:** `dashboard.php`

**Structure:**
```html
<style>
/* Page-specific styles scoped to component */
.dashboard-header { /* gradient header */ }
.stats-grid { /* responsive stat cards */ }
.stat-card-modern { /* individual stat card */ }
</style>

<!-- Gradient Header -->
<div class="dashboard-header">
  <div class="dashboard-header-content">
    <i class="fas fa-icon dashboard-header-icon"></i>
    <div class="dashboard-title-group">
      <h1>Page Title</h1>
      <p class="dashboard-subtitle">Context • Date</p>
    </div>
  </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card-modern stat-navy">
    <div class="stat-content">
      <div class="stat-info">
        <div class="stat-label">METRIC NAME</div>
        <div class="stat-value">1,234</div>
      </div>
      <i class="fas fa-icon stat-icon-modern"></i>
    </div>
  </div>
  <!-- More stat cards with stat-amber, stat-green, stat-blue -->
</div>

<!-- Data Section -->
<div class="recent-activity-card">
  <div class="recent-activity-header">
    <h5 class="recent-activity-title">
      <i class="fas fa-icon"></i> Section Title
    </h5>
    <a href="#" class="btn btn-sm btn-primary">Action</a>
  </div>
  <table class="dashboard-table">
    <!-- Table content -->
  </table>
</div>
```

**Key Elements:**
- Gradient header with icon + title + subtitle
- 4-column responsive stat cards (240px min-width)
- Left-colored borders on stat cards (navy/amber/green/blue)
- Monospace stat values (32px, bold)
- Clean data tables with proper typography

### Pattern 2: List/CRUD Page

**Example:** `services.php`, `customers.php`

**Structure:**
```html
<style>
/* Component-scoped styles */
.page-container { /* wrapper */ }
.app-header { /* gradient header */ }
.tab-nav { /* optional tabs */ }
.content-toolbar { /* search + actions */ }
.data-table { /* main data table */ }
</style>

<!-- Header -->
<div class="app-header">
  <div class="header-content">
    <i class="fas fa-icon header-icon"></i>
    <div class="header-title-group">
      <h1>Resource Name</h1>
      <p class="header-subtitle">Description</p>
    </div>
  </div>
</div>

<!-- Optional: Tabs -->
<div class="tab-nav">
  <button class="tab-button active">Tab 1</button>
  <button class="tab-button">Tab 2</button>
</div>

<!-- Toolbar -->
<div class="content-toolbar">
  <input class="form-input" placeholder="Search..." />
  <div class="toolbar-spacer"></div>
  <button class="btn btn-primary">+ Add New</button>
</div>

<!-- Data Table -->
<table class="data-table">
  <thead>
    <tr>
      <th>CODE</th>
      <th>NAME</th>
      <th>STATUS</th>
      <th>ACTIONS</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td class="table-code">#001</td>
      <td class="table-name">Item Name</td>
      <td><span class="badge bg-success">Active</span></td>
      <td>
        <button class="btn btn-sm btn-outline-primary">Edit</button>
      </td>
    </tr>
  </tbody>
</table>
```

**Key Elements:**
- Same gradient header pattern
- Optional tabbed navigation
- Search + action toolbar
- Clean data tables
- Badge-based status indicators
- Button groups for row actions

### Pattern 3: Detail/Form Page

**Structure:**
```html
<!-- Header with back button -->
<div class="page-header">
  <a href="?page=list" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back
  </a>
  <h1>Record Details</h1>
</div>

<!-- Info Cards -->
<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Section Title</h5>
      </div>
      <div class="card-body">
        <!-- Form fields or data display -->
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Actions</h5>
      </div>
      <div class="card-body">
        <!-- Action buttons -->
      </div>
    </div>
  </div>
</div>
```

**Key Elements:**
- Back navigation
- 8/4 column split (main content / sidebar)
- Bootstrap cards for sections
- Form fields with proper labels
- Action sidebar

---

## 7. Component Library

### Buttons

**Implementation:** Bootstrap classes with RoadRunner CSS overrides

**Primary Button** — Navy background, main actions
```html
<button class="btn btn-primary">
  <i class="fas fa-plus"></i> Add Service
</button>
```

```css
/* RoadRunner Override */
.btn-primary {
  background: linear-gradient(135deg, var(--navy-600), var(--navy-500)) !important;
  border: none !important;
  color: white;
  font-weight: 500;
  border-radius: var(--radius-sm);
}
.btn-primary:hover {
  background: linear-gradient(135deg, var(--navy-500), var(--navy-400)) !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px var(--navy-glow);
}
```

**Secondary Button** — Outlined, alternate actions
```html
<button class="btn btn-secondary">Cancel</button>
```

```css
/* RoadRunner Override */
.btn-secondary {
  background: var(--bg-surface);
  border: 1px solid var(--border-medium);
  color: var(--text-secondary);
}
.btn-secondary:hover {
  background: var(--bg-surface-hover);
  color: var(--text-primary);
}
```

**Danger Button** — Destructive actions only
```html
<button class="btn btn-danger">Delete</button>
```

```css
/* RoadRunner Override */
.btn-danger {
  background-color: var(--red-500) !important;
  border-color: var(--red-500) !important;
}
.btn-outline-danger {
  color: var(--red-500);
  border-color: rgba(239, 68, 68, 0.3);
}
.btn-outline-danger:hover {
  background: var(--red-glow);
  border-color: var(--red-500);
  color: var(--red-500);
}
```

**Bootstrap Button Sizes**
```html
<button class="btn btn-sm btn-primary">Small</button>
<button class="btn btn-primary">Default</button>
<button class="btn btn-lg btn-primary">Large</button>
```

### Status Badges

**Implementation:** Bootstrap badges with RoadRunner signal colors

```html
<span class="badge bg-success">Active</span>
<span class="badge bg-warning">Pending</span>
<span class="badge bg-danger">Urgent</span>
<span class="badge bg-info">In Progress</span>
```

```css
/* RoadRunner Overrides */
.badge {
  font-weight: 500;
  padding: 0.35em 0.65em;
  border-radius: var(--radius-sm);
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge.bg-success {
  background-color: var(--green-glow) !important;
  color: var(--green-500);
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.badge.bg-warning {
  background-color: var(--amber-glow) !important;
  color: var(--amber-400);
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.badge.bg-danger {
  background-color: var(--red-glow) !important;
  color: var(--red-500);
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.badge.bg-info {
  background-color: var(--blue-glow) !important;
  color: var(--blue-500);
  border: 1px solid rgba(59, 130, 246, 0.3);
}
```

### Stat Cards

**Implementation:** Bootstrap cards with RoadRunner signal color backgrounds

```html
<div class="card stat-card bg-primary text-white">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h6 class="text-uppercase mb-2">Total Customers</h6>
        <h2 class="mb-0">1,234</h2>
      </div>
      <i class="fas fa-users stat-icon"></i>
    </div>
  </div>
</div>
```

```css
/* RoadRunner Overrides */
.stat-card {
  transition: transform var(--transition-smooth), box-shadow var(--transition-smooth);
  border: 1px solid var(--border-medium);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.stat-icon {
  font-size: 2rem;
  opacity: 0.9;
}

/* Bootstrap color overrides for stat cards */
.bg-primary { background-color: var(--navy-500) !important; }
.bg-warning { background-color: var(--amber-500) !important; }
.bg-success { background-color: var(--green-500) !important; }
.bg-info { background-color: var(--blue-500) !important; }
```

### Data Tables

**Implementation:** Bootstrap tables with RoadRunner styling

```html
<div class="table-responsive">
  <table class="table table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Service Type</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>#4821</td>
        <td>John Doe</td>
        <td>Jump Start</td>
        <td><span class="badge bg-warning">Pending</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <a href="#" class="btn btn-outline-primary">View</a>
            <a href="#" class="btn btn-outline-success">Invoice</a>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

```css
/* RoadRunner Overrides */
.table {
  color: var(--text-primary);
}

.table th {
  border-top: none;
  font-weight: 600;
  background-color: var(--bg-secondary);
  color: var(--text-secondary);
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--border-medium);
}

.table td {
  border-color: var(--border-subtle);
}

.table-hover tbody tr:hover {
  background-color: var(--bg-surface-hover);
}

/* ID columns use monospace */
.table td:first-child {
  font-family: 'JetBrains Mono', monospace;
  color: var(--text-tertiary);
  font-size: 12px;
}
```

### Forms

**Implementation:** Bootstrap form components with RoadRunner styling

```html
<div class="mb-3">
  <label for="serviceName" class="form-label">Service Name *</label>
  <input type="text" class="form-control" id="serviceName" required>
</div>

<div class="mb-3">
  <label for="category" class="form-label">Category</label>
  <select class="form-select" id="category">
    <option value="">Select Category</option>
    <option value="roadside">Roadside Emergency</option>
    <option value="maintenance">Maintenance</option>
  </select>
</div>

<div class="mb-3">
  <label for="description" class="form-label">Description</label>
  <textarea class="form-control" id="description" rows="3"></textarea>
</div>
```

```css
/* RoadRunner Overrides */
.form-label {
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 13px;
}

.form-control,
.form-select {
  background-color: var(--bg-surface);
  border: 1px solid var(--border-medium);
  color: var(--text-primary);
}

.form-control:focus,
.form-select:focus {
  background-color: var(--bg-surface-hover);
  border-color: var(--navy-400);
  box-shadow: 0 0 0 0.25rem var(--navy-glow);
  color: var(--text-primary);
}

.form-control::placeholder {
  color: var(--text-tertiary);
}

.form-select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%238A92A6' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
}

.input-group-text {
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-medium);
  color: var(--text-secondary);
}
```

### Modals
```css
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: var(--bg-surface);
  border: 1px solid var(--border-medium);
  border-radius: var(--radius-lg);
  width: var(--modal-width-md);
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: var(--spacing-lg) var(--spacing-xl);
  border-bottom: 1px solid var(--border-subtle);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: var(--spacing-xl);
  overflow-y: auto;
}

.modal-footer {
  padding: var(--spacing-lg) var(--spacing-xl);
  border-top: 1px solid var(--border-subtle);
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
}
```

### Panels & Cards

**Implementation:** Bootstrap cards with RoadRunner styling

```html
<div class="card">
  <div class="card-header">
    <h5 class="card-title mb-0">Service Catalog</h5>
  </div>
  <div class="card-body">
    <!-- Content here -->
  </div>
</div>
```

```css
/* RoadRunner Overrides */
.card {
  background-color: var(--bg-surface);
  border: 1px solid var(--border-medium);
  border-radius: var(--radius-md);
  color: var(--text-primary);
}

.card-header {
  background-color: var(--bg-secondary);
  border-bottom: 1px solid var(--border-medium);
  padding: 1rem 1.25rem;
}

.card-title {
  color: var(--text-primary);
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}

.card-body {
  padding: 1.25rem;
}
```

---

## 8. Navigation & Routing

### Navigation Structure
```
OPERATIONS
  ├── dashboard      — Overview stats, active jobs, activity feed
  ├── dispatch       — Dispatch board, assign techs, manage queue
  ├── jobs           — Full job list with filters, job detail views
  └── customers      — Customer directory, service history

FLEET
  ├── technicians    — Tech roster, availability, performance
  ├── vehicles       — Service vehicle fleet, maintenance logs
  └── map            — Real-time GPS tracking of field units

BUSINESS
  ├── invoicing      — Create/send/track invoices
  ├── estimates      — Generate service estimates, approval tracking
  └── messaging      — SMS templates, conversation history, 10DLC compliance

CATALOG
  ├── services       — Service type definitions, pricing, labor rates
  └── parts          — Parts inventory, pricing, suppliers, stock levels

FINANCIAL
  ├── accounting     — Revenue/expense tracking, P&L, chart of accounts
  ├── payments       — Payment processing, history, refunds
  └── payroll        — Technician pay, commission tracking, pay periods

SYSTEM
  ├── settings       — Business info, system config, integrations
  └── users          — User accounts, roles, permissions
```

### Sidebar Structure
```html
<nav class="sidebar">
  <div class="sidebar-header">
    <div class="logo">RoadRunner</div>
  </div>
  
  <div class="nav-section">
    <div class="nav-section-label">Operations</div>
    <a class="nav-item active" data-page="dashboard" onclick="navigate(this)">
      <svg><!-- Dashboard icon --></svg>
      <span>Dashboard</span>
    </a>
    <!-- Additional nav items -->
  </div>
  
  <!-- Additional sections -->
</nav>
```

### Navigation Function
```javascript
function navigate(element) {
  // Remove active from all nav items
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
  });
  
  // Add active to clicked item
  element.classList.add('active');
  
  // Get page name
  const page = element.getAttribute('data-page');
  
  // Update page title and breadcrumb
  const titles = {
    dashboard: ['Dashboard', 'Operations overview and activity'],
    dispatch: ['Dispatch Board', 'Manage job queue and assignments'],
    jobs: ['Service Jobs', 'All jobs and service requests'],
    customers: ['Customers', 'Customer directory and history'],
    technicians: ['Technicians', 'Technician roster and availability'],
    vehicles: ['Vehicles', 'Fleet management and maintenance'],
    map: ['Live Map', 'Real-time fleet tracking'],
    invoicing: ['Invoicing', 'Create and manage invoices'],
    estimates: ['Estimates', 'Service estimates and approvals'],
    messaging: ['Messaging', 'SMS communications and templates'],
    services: ['Service Catalog', 'Service definitions and pricing'],
    parts: ['Parts Catalog', 'Parts inventory and pricing'],
    accounting: ['Accounting', 'Financial records and reports'],
    payments: ['Payments', 'Payment processing and history'],
    payroll: ['Payroll', 'Technician compensation management'],
    settings: ['Settings', 'System configuration'],
    users: ['Users', 'User accounts and permissions'],
  };
  
  // Render page content
  renderPage(page);
}
```

---

## 9. Module Specifications

### Dashboard (`data-page="dashboard"`)
**Purpose:** At-a-glance operational overview. First screen dispatchers see.

**Components:**
- **Stats Grid:** Active Jobs, Completed Today, Available Techs, Daily Revenue
- **Active Jobs Table:** Current jobs with status, customer, tech, location
- **Activity Feed:** Real-time log of system events (new jobs, status changes, payments)

### Dispatch Board (`data-page="dispatch"`)
**Purpose:** Manage job queue and technician assignments.

**Components:**
- **Kanban columns:** Unassigned → Dispatched → En Route → On Scene → Completed
- **Drag-and-drop** job cards between columns
- **Quick-add** job form
- **Tech availability panel**
- **Map sidebar** for location context

### Service Jobs (`data-page="jobs"`)
**Purpose:** Full job lifecycle management.

**List View Columns:** Job ID, Customer, Service Type, Status, Technician, Created, Location

**Job Detail View:**
- Customer info + vehicle
- Service type (from catalog)
- Parts used (from catalog)
- Technician assignment
- Timestamps (created, dispatched, arrived, completed)
- Photos/attachments
- Notes
- Linked estimate/invoice
- SMS communication log

**Status Workflow:** Created → Dispatched → En Route → On Scene → In Progress → Completed → Invoiced

### Customers (`data-page="customers"`)
**Purpose:** Customer directory and relationship management.

**Customer Record:**
- Contact: name, phone, email, address
- Vehicles: make/model/year/VIN (multiple)
- Service history
- Invoice history
- SMS opt-in status
- Communication log
- Notes

### Technicians (`data-page="technicians"`)
**Purpose:** Manage technician roster and performance.

**Tech Profile:**
- Contact info
- Skills/certifications
- Assigned vehicle
- Availability schedule
- Current status: Available, En Route, On Scene, Off Duty
- Performance stats: avg response time, jobs/day, rating

### Vehicles (`data-page="vehicles"`)
**Purpose:** Fleet management.

**Vehicle Record:**
- Year/make/model, VIN, license plate
- Current mileage
- Maintenance schedule + history
- Assigned technician
- Equipment inventory
- Insurance info

### Live Map (`data-page="map"`)
**Purpose:** Real-time fleet tracking.

**Features:**
- Map embed (Google Maps or Mapbox)
- Real-time technician positions
- Active job markers
- Click marker → job/tech details
- Clustering at zoom levels

### Invoicing (`data-page="invoicing"`)
**Purpose:** Create, send, and track invoices.

**Invoice List Columns:** Invoice #, Customer, Date, Amount, Status, Actions

**Invoice Statuses:** Draft, Sent, Viewed, Paid, Overdue, Cancelled

**Invoice Detail:**
- Customer info
- Line items (from catalog)
- Tax calculation
- Total
- Payment status
- Send via SMS/email
- Payment recording

### Estimates (`data-page="estimates"`)
**Purpose:** Generate and track service estimates.

**Estimate List Columns:** Estimate #, Customer, Date, Amount, Status, Expires

**Estimate Statuses:** Draft, Sent, Viewed, Approved, Declined, Expired

**Workflow:**
- Create from catalog items
- Send approval link via SMS
- Customer approves online
- Convert to Job + Invoice

### Messaging (`data-page="messaging"`)
**Purpose:** SMS communications hub.

**Components:**
- Conversation thread view per customer
- Template management
- Auto-message triggers:
  - Dispatch notification
  - Tech ETA update
  - Estimate approval link
  - Invoice link
  - Payment confirmation
  - Review request
- 10DLC compliance panel
- Opt-in/opt-out management

### Services Catalog (`data-page="services"`)
**Purpose:** Define and price all services.

**List Columns:** Code, Service Name, Category, Labor Hours, Rate, Base Price, Status

**Categories:** Roadside, Towing, Mechanical, Electrical, Diagnostic, Maintenance

**Service Record:**
- Code (auto: SVC-001)
- Name, description
- Category
- Estimated labor hours
- Labor rate (or default)
- Base parts cost
- Total base price
- Tax applicable (toggle)
- Active/inactive
- Notes

### Parts Catalog (`data-page="parts"`)
**Purpose:** Parts inventory and pricing.

**List Columns:** Part #, Name, Category, Cost, Markup %, Sell Price, Stock, Supplier, Status

**Categories:** Batteries, Tires, Belts/Hoses, Filters, Fluids, Electrical, Brakes, Body, Misc

**Part Record:**
- Part number
- Name, description
- Category
- Cost price
- Markup %
- Sell price (calculated)
- Supplier name + part #
- Reorder point
- Current stock qty
- Unit of measure
- Active/inactive

### Accounting (`data-page="accounting"`)
**Purpose:** Financial tracking and reporting.

**Dashboard Cards:** Revenue (day/week/month/YTD), Expenses, Net Profit, Outstanding Receivables

**Chart of Accounts:**
- Revenue: Service Revenue, Parts Revenue, Towing Revenue, After-Hours Surcharge
- Expense: Fuel, Parts Cost, Vehicle Maintenance, Insurance, Tools, Marketing, etc.
- Asset: Cash, Accounts Receivable, Parts Inventory, Vehicles, Equipment
- Liability: Accounts Payable, Sales Tax Payable, Loans

**Transaction Ledger Columns:** Date, Type, Description, Account, Amount, Reference, Status

**Reports:** P&L Statement, Revenue by Service Type, Expense Breakdown, Monthly Comparison

### Payments (`data-page="payments"`)
**Purpose:** Payment processing and history.

**Payment List Columns:** Date, Customer, Invoice #, Amount, Method, Status, Collected By

**Payment Methods:** Cash, Card, Check, Digital (Venmo, Zelle, etc.)

**Features:**
- Record payment
- Issue full/partial refund
- Link to invoice
- Export history

### Payroll (`data-page="payroll"`)
**Purpose:** Technician compensation management.

**Tech Pay Overview Columns:** Tech Name, Jobs Completed, Hours, Base Pay, Commission, Bonuses, Total

**Features:**
- Pay period management (weekly/biweekly)
- Commission rules (per-tech or global %)
- Pay run workflow: Review → Approve → Mark Paid
- Export payroll

### Settings (`data-page="settings"`)
**Purpose:** System configuration.

**Sections:**
- Business info: name, address, phone, logo, tax ID
- Default labor rates
- Tax rates
- SMS provider config (Telnyx)
- Notification preferences
- Data backup/export
- Integration settings

### Users (`data-page="users"`)
**Purpose:** User accounts and access control.

**Roles:**
- **Admin:** Full access
- **Dispatcher:** Operations + Fleet
- **Bookkeeper:** Financial + Invoicing
- **Technician:** Assigned jobs only (mobile)

**Features:**
- User list with role/status
- Invite/deactivate users
- Role assignment
- Audit log

---

## 10. Coding Rules for Agents

### CRITICAL RULES — Read First

| Rule | Description |
|------|-------------|
| **🎯 REFERENCE services.php & dashboard.php** | These are the canonical implementations. Copy their patterns exactly. |
| **✅ USE Bootstrap 5.1.3** | Bootstrap provides structure (grid, cards, tables, forms). Use its classes. |
| **🎨 OVERRIDE with RoadRunner CSS** | Define CSS variables in style.css, override Bootstrap colors with `!important`. |
| **🚫 NEVER hardcode colors** | Always use `var(--navy-500)`, `var(--amber-500)`, etc. Never use hex values directly. |
| **📝 TYPOGRAPHY HIERARCHY** | DM Sans for UI text, JetBrains Mono for data (IDs, prices, dates, codes). |
| **🔵 NAVY = UI CHROME** | Buttons, links, nav, focus states, headers. NEVER for status. |
| **🟡🟢🔴 SIGNALS = STATUS** | Amber/green/red/blue/purple for status meaning ONLY. NEVER for buttons/nav. |
| **📐 MATCH THE PATTERN** | Every page should have gradient header, proper card styling, consistent spacing. |
| **🎭 SCOPE YOUR STYLES** | Use component-specific class prefixes to avoid conflicts (.dashboard-*, .services-*). |
| **📱 RESPONSIVE BY DEFAULT** | Bootstrap handles responsive. Use its grid system. |
| **📁 CREATE IN pages/** | New pages go in pages/ directory, not root. |

### Workspace Organization

**Location:** `claude_admin2/` (workspace = production, served directly)  
**URL:** `http://localhost/claude_admin2/`

**File Locations:**

| File Type | Location |
|-----------|----------|
| **Pages** | `pages/*.php` |
| **Main router** | `_working/index.php` |
| **Service catalog** | `_working/services.php` |
| **Global CSS** | `assets/css/style.css` |
| **Helper functions** | `includes/functions.php` |
| **API endpoints** | `api/*.php` |

Edits are live immediately — no deploy step needed.

### Implementation Workflow

**When creating a new page:**

1. **Study the reference pages:**
   - Read `dashboard.php` for layout patterns
   - Read `services.php` for React integration
   - Copy their CSS structure exactly

2. **Use this template:**
   ```php
   <?php
   // Page logic, database queries
   ?>
   
   <style>
   /* Component-scoped styles with prefix */
   .mypage-header { /* gradient header pattern */ }
   .mypage-card { /* card pattern */ }
   /* Copy patterns from dashboard.php */
   </style>
   
   <!-- Gradient Header -->
   <div class="mypage-header">
     <div class="header-content">
       <i class="fas fa-icon header-icon"></i>
       <div class="header-title-group">
         <h1>Page Title</h1>
         <p class="header-subtitle">Description</p>
       </div>
     </div>
   </div>
   
   <!-- Page Content -->
   <!-- Use Bootstrap grid: .container-fluid, .row, .col-* -->
   <!-- Use Bootstrap components: .card, .table, .btn -->
   ```

3. **Copy exact CSS patterns:**
   - Gradient header: `linear-gradient(135deg, var(--bg-surface), var(--bg-secondary))`
   - Border: `border-bottom: 2px solid var(--navy-500)`
   - Card backgrounds: `var(--bg-surface)`
   - Card borders: `1px solid var(--border-medium)`
   - Table headers: `background: var(--bg-secondary)`, `11px uppercase`
   - Stat cards: left border 4px, signal color, hover effect

4. **Use proper typography:**
   - Page titles: `24px`, `font-weight: 700`, `-0.5px letter-spacing`
   - Subtitles: `13px`, `color: var(--text-secondary)`
   - Table headers: `11px`, `font-weight: 600`, `uppercase`, `0.5px letter-spacing`
   - Body text: `13px`
   - Data values: `font-family: var(--font-mono)`

5. **Test these aspects:**
   - [ ] Gradient header with navy border
   - [ ] Proper typography hierarchy
   - [ ] Bootstrap cards styled correctly
   - [ ] Tables with uppercase headers
   - [ ] Buttons use navy blue
   - [ ] Status badges use signal colors
   - [ ] Hover states work
   - [ ] No hardcoded colors
   - [ ] Responsive at 768px

### Integration Patterns

**PHP Pages:**
- Use Bootstrap classes for structure
- Add scoped `<style>` block for page-specific styling
- Override Bootstrap defaults with RoadRunner CSS variables
- Include Font Awesome icons
- Use helper functions: `format_currency()`, `get_status_badge()`, etc.

**React Components (Optional):**
- Load React 18 + Babel from CDN
- Embed in PHP page with `<div id="root"></div>`
- Use same CSS variable system
- Scope styles to component container
- See `services.php` for complete pattern

**Modals:**
- Use React for complex interactions (see services.php)
- Use Bootstrap modals for simple confirms (PHP pages)
- Dark overlay: `rgba(0, 0, 0, 0.75)` + `backdrop-filter: blur(4px)`
- Modal surface: `var(--bg-surface)` with `var(--border-medium)` border

### Naming Conventions

**CSS Classes:**
```
Structural:     .module-name           (e.g., .jobs-list, .catalog-grid)
Component:      .component-variant     (e.g., .btn-primary, .stat-card)
State:          .is-state or .state    (e.g., .is-loading, .active, .collapsed)
```

**JS Functions:**
```
Event handlers: handleActionTarget()   (e.g., handleCreateJob(), handleDeletePart())
Navigation:     navigate(el)           (already exists)
Data:           loadModuleData()       (e.g., loadJobsList(), loadCatalogParts())
Render:         renderModuleName()     (e.g., renderJobsTable(), renderAccountingDashboard())
Utility:        formatType()           (e.g., formatCurrency(), formatDate())
```

**Data Attributes:**
```
data-page="pagename"        (navigation routing)
data-id="record-id"         (row/item identification)
data-status="status-name"   (filterable status)
data-category="category-name" (filterable category)
```

### Code Organization

```
CSS: Organized by section in <style> block
     /* ═══ MODULE NAME ═══ */
     Add new module styles at END, before @media queries

HTML: Module content inside #contentArea
      Use: page-header → stats-grid → content-grid pattern

JS:  In <script> block at bottom:
     1. Utility functions at top
     2. Module render functions in middle
     3. Event handlers grouped by module
     4. Global initialization at bottom
```

### Data Management

```javascript
// Data store structure
const store = {
  jobs: [],
  customers: [],
  catalog: { services: [], parts: [] },
  financial: { transactions: [], payments: [] },
};

// Monetary values: stored as integers (cents)
// Display: formatCurrency(amount) → (amount / 100).toFixed(2)

// Dates: stored as ISO strings
// Display: formatDate() or formatRelativeTime()

// IDs follow pattern: PREFIX-YYMMDD-SEQ-VER (4-part canonical format)
// Tickets: RR-260213-001-01, Estimates: EST-260213-003-02, Work Orders: WO-260213-001-01
// Invoices: INV-260213-001-01, Receipts: RCT-260213-001-01, Change Orders: CO-260213-001-01
// VER increments on edit (old version archived as read-only snapshot)
```

### Adding a New Module (Step by Step)

1. **Add nav item** in sidebar HTML with correct section
2. **Register page title** in `navigate()` function's titles object
3. **Create render function** that returns/injects HTML into `#contentArea`
4. **Add module styles** at end of `<style>` before `@media`
5. **Add module logic** (event handlers, data management)
6. **Test** nav highlighting, breadcrumb, responsive behavior

### What NOT to Do

```
❌ Do NOT add additional CSS frameworks (only Bootstrap 5.1.3)
❌ Do NOT hardcode colors — always use CSS variables
❌ Do NOT ignore the reference pages (dashboard.php, services.php)
❌ Do NOT use light theme colors (this is dark-first)
❌ Do NOT use navy blue for status — use signal colors
❌ Do NOT use signal colors for buttons/nav — use navy blue
❌ Do NOT create inconsistent page headers — copy the gradient pattern
❌ Do NOT skip the left-colored borders on stat cards
❌ Do NOT forget uppercase table headers (11px, 0.5px spacing)
❌ Do NOT use Comic Sans (seriously, DM Sans + JetBrains Mono only)
```

### Common Mistakes to Avoid

**Mistake #1: Creating unique snowflake designs**
- ❌ Bad: Each page looks totally different
- ✅ Good: All pages use the same gradient header, stat card, and table patterns

**Mistake #2: Bootstrap color classes without overrides**
- ❌ Bad: Light gray Bootstrap defaults show through
- ✅ Good: RoadRunner CSS variables override all Bootstrap colors

**Mistake #3: Hardcoded colors**
- ❌ Bad: `background: #2B5EA7;`
- ✅ Good: `background: var(--navy-500);`

**Mistake #4: Wrong font choices**
- ❌ Bad: Everything in JetBrains Mono (looks like Matrix)
- ✅ Good: DM Sans for UI, JetBrains Mono only for data values

**Mistake #5: Ignoring the visual hierarchy**
- ❌ Bad: 16px page titles, 14px stat values
- ✅ Good: 24px page titles, 32px monospace stat values

**Mistake #6: Using navy for status**
- ❌ Bad: Navy badge for "pending" status
- ✅ Good: Amber badge for "pending" status, navy button for "View Details"

### Quality Checklist

Before marking a page complete, verify:

**Visual Design:**
- [ ] Has gradient header matching dashboard.php pattern
- [ ] Uses 24px page title with -0.5px letter-spacing
- [ ] Stats/info cards have left-colored borders (4px)
- [ ] Tables have uppercase headers (11px, 0.5px spacing)
- [ ] Navy blue used ONLY for UI chrome (buttons, links, focus)
- [ ] Signal colors used ONLY for status meaning
- [ ] Monospace font used for IDs, prices, dates, codes
- [ ] Proper hover states on interactive elements

**Technical Implementation:**
- [ ] No hardcoded color values (all CSS variables)
- [ ] Bootstrap classes used for structure
- [ ] Scoped styles with component prefix
- [ ] Responsive at 768px viewport
- [ ] Font Awesome icons loaded
- [ ] Google Fonts (DM Sans + JetBrains Mono) loaded

**Code Quality:**
- [ ] PHP logic separated from presentation
- [ ] Prepared statements for database queries
- [ ] Helper functions used (format_currency, etc.)
- [ ] Proper error handling
- [ ] XSS protection (htmlspecialchars)

**User Experience:**
- [ ] Clear page purpose and navigation
- [ ] Search/filter functionality where appropriate
- [ ] Empty states with helpful messages
- [ ] Loading states for async operations
- [ ] Success/error feedback for actions

---

## 11. Accessibility & Responsive

### Accessibility Requirements

- All interactive elements keyboard navigable (tab order)
- Visible focus states (navy glow ring)
- Color alone never conveys meaning — always pair with text/icon
- Tables use proper `<th>` headers with `scope`
- Modals trap focus and close on Escape
- Status chips include text labels, not just color dots
- SVG icons: `aria-hidden="true"` (decorative) or `aria-label` (functional)

### Responsive Breakpoints

```css
/* Desktop: > 1200px */
Full layout. 4-column stats, 2-column content grid, full sidebar.

/* Tablet: ≤ 1200px */
Content grid → single column. Stats → 2 columns.

/* Mobile: ≤ 768px */
Sidebar hidden (slide-in on toggle). Stats → single column.
Header search hidden. Content padding reduced.
```

### Mobile Sidebar Behavior

```css
/* Mobile: sidebar hidden by default */
.sidebar {
  position: fixed;
  left: -280px;
  transition: var(--transition-normal);
}

/* Toggle adds class to show */
.sidebar.mobile-open {
  left: 0;
}

/* Overlay behind sidebar when open */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 99;
}
```

---

## 12. File Structure & Deployment

### Current Workspace Structure

```
claude_admin2/                      — Development workspace
├── _working/                       — 🔧 Active development files
│   ├── index.php                   — Main router (deploy after nav changes)
│   ├── services.php                — Service catalog React app (deploy after edits)
│   └── style.css                   — RoadRunner CSS overrides (deploy to assets/)
│
├── _archive_reference/             — 📦 Original design references
│   ├── dash.jsx                    — User's original React design
│   └── service-catalog.html        — Standalone catalog implementation
│
├── pages/                          — 📄 All application pages
│   ├── dashboard.php               — ✅ Transformed (canonical reference)
│   ├── customers.php               — ⏳ Needs transformation
│   ├── technicians.php             — ⏳ Needs transformation
│   ├── invoices.php                — ⏳ Needs transformation
│   ├── service-requests.php        — ⏳ Needs transformation
│   ├── login.php                   — ⏳ Needs transformation
│   └── logout.php                  — Utility page
│
├── config/                         — ⚙️ Configuration
│   └── database.php                — MySQL connection (credentials from .env)
│
├── includes/                       — 🔧 Helper functions
│   └── functions.php               — Utility functions
│
├── api/                            — 🌐 API endpoints
│   ├── assign_technician.php       — Assign tech to job
│   └── update_status.php           — Update job status
│
├── assets/                         — 🎨 Static assets
│   ├── css/
│   │   └── style.css               — RoadRunner CSS variables + Bootstrap overrides
│   └── js/
│       └── app.js                  — Application JavaScript
│
├── .github/                        — 🤖 GitHub & Copilot
│   └── copilot-instructions.md     — Copilot configuration
│
├── DESIGN.md                       — 📖 This document (v3.0)
├── WORKSPACE.md                    — 📋 Workspace organization guide
├── README.md                       — 📝 Project notes
├── reset_db.php                    — 🔄 Database reset utility
└── test.php                        — 🧪 Testing utility
```

### Development Workflow

App runs directly from workspace — edits are live immediately.

**URL:** `http://localhost/claude_admin2/`

**Edit files in place:**
```bash
code _working/index.php       # Main router
code pages/dashboard.php      # Page files
code assets/css/style.css     # Global styles
```

### Environment Configuration

| Setting | Value |
|---------|-------|
| **Workspace Path** | `\\wsl.localhost\Ubuntu\var\www\html\claude_admin2` |
| **Application URL** | `http://localhost/claude_admin2/` |
| **Database Host** | localhost |
| **Database Name** | roadside_assistance |
| **Database User** | (from `.env` — `DB_USER`) |
| **Database Password** | (from `.env` — `DB_PASS`) |

### Future Enhancements

**When scaling to framework (React, Next.js, etc.):**
```
/src
  /components
    /shell          — Sidebar, Header, ContentArea
    /common         — Button, StatCard, Panel, Table, Modal, StatusChip, Form
    /dashboard      — DashboardView, ActiveJobsTable, ActivityFeed
    /dispatch       — DispatchBoard, JobQueue, AssignmentCard
    /jobs           — JobsList, JobDetail, JobForm
    /customers      — CustomerList, CustomerDetail, VehicleForm
    /fleet          — TechList, TechDetail, VehicleList, LiveMap
    /catalog        — ServicesList, ServiceForm, PartsList, PartForm
    /financial      — AccountingDash, TransactionLedger, PaymentsList, PayrollView
    /invoicing      — InvoiceList, InvoiceDetail, InvoiceForm
    /estimates      — EstimateList, EstimateDetail, EstimateForm
    /messaging      — ConversationList, ThreadView, TemplateManager
    /settings       — SettingsForm, UserList, RoleManager
  /utils            — formatCurrency, formatDate, generateId, etc.
  /stores           — Data management / API integration
  /styles           — CSS variables, global styles, component styles
```

---

## 13. Quick Reference: Copy-Paste Patterns

### Gradient Header Pattern
```html
<style>
.mypage-header {
    background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 24px 28px;
    margin: -1rem -1rem 2rem -1rem;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
}
.mypage-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}
.mypage-header-icon {
    font-size: 26px;
    color: var(--navy-300);
}
.mypage-title-group h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--navy-300);
    letter-spacing: -0.5px;
    margin: 0;
}
.mypage-subtitle {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
    margin-top: 2px;
}
</style>

<div class="mypage-header">
    <div class="mypage-header-content">
        <i class="fas fa-icon mypage-header-icon"></i>
        <div class="mypage-title-group">
            <h1>Page Title</h1>
            <p class="mypage-subtitle">Subtitle or context</p>
        </div>
    </div>
</div>
```

### Stat Card Pattern
```html
<style>
.stat-card-modern {
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-md);
    padding: 20px;
    transition: all var(--transition-smooth);
    position: relative;
    overflow: hidden;
}
.stat-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}
.stat-card-modern.stat-navy::before { background: var(--navy-500); }
.stat-card-modern.stat-amber::before { background: var(--amber-500); }
.stat-card-modern.stat-green::before { background: var(--green-500); }
.stat-card-modern.stat-blue::before { background: var(--blue-500); }
.stat-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-tertiary);
    margin-bottom: 8px;
}
.stat-value {
    font-size: 32px;
    font-weight: 700;
    font-family: var(--font-mono);
    color: var(--text-primary);
}
</style>

<div class="stat-card-modern stat-navy">
    <div class="stat-label">METRIC NAME</div>
    <div class="stat-value">1,234</div>
</div>
```

### Data Table Pattern
```html
<style>
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-md);
}
.data-table thead th {
    background: var(--bg-secondary);
    color: var(--text-tertiary);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 20px;
    text-align: left;
    border-bottom: 1px solid var(--border-medium);
}
.data-table tbody td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-primary);
    font-size: 13px;
}
.data-table tbody tr:hover {
    background: var(--bg-surface-hover);
}
.data-table tbody td:first-child {
    font-family: var(--font-mono);
    color: var(--text-tertiary);
    font-size: 12px;
}
</style>

<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>NAME</th>
            <th>STATUS</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#001</td>
            <td>Item Name</td>
            <td><span class="badge bg-success">Active</span></td>
        </tr>
    </tbody>
</table>
```

---

**Last Updated:** February 5, 2026  
**Version:** 3.0 — Production Implementation Guide  
**Author:** RoadRunner Development Team
