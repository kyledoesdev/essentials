# kyledoesdev - Laravel Essentials

Essential utilities for my Laravel projects.

## Installation

```bash
composer require kyledoesdev/essentials
```

## Features

### Global Timezone Helper

```php
timezone() // Returns the timezone of the current request()->ip()
```

### Carbon Macro

```php
Carbon::parse($created_at)->inUserTimezone() // Converts carbon instance to user's timezone
```

### Action Class Generator

```bash
php artisan make:action CreateUserAction
```

Generates action classes in `app/Actions` directory.

## Requirements

- Laravel 11+
- PHP 8.4+

## License
MIT license.