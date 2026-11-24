# Changelog

All notable changes to `essentials` will be documented in this file.

## v0.0.10 - 2026-11-24

- Add `essentials:backup-database`: dumps, gzips and uploads the database to S3 under `{directory}/{timestamp}/{database}.sql.gz`, deletes backups older than the retention window and logs the result. Schedules itself from `essentials.db_backups` when enabled
- Require `illuminate/console`, `illuminate/filesystem`, `illuminate/process` and `league/flysystem-aws-s3-v3`
- Require PHP 8.5 and drop the PHP 8.4 CI jobs
- Allow Pest 4 or 5, so Laravel 12 tests on Pest 4 and Laravel 13 on Pest 5
- Upgrade `actions/checkout` to v7
- Fix legacy timezone names like `US/Eastern` falling back to an IP lookup on servers without `tzdata-legacy` (Ubuntu 24.04 and newer). They now map to the current name through `intl`
