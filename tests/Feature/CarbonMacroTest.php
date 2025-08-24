<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

test('carbon macro inUserTimezone uses user timezone', function () {
    $user = (object) ['timezone' => 'America/New_York'];

    Auth::shouldReceive('user')
        ->once()
        ->andReturn($user);

    $date = Carbon::parse('2025-01-01 12:00:00')->inUserTimezone();

    expect($date->tzName)->toBe('America/New_York');
});

test('carbon macro inUserTimezone falls back to default timezone when no user is authenticated', function () {
    Auth::shouldReceive('user')
        ->once()
        ->andReturn(null);

    $date = Carbon::parse('2025-01-01 12:00:00')->inUserTimezone();

    expect($date->tzName)->toBe(config('app.timezone'));
});
