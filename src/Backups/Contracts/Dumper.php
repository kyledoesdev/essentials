<?php

namespace Kyledoesdev\Essentials\Backups\Contracts;

interface Dumper
{
    /**
     * Write a plain SQL dump of the connection's database to the destination path.
     *
     * @param  array<string, mixed>  $connection  The connection's config from config/database.php.
     */
    public function dump(array $connection, string $destination): void;
}
