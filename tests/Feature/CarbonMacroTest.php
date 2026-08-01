<?php

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Http::preventStrayRequests());

describe('Carbon Macro Helpers', function () {
    test('inUserTimezone uses user timezone', function () {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object) ['timezone' => 'America/New_York']);

        $date = Carbon::parse('2025-01-01 12:00:00')->inUserTimezone();

        expect($date->tzName)->toBe('America/New_York');
    });

    test('inUserTimezone falls back to default timezone when no user is authenticated', function () {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn(null);

        $date = Carbon::parse('2025-01-01 12:00:00')->inUserTimezone();

        expect($date->tzName)->toBe(config('app.timezone'));
    });

    test('inUserTimezone uses the ip detected timezone when no user is authenticated', function () {
        config(['essentials.timezone.local_envs' => []]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        Auth::shouldReceive('user')->once()->andReturn(null);

        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/Chicago'])]);

        $date = Carbon::parse('2025-01-01 12:00:00')->inUserTimezone();

        expect($date->tzName)->toBe('America/Chicago');
    });

    test('inUserTimezone is available on CarbonImmutable', function () {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object) ['timezone' => 'America/New_York']);

        $date = CarbonImmutable::parse('2025-01-01 12:00:00')->inUserTimezone();

        expect($date)->toBeInstanceOf(CarbonImmutable::class)
            ->and($date->tzName)->toBe('America/New_York');
    });

    test('inUserTimezone shifts the underlying instant rather than relabelling it', function () {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object) ['timezone' => 'America/New_York']);

        $date = Carbon::parse('2025-01-01 12:00:00', 'UTC')->inUserTimezone();

        expect($date->format('Y-m-d H:i:s'))->toBe('2025-01-01 07:00:00')
            ->and($date->utc()->format('H:i:s'))->toBe('12:00:00');
    });
});
