<?php

use Illuminate\Support\Facades\Process;
use Kyledoesdev\Essentials\Backups\Dumpers\MySqlDumper;
use Kyledoesdev\Essentials\Backups\Exceptions\BackupFailed;

$connection = [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'song_rank',
    'username' => 'forge',
    'password' => 'se"cret\\pass',
    'unix_socket' => '',
];

describe('MySqlDumper', function () use ($connection) {
    it('keeps the password out of the command', function () use ($connection) {
        $command = (new MySqlDumper('mysqldump'))->command($connection, '/tmp/creds.cnf', '/tmp/song_rank.sql');

        expect($command)->toBe([
            'mysqldump',
            '--defaults-extra-file=/tmp/creds.cnf',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--no-tablespaces',
            '--result-file=/tmp/song_rank.sql',
            'song_rank',
        ])->and(implode(' ', $command))->not->toContain('cret');
    });

    it('writes an escaped option file and skips empty values', function () use ($connection) {
        expect((new MySqlDumper)->optionFile($connection))->toBe(implode(PHP_EOL, [
            '[client]',
            'user="forge"',
            'password="se\"cret\\\\pass"',
            'host="127.0.0.1"',
            'port="3306"',
        ]).PHP_EOL);
    });

    it('runs mysqldump and removes the option file', function () use ($connection) {
        Process::fake();
        $destination = sys_get_temp_dir().'/essentials-dumper-test.sql';

        (new MySqlDumper('/usr/bin/mysqldump', 60))->dump($connection, $destination);

        Process::assertRan(fn ($process) => $process->command[0] === '/usr/bin/mysqldump'
            && $process->timeout === 60
            && in_array('song_rank', $process->command, true));

        expect(file_exists($destination.'.cnf'))->toBeFalse();
    });

    it('throws with the error output when mysqldump fails', function () use ($connection) {
        Process::fake(['*' => Process::result(errorOutput: 'Access denied for user', exitCode: 2)]);

        (new MySqlDumper)->dump($connection, sys_get_temp_dir().'/essentials-dumper-test.sql');
    })->throws(BackupFailed::class, 'Dumping [song_rank] failed: Access denied for user');
});
