<?php

namespace Kyledoesdev\Essentials\Backups\Dumpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Kyledoesdev\Essentials\Backups\Contracts\Dumper;
use Kyledoesdev\Essentials\Backups\Exceptions\BackupFailed;

/**
 * Dumps MySQL and MariaDB databases with mysqldump. Credentials go through a
 * temporary option file so the password never shows up in the process list.
 */
final class MySqlDumper implements Dumper
{
    public function __construct(
        private readonly string $binary = 'mysqldump',
        private readonly int $timeout = 900,
    ) {}

    /**
     * @param  array<string, mixed>  $connection
     */
    public function dump(array $connection, string $destination): void
    {
        $credentials = $destination.'.cnf';

        File::put($credentials, $this->optionFile($connection));
        File::chmod($credentials, 0600);

        try {
            $result = Process::timeout($this->timeout)->run($this->command($connection, $credentials, $destination));

            if ($result->failed()) {
                throw BackupFailed::dumpFailed($connection['database'], $result->errorOutput() ?: $result->output());
            }
        } finally {
            File::delete($credentials);
        }
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return array<int, string>
     */
    public function command(array $connection, string $credentials, string $destination): array
    {
        return [
            $this->binary,
            "--defaults-extra-file={$credentials}",
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--no-tablespaces',
            "--result-file={$destination}",
            (string) $connection['database'],
        ];
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    public function optionFile(array $connection): string
    {
        return collect([
            'user' => $connection['username'] ?? null,
            'password' => $connection['password'] ?? null,
            'host' => $connection['host'] ?? null,
            'port' => $connection['port'] ?? null,
            'socket' => $connection['unix_socket'] ?? null,
        ])
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value, string $key): string => $key.'='.Str::wrap(
                Str::replace(['\\', '"'], ['\\\\', '\\"'], (string) $value),
                '"',
            ))
            ->prepend('[client]')
            ->push('')
            ->implode(PHP_EOL);
    }
}
