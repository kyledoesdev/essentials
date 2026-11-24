# Changelog

All notable changes to `essentials` will be documented in this file.

## v0.0.10 - 2026-11-24

- Add `essentials:backup-database`: dumps, gzips and uploads the database to S3 under `{directory}/{timestamp}/{database}.sql.gz`, deletes backups older than the retention window and logs the result. Schedules itself from `essentials.db_backups` when enabled
- Require `illuminate/console`, `illuminate/filesystem`, `illuminate/process` and `league/flysystem-aws-s3-v3`
- Upgrade Pest to v5 and `actions/checkout` to v7
