## kyledoesdev/essentials

Essential utilities for my Laravel applications — the helpers, macros and generators I'd otherwise copy between projects.

### Installation

```bash
composer require kyledoesdev/essentials
```

### Features

**Timezone detection** — `timezone()` resolves the timezone for the current request from the authenticated user, an IP geo lookup, then `config('app.timezone')`. Results are cached per IP address.

**Carbon macro** — `Carbon::parse($date)->inUserTimezone()` shifts an instance into that timezone.

**Action generator** — `php artisan make:action CreateUserAction` scaffolds action classes into `app/Actions`.

**Developer middleware** — `IsDeveloper` aborts with a 403 unless the authenticated user has `is_dev`.

**Model stats** — `HasStatsAfterEvents` records created and deleted counts through spatie/laravel-stats.

**String helpers** — `toSafeFileName()` strips characters that break downloads.

**Database backups** — `php artisan essentials:backup-database` dumps each database with `mysqldump`, gzips it and uploads it to `{directory}/{timestamp}/{database}.sql.gz` on S3. Backups older than the retention window are deleted after each run, and the result (size and path) is logged to the channel you pick. Set `DB_BACKUP_ENABLED=true` and the package schedules it for you.

```dotenv
DB_BACKUP_ENABLED=true
DB_BACKUP_FREQUENCY="30 20 * * *"
DB_BACKUP_TIMEZONE=UTC
DB_BACKUP_AWS_BUCKET=my-backups-bucket
DB_BACKUP_DIRECTORY=backups
DB_BACKUP_RETENTION_DAYS=7
DB_BACKUP_LOG_CHANNEL=discord
```

Credentials fall back to the app's `AWS_*` values. Set `DB_BACKUP_AWS_ACCESS_KEY_ID` and `DB_BACKUP_AWS_SECRET_ACCESS_KEY` to use a different key, or `DB_BACKUP_DISK` to use a disk from `config/filesystems.php`. `DB_BACKUP_CONNECTIONS` takes a comma separated list and defaults to the app's default connection. Pass `--connection=` to back up others, or `--skip-prune` to keep old backups. MySQL and MariaDB are supported, and the server needs `mysqldump` installed.

### Configuration

```bash
php artisan vendor:publish --tag=essentials-config
```

### Requirements

- Laravel 12+
- PHP 8.4 / 8.5

### License

essentials is open-sourced software licensed under the MIT license.

### Credits

Created by [kyledoesdev](https://github.com/kyledoesdev)
