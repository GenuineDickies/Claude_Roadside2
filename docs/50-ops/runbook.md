# Runbook — Local Development

## Environment
- **OS:** WSL Ubuntu on Windows
- **Web Server:** Apache 2
- **PHP:** 8.0+
- **Database:** MySQL (localhost, credentials from `.env`, database: roadside_assistance)
- **Workspace:** /var/www/html/claude_admin2/
- **URL:** http://localhost/claude_admin2/

## Start Services
```bash
sudo service apache2 start
sudo service mysql start
```

## Default Login
- Username: admin
- Password: generated at first launch — check `/var/log/apache2/error.log` and change it immediately

## Common Tasks
### Reset Database
```bash
php reset_db.php
```

### Check PHP Syntax
```bash
find . -name "*.php" -exec php -l {} \;
```

### View Error Log
```bash
tail -f /var/log/apache2/error.log
```

## File Locations
| What | Where |
|------|-------|
| Entry point | index.php → _working/index.php |
| Pages | pages/*.php |
| APIs | api/*.php |
| Styles | assets/css/style.css |
| Config | config/database.php |

## Last Updated
2026-02-12