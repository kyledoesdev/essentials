<?php

use Kyledoesdev\Essentials\Backups\BackupResult;

describe('BackupResult', function () {
    it('formats the size with the largest fitting unit', function (int $bytes, string $expected) {
        $result = new BackupResult(connection: 'mysql', database: 'song_rank', bytes: $bytes);

        expect($result->size())->toBe($expected);
    })->with([
        'bytes' => [512, '512 B'],
        'kilobytes' => [3 * 1024, '3 KB'],
        'megabytes' => [(int) (68.4 * 1024 * 1024), '68.4 MB'],
        'gigabytes' => [5 * 1024 ** 3, '5 GB'],
        'caps at terabytes' => [2048 * 1024 ** 4, '2048 TB'],
    ]);

    it('is successful only without an exception', function () {
        expect(new BackupResult('mysql', 'song_rank'))->successful()->toBeTrue()
            ->and(new BackupResult('mysql', 'song_rank', exception: new RuntimeException))->successful()->toBeFalse();
    });
});
