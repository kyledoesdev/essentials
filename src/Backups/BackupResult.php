<?php

namespace Kyledoesdev\Essentials\Backups;

use Kyledoesdev\Essentials\Backups\Enums\SizeUnit;
use Throwable;

final readonly class BackupResult
{
    private const int BYTES_PER_UNIT = 1024;

    public function __construct(
        public string $connection,
        public string $database,
        public ?string $path = null,
        public int $bytes = 0,
        public int $pruned = 0,
        public ?Throwable $exception = null,
    ) {}

    public function successful(): bool
    {
        return $this->exception === null;
    }

    public function size(): string
    {
        $units = SizeUnit::cases();
        $size = (float) $this->bytes;
        $unit = 0;

        while ($size >= self::BYTES_PER_UNIT && $unit < count($units) - 1) {
            $size /= self::BYTES_PER_UNIT;
            $unit++;
        }

        return round($size, 1).' '.$units[$unit]->value;
    }
}
