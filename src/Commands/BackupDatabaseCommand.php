<?php

namespace Kyledoesdev\Essentials\Commands;

use Illuminate\Console\Command;
use Kyledoesdev\Essentials\Backups\BackupResult;
use Kyledoesdev\Essentials\Backups\DatabaseBackup;

final class BackupDatabaseCommand extends Command
{
    protected $signature = 'essentials:backup-database
                            {--connection=* : The connections to back up (defaults to the configured ones)}
                            {--skip-prune : Keep backups older than the retention window}';

    protected $description = 'Dump, gzip and upload the database, then remove old backups';

    public function handle(DatabaseBackup $backup): int
    {
        $results = $backup->run(
            connections: (array) $this->option('connection'),
            prune: ! $this->option('skip-prune'),
        );

        $results->each(function (BackupResult $result): void {
            if ($result->successful()) {
                $this->components->info("Backed up {$result->database} ({$result->size()}) to {$result->path}.");

                return;
            }

            $this->components->error("Backing up {$result->database} failed: {$result->exception?->getMessage()}");
        });

        return $results->every(fn (BackupResult $result): bool => $result->successful())
            ? self::SUCCESS
            : self::FAILURE;
    }
}
