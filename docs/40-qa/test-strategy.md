# Test Strategy

## Current Approach (MVP)
- Manual testing via browser
- PHP syntax validation (`php -l`)
- SQL injection review (verify prepared statements)
- XSS review (verify htmlspecialchars usage)

## Test Areas
| Area | Method | Status |
|------|--------|--------|
| PHP syntax | `php -l` on all .php files | Automated |
| SQL injection | Code review for raw queries | Manual |
| XSS | Code review for unescaped output | Manual |
| CRUD operations | Browser testing each page | Manual |
| Responsive design | Browser DevTools resize | Manual |
| API endpoints | curl/browser testing | Manual |

## Future: Automated Testing
- PHPUnit for backend logic
- Playwright for E2E UI testing
- API integration tests

## Last Updated
2026-02-12