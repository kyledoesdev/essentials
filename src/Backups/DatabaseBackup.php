<?php

namespace Kyledoesdev\Essentials\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kyledoesdev\Essentials\Backups\Exceptions\BackupFailed;
use Throwable;

final class DatabaseBackup
{
    private const int CHUNK_SIZE = 1024 * 1024;

    public function __construct(private readonly DumperFactory $dumpers) {}

    /**
     * Back up each connection to {directory}/{timestamp}/{database}.sql.gz,
     * then delete that database's backups older than the retention window.
     *
     * @param  array<int, string>  $connections  Defaults to the configured connections, or the default connection.
     * @return Collection<int, BackupResult>
     */
    public function run(array $connections = [], bool $prune = true): Collection
    {
        $connections = $connections ?: $this->config('connections') ?: [config('database.default')];
        $folder = $this->now()->format($this->config('folder_format'));
        $disk = $this->disk();

        return collect($connections)
            ->map(fn (string $connection): BackupResult => $this->backup($disk, $connection, $folder, $prune))
            ->each(fn (BackupResult $result) => $this->log($result));
    }

    /**
     * Delete this database's backups in folders older than the retention window.
     * Folders that don't match the folder format are never touched.
     */
    public function prune(Filesystem $disk, string $database): int
    {
        $days = (int) $this->config('retention_days');

        if ($days <= 0) {
            return 0;
        }

        $cutoff = $this->now()->subDays($days);
        $pruned = 0;

        foreach ($disk->directories($this->directory()) as $folder) {
            $createdAt = $this->parseFolder(Str::afterLast($folder, '/'));

            if ($createdAt === null || $createdAt->greaterThanOrEqualTo($cutoff)) {
                continue;
            }

            $file = "{$folder}/{$database}.sql.gz";

            if ($disk->exists($file)) {
                $disk->delete($file);
                $pruned++;
            }

            if ($disk->allFiles($folder) === []) {
                $disk->deleteDirectory($folder);
            }
        }

        return $pruned;
    }

    public function disk(): Filesystem
    {
        $disk = $this->config('disk');

        if (filled($disk)) {
            return Storage::disk($disk);
        }

        return Storage::build(array_merge(
            ['driver' => 's3', 'throw' => true],
            array_filter((array) $this->config('s3'), fn (mixed $value): bool => $value !== null),
        ));
    }

    private function backup(Filesystem $disk, string $connection, string $folder, bool $prune): BackupResult
    {
        $config = config("database.connections.{$connection}");
        $database = (string) ($config['database'] ?? $connection);

        $workspace = Str::of((string) $this->config('temporary_directory'))
            ->rtrim('/')
            ->append('/backup-', Str::random(16))
            ->toString();
        $sql = "{$workspace}/{$database}.sql";
        $gzip = "{$sql}.gz";

        try {
            if (! is_array($config)) {
                throw BackupFailed::missingConnection($connection);
            }

            File::ensureDirectoryExists($workspace, 0700);

            $this->dumpers->for($connection, (string) $config['driver'])->dump($config, $sql);
            $this->compress($sql, $gzip);

            $path = "{$this->directory()}/{$folder}/{$database}.sql.gz";
            $bytes = (int) filesize($gzip);

            $this->upload($disk, $gzip, $path);

            return new BackupResult(
                connection: $connection,
                database: $database,
                path: $path,
                bytes: $bytes,
                pruned: $prune ? $this->prune($disk, $database) : 0,
            );
        } catch (Throwable $exception) {
            report($exception);

            return new BackupResult(connection: $connection, database: $database, exception: $exception);
        } finally {
            File::deleteDirectory($workspace);
        }
    }

    private function compress(string $source, string $destination): void
    {
        $input = fopen($source, 'rb');
        $output = gzopen($destination, 'wb6');

        if ($input === false || $output === false) {
            throw BackupFailed::compressionFailed($source);
        }

        try {
            while (! feof($input)) {
                gzwrite($output, (string) fread($input, self::CHUNK_SIZE));
            }
        } finally {
            fclose($input);
            gzclose($output);
        }
    }

    private function upload(Filesystem $disk, string $source, string $path): void
    {
        $stream = fopen($source, 'rb');

        try {
            if ($stream === false || ! $disk->writeStream($path, $stream)) {
                throw BackupFailed::uploadFailed($path);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function log(BackupResult $result): void
    {
        $logger = Log::channel($this->config('log_channel') ?: null);

        if (! $result->successful()) {
            $logger->error("Database backup failed for {$result->database}: {$result->exception?->getMessage()}");

            return;
        }

        $message = "Database backup complete for {$result->database} ({$result->size()}), saved to {$result->path}.";

        if ($result->pruned > 0) {
            $message .= " Removed {$result->pruned} ".Str::plural('backup', $result->pruned)
                .' older than '.$this->config('retention_days').' days.';
        }

        $logger->info($message);
    }

    private function parseFolder(string $name): ?CarbonImmutable
    {
        $format = (string) $this->config('folder_format');

        try {
            $date = CarbonImmutable::createFromFormat('!'.$format, $name, $this->config('timezone'));
        } catch (Throwable) {
            return null;
        }

        return $date && $date->format($format) === $name ? $date : null;
    }

    private function directory(): string
    {
        return Str::trim((string) $this->config('directory'), '/');
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->config('timezone'));
    }

    private function config(string $key): mixed
    {
        return config("essentials.db_backups.{$key}");
    }
}
