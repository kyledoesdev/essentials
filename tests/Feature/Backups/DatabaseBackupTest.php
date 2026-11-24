<?php

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kyledoesdev\Essentials\Backups\Contracts\Dumper;
use Kyledoesdev\Essentials\Backups\DatabaseBackup;
use Kyledoesdev\Essentials\Backups\DumperFactory;
use Kyledoesdev\Essentials\Commands\BackupDatabaseCommand;
use Kyledoesdev\Essentials\EssentialsServiceProvider;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-23 20:30:01', 'UTC'));

    config([
        'database.connections.app' => ['driver' => 'mysql', 'database' => 'song_rank'],
        'essentials.db_backups.connections' => ['app'],
        'essentials.db_backups.disk' => 'backups',
        'essentials.db_backups.directory' => 'backups',
        'essentials.db_backups.folder_format' => 'YmdHis',
        'essentials.db_backups.timezone' => 'UTC',
        'essentials.db_backups.retention_days' => 7,
        'essentials.db_backups.log_channel' => null,
        'essentials.db_backups.temporary_directory' => sys_get_temp_dir().'/essentials-backups-test',
    ]);

    Storage::fake('backups');

    app()->instance(DumperFactory::class, new class extends DumperFactory
    {
        public function for(string $connectionName, string $driver): Dumper
        {
            return new class implements Dumper
            {
                public function dump(array $connection, string $destination): void
                {
                    file_put_contents($destination, "CREATE TABLE users (id int);\n-- {$connection['database']}\n");
                }
            };
        }
    });
});

afterEach(fn () => CarbonImmutable::setTestNow());

