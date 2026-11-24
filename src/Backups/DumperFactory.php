<?php

namespace Kyledoesdev\Essentials\Backups;

use Kyledoesdev\Essentials\Backups\Contracts\Dumper;
use Kyledoesdev\Essentials\Backups\Dumpers\MySqlDumper;
use Kyledoesdev\Essentials\Backups\Exceptions\BackupFailed;

class DumperFactory
{
    public function for(string $connectionName, string $driver): Dumper
    {
        return match ($driver) {
            'mysql', 'mariadb' => new MySqlDumper(
                binary: (string) config('essentials.db_backups.mysqldump.binary', 'mysqldump'),
                timeout: (int) config('essentials.db_backups.mysqldump.timeout', 900),
            ),
            default => throw BackupFailed::unsupportedDriver($connectionName, $driver),
        };
    }
}
