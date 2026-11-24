<?php

namespace Kyledoesdev\Essentials\Backups\Exceptions;

use Illuminate\Support\Str;
use RuntimeException;

final class BackupFailed extends RuntimeException
{
    public static function unsupportedDriver(string $connection, string $driver): self
    {
        return new self("The [{$connection}] connection uses the [{$driver}] driver, which database backups don't support yet.");
    }

    public static function missingConnection(string $connection): self
    {
        return new self("The [{$connection}] database connection isn't configured.");
    }

    public static function dumpFailed(string $database, string $output): self
    {
        return new self("Dumping [{$database}] failed: ".Str::trim($output));
    }

    public static function compressionFailed(string $path): self
    {
        return new self("Couldn't compress [{$path}].");
    }

    public static function uploadFailed(string $path): self
    {
        return new self("Couldn't upload the backup to [{$path}].");
    }
}