describe('Database backups', function () {
    it('uploads a gzipped dump into a timestamped folder', function () {
        $exitCode = Artisan::call(BackupDatabaseCommand::class);

        expect($exitCode)->toBe(Command::SUCCESS);

        $path = 'backups/20261123203001/song_rank.sql.gz';

        Storage::disk('backups')->assertExists($path);

        expect(gzdecode(Storage::disk('backups')->get($path)))
            ->toContain('CREATE TABLE users')
            ->toContain('-- song_rank');
    });

    it('backs up every configured connection into the same folder', function () {
        config([
            'database.connections.blog' => ['driver' => 'mysql', 'database' => 'kyledoesdev'],
            'essentials.db_backups.connections' => ['app', 'blog'],
        ]);

        Artisan::call(BackupDatabaseCommand::class);

        Storage::disk('backups')->assertExists('backups/20261123203001/song_rank.sql.gz');
        Storage::disk('backups')->assertExists('backups/20261123203001/kyledoesdev.sql.gz');
    });

    it('falls back to the default connection', function () {
        config([
            'database.default' => 'app',
            'essentials.db_backups.connections' => [],
        ]);

        Artisan::call(BackupDatabaseCommand::class);

        Storage::disk('backups')->assertExists('backups/20261123203001/song_rank.sql.gz');
    });

    it('uses the folder format and directory from config', function () {
        config([
            'essentials.db_backups.directory' => 'db/',
            'essentials.db_backups.folder_format' => 'Y-m-d-His',
        ]);

        Artisan::call(BackupDatabaseCommand::class);

        Storage::disk('backups')->assertExists('db/2026-11-23-203001/song_rank.sql.gz');
    });

    it('removes its own backups older than the retention window', function () {
        $disk = Storage::disk('backups');
        $disk->put('backups/20261110203001/song_rank.sql.gz', 'old');
        $disk->put('backups/20261110203001/kyledoesdev.sql.gz', 'other app');
        $disk->put('backups/20261115203001/song_rank.sql.gz', 'old');
        $disk->put('backups/20261120203001/song_rank.sql.gz', 'recent');
        $disk->put('backups/not-a-backup/song_rank.sql.gz', 'unrelated');

        Artisan::call(BackupDatabaseCommand::class);

        $disk->assertMissing('backups/20261110203001/song_rank.sql.gz');
        $disk->assertMissing('backups/20261115203001/song_rank.sql.gz');
        $disk->assertExists('backups/20261110203001/kyledoesdev.sql.gz');
        $disk->assertExists('backups/20261120203001/song_rank.sql.gz');
        $disk->assertExists('backups/not-a-backup/song_rank.sql.gz');

        expect($disk->directories('backups'))->not->toContain('backups/20261115203001');
    });

    it('keeps everything when retention is zero or pruning is skipped', function (array $config, array $options) {
        config($config);
        Storage::disk('backups')->put('backups/20250101000000/song_rank.sql.gz', 'old');

        Artisan::call(BackupDatabaseCommand::class, $options);

        Storage::disk('backups')->assertExists('backups/20250101000000/song_rank.sql.gz');
    })->with([
        'retention of zero' => [['essentials.db_backups.retention_days' => 0], []],
        'skip prune option' => [[], ['--skip-prune' => true]],
    ]);

    it('logs the size and path to the configured channel', function () {
        config([
            'logging.channels.backups' => ['driver' => 'single', 'path' => storage_path('logs/backups.log')],
            'essentials.db_backups.log_channel' => 'backups',
        ]);
        Storage::disk('backups')->put('backups/20261101000000/song_rank.sql.gz', 'old');

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->withArgs(fn (string $message) => str_contains($message, 'Database backup complete for song_rank (')
            && str_contains($message, 'saved to backups/20261123203001/song_rank.sql.gz.')
            && str_contains($message, 'Removed 1 backup older than 7 days.'));

        Log::shouldReceive('channel')->once()->with('backups')->andReturn($logger);

        Artisan::call(BackupDatabaseCommand::class);
    });

    it('logs an error and fails when a backup fails', function () {
        config(['database.connections.app.driver' => 'sqlsrv']);
        app()->forgetInstance(DumperFactory::class);
        $this->withExceptionHandling();

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->withArgs(fn (string $message) => str_contains($message, 'Database backup failed for song_rank')
            && str_contains($message, '[sqlsrv] driver'));

        Log::shouldReceive('channel')->once()->with(null)->andReturn($logger);
        Log::shouldReceive('error')->zeroOrMoreTimes();

        expect(Artisan::call(BackupDatabaseCommand::class))->toBe(Command::FAILURE);

        Storage::disk('backups')->assertDirectoryEmpty('backups');
    });

    it('cleans up its temporary files', function () {
        Artisan::call(BackupDatabaseCommand::class);

        expect(glob(sys_get_temp_dir().'/essentials-backups-test/*'))->toBe([]);
    });

    it('builds an s3 disk from config when no disk is named', function () {
        config([
            'essentials.db_backups.disk' => null,
            'essentials.db_backups.s3' => ['key' => 'key', 'secret' => 'secret', 'region' => 'us-east-1', 'bucket' => 'kyledoesdev-forge-bucket', 'endpoint' => null],
        ]);

        $disk = app(DatabaseBackup::class)->disk();

        expect($disk->getConfig())
            ->toMatchArray(['driver' => 's3', 'bucket' => 'kyledoesdev-forge-bucket', 'throw' => true])
            ->not->toHaveKey('endpoint');
    });
});

describe('Database backup schedule', function () {
    it('schedules the command from config when enabled', function () {
        config([
            'essentials.db_backups.enabled' => true,
            'essentials.db_backups.frequency' => '30 20 * * *',
            'essentials.db_backups.timezone' => 'America/New_York',
        ]);

        (new EssentialsServiceProvider(app()))->packageBooted();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'essentials:backup-database'));

        expect($event)->not->toBeNull()
            ->and($event->expression)->toBe('30 20 * * *')
            ->and($event->timezone)->toBe('America/New_York')
            ->and($event->withoutOverlapping)->toBeTrue();
    });

    it('does not schedule anything when disabled', function () {
        config(['essentials.db_backups.enabled' => false]);

        (new EssentialsServiceProvider(app()))->packageBooted();

        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'essentials:backup-database'));

        expect($events)->toBeEmpty();
    });
});
