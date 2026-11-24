<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Kyledoesdev\Essentials\Services\TimezoneService;

beforeEach(function () {
    Http::preventStrayRequests();

    config(['essentials.timezone.local_envs' => []]);

    request()->server->set('REMOTE_ADDR', '8.8.8.8');
});

describe('TimezoneService', function () {
    it('returns default timezone when ip is null', function () {
        request()->server->set('REMOTE_ADDR', null);

        expect((new TimezoneService)->detect())->toBe('UTC');

        Http::assertNothingSent();
    });

    it('returns default timezone for localhost ipv4', function () {
        request()->server->set('REMOTE_ADDR', '127.0.0.1');

        expect((new TimezoneService)->detect())->toBe('UTC');

        Http::assertNothingSent();
    });

    it('returns default timezone for localhost ipv6', function () {
        request()->server->set('REMOTE_ADDR', '::1');

        expect((new TimezoneService)->detect())->toBe('UTC');

        Http::assertNothingSent();
    });

    it('returns default timezone when in configured local environment', function () {
        config(['essentials.timezone.local_envs' => ['testing']]);

        expect((new TimezoneService)->detect())->toBe('UTC');

        Http::assertNothingSent();
    });

    it('fetches timezone from ip-api for valid ip', function () {
        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/New_York'])]);

        expect((new TimezoneService)->detect())->toBe('America/New_York');

        Http::assertSentCount(1);
    });

    it('sanitizes Europe/Kiev to Europe/Kyiv', function () {
        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'Europe/Kiev'])]);

        expect((new TimezoneService)->detect())->toBe('Europe/Kyiv');
    });

    it('returns default timezone when api request fails', function () {
        Http::fake(['ip-api.com/*' => Http::response(null, 500)]);

        expect((new TimezoneService)->detect())->toBe('UTC');

        // The lookup must actually have been attempted, otherwise this
        // would pass even with the http call removed entirely.
        Http::assertSentCount(1);
    });

    it('returns default timezone when api returns null timezone', function () {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'fail'])]);

        expect((new TimezoneService)->detect())->toBe('UTC');

        Http::assertSentCount(1);
    });

    it('returns default timezone when api returns a non string timezone', function () {
        Http::fake(['ip-api.com/*' => Http::response(['timezone' => ['America/New_York']])]);

        expect((new TimezoneService)->detect())->toBe('UTC');
    });

    it('returns default timezone when api returns an unknown identifier', function () {
        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'Mars/Olympus_Mons'])]);

        expect((new TimezoneService)->detect())->toBe('UTC');
    });
});

describe('TimezoneService user timezone', function () {
    it('prefers the authenticated user timezone over an ip lookup', function () {
        actingAsUserWithTimezone('Australia/Sydney');

        expect((new TimezoneService)->detect())->toBe('Australia/Sydney');

        Http::assertNothingSent();
    });

    it('accepts a legacy timezone identifier stored on the user', function () {
        actingAsUserWithTimezone('US/Eastern');

        expect((new TimezoneService)->detect())->toBeIn(['US/Eastern', 'America/New_York']);

        Http::assertNothingSent();
    });

    it('falls through to the ip lookup when the user timezone is invalid', function () {
        actingAsUserWithTimezone('Not/AZone');

        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/New_York'])]);

        expect((new TimezoneService)->detect())->toBe('America/New_York');
    });

    it('falls through to the ip lookup when the user has no timezone', function () {
        actingAsUserWithTimezone(null);

        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/New_York'])]);

        expect((new TimezoneService)->detect())->toBe('America/New_York');
    });
});

describe('TimezoneService caching', function () {
    it('caches a successful lookup against the ip address', function () {
        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/New_York'])]);

        expect((new TimezoneService)->detect())->toBe('America/New_York');
        expect((new TimezoneService)->detect())->toBe('America/New_York');

        Http::assertSentCount(1);
        expect(Cache::get('essentials:tz:8.8.8.8'))->toBe('America/New_York');
    });

    it('caches a failed lookup so the api is not hammered', function () {
        Http::fake(['ip-api.com/*' => Http::response(null, 500)]);

        expect((new TimezoneService)->detect())->toBe('UTC');
        expect((new TimezoneService)->detect())->toBe('UTC');

        Http::assertSentCount(1);
    });

    it('does not reuse a cached timezone for a different ip', function () {
        Cache::put('essentials:tz:8.8.8.8', 'America/New_York', 60);
        request()->server->set('REMOTE_ADDR', '1.1.1.1');

        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'Europe/Paris'])]);

        expect((new TimezoneService)->detect())->toBe('Europe/Paris');
    });

    it('honors the configured cache ttls', function () {
        config([
            'essentials.timezone.ttl' => 3600,
            'essentials.timezone.retry_after' => 60,
        ]);

        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/New_York'])]);

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once()->with('essentials:tz:8.8.8.8', 'America/New_York', 3600);

        expect((new TimezoneService)->detect())->toBe('America/New_York');
    });
});

describe('Timezone Helper Function', function () {
    it('returns timezone from service', function () {
        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/Chicago'])]);

        expect(timezone())->toBe('America/Chicago');
    });

    it('caches result via once', function () {
        Http::fake(['ip-api.com/*' => Http::response(['timezone' => 'America/Denver'])]);

        expect(timezone())->toBe(timezone());

        Http::assertSentCount(1);
    });
});

/** Stub the authenticated user without needing a real User model. */
function actingAsUserWithTimezone(?string $timezone): void
{
    Auth::shouldReceive('user')->andReturn(
        is_null($timezone) ? null : (object) ['timezone' => $timezone]
    );
}
