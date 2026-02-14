# Definition of Done

A feature/page is considered "done" when:

## Code Quality
- [ ] PHP syntax check passes (`php -l`)
- [ ] No hardcoded colors — CSS variables only
- [ ] CSS scoped with page prefix (no global leaks)
- [ ] All SQL uses PDO prepared statements
- [ ] All user output escaped with htmlspecialchars()
- [ ] No console.log() left in production JS

## Design Compliance
- [ ] Gradient header with navy border present
- [ ] DM Sans for UI text, JetBrains Mono for data
- [ ] Navy = UI chrome only, signal colors = status only
- [ ] Responsive down to 576px
- [ ] Dark theme consistent with --bg-* variables

## Functionality
- [ ] CRUD operations work end-to-end
- [ ] Form validation (client + server)
- [ ] API returns consistent JSON format
- [ ] Error states handled gracefully
- [ ] Empty states have appropriate messaging

## Last Updated
2026-02-12