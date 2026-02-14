# Release Checklist

## Pre-Release
- [ ] All PHP files pass syntax check
- [ ] No hardcoded credentials in code
- [ ] All SQL uses prepared statements
- [ ] All output properly escaped
- [ ] Regression checklist passes
- [ ] All Director artifacts at "approved" status
- [ ] No console.log() in production JS

## Release
- [ ] Tag version in Director releases
- [ ] Update version number in applicable files
- [ ] Database migration scripts ready (if schema changed)
- [ ] Backup current database

## Post-Release
- [ ] Verify app loads correctly
- [ ] Smoke test core workflow
- [ ] Monitor error logs for 24 hours
- [ ] Update release notes in Director

## Last Updated
2026-02-12