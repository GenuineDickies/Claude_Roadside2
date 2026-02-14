# Risk Register

| ID | Risk | Likelihood | Impact | Mitigation | Status |
|----|------|-----------|--------|------------|--------|
| R1 | SQL injection via unsanitized input | Medium | Critical | All queries use PDO prepared statements | Mitigated |
| R2 | XSS via unescaped output | Medium | High | htmlspecialchars() on all user output | Mitigated |
| R3 | Session hijacking | Low | High | Local session directory, secure cookies | Mitigated |
| R4 | Data loss — no backups | Medium | Critical | Need automated DB backup script | Open |
| R5 | Single point of failure — one server | High | High | Future: containerization | Accepted |
| R6 | No rate limiting on API | Medium | Medium | Future: implement throttling | Open |

## Last Updated
2026-02-12