# Rollback Plan

## If a Release Goes Wrong

### Step 1: Identify
- Check Apache error log: `tail -f /var/log/apache2/error.log`
- Check browser console for JS errors
- Try loading each page to isolate the failure

### Step 2: Rollback Code
Since the app runs directly from the workspace:
1. `git stash` or `git checkout` to previous working commit
2. Or restore files from `_archive_reference/` if applicable

### Step 3: Rollback Database
If schema was changed:
1. Run `php reset_db.php` to rebuild tables
2. Or restore from backup: `mysql -u "$DB_USER" -p "$DB_NAME" < backup.sql`

### Step 4: Verify
- Load dashboard
- Run through regression checklist
- Confirm no errors in Apache log

### Step 5: Post-Mortem
- Log the issue in Director → Decisions as an ADR
- Update risk register
- Add regression test for the failure

## Last Updated
2026-02-12