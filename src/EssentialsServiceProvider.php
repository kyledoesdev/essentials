<?php

namespace Kyledoesdev\Essentials;

use Illuminate\Console\Scheduling\Schedule;
use Kyledoesdev\Essentials\Commands\BackupDatabaseCommand;
use Kyledoesdev\Essentials\Commands\MakeActionCommand;
use Kyledoesdev\Essentials\Providers\MacroServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EssentialsServiceProvider extends PackageServiceProvider
{
    public function register()
    {
        parent::register();

        $this->app->register(MacroServiceProvider::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('essentials')
            ->hasConfigFile()
            ->hasMigration('create_stats_events_tables')
            ->runsMigrations()
            ->hasCommands([
                BackupDatabaseCommand::class,
                MakeActionCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        $this->scheduleDatabaseBackups();
    }

    private function scheduleDatabaseBackups(): void
    {
        if (! config('essentials.db_backups.enabled')) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(BackupDatabaseCommand::class)
                ->cron((string) config('essentials.db_backups.frequency'))
                ->timezone((string) config('essentials.db_backups.timezone'))
                ->withoutOverlapping();
        });
    }
}
