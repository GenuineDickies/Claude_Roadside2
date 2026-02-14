# Architecture Document

## Overview
RoadRunner Admin follows a traditional server-rendered PHP architecture with AJAX enhancements.

## Stack
| Layer | Technology |
|-------|-----------|
| Server | Apache on WSL Ubuntu |
| Backend | PHP 8 + PDO |
| Database | MySQL (roadside_assistance) |
| Frontend | Bootstrap 5.1.3 + Vanilla JS |
| Catalog | React 18 (CDN, isolated) |
| Fonts | DM Sans (UI), JetBrains Mono (data) |

## Application Structure
```
index.php              → Entry point, routes to _working/index.php
_working/index.php     → Session, auth, sidebar nav, page router
pages/*.php            → Individual page views
api/*.php              → JSON API endpoints
config/database.php    → PDO connection
includes/functions.php → Shared helpers
assets/css/style.css   → Global styles
assets/js/app.js       → Global JS
```

## Patterns
- **Page inclusion:** Main router includes page files via switch/case
- **API pattern:** POST/GET → JSON response `{ success: bool, data?, error? }`
- **CSS scoping:** Each page prefixes styles (`.intake-*`, `.est-*`, etc.)
- **Security:** PDO prepared statements, htmlspecialchars() output escaping
- **Session:** File-based sessions in local `sessions/` directory

## Last Updated
2026-02-12