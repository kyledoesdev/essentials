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
