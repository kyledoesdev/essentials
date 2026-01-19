<?php

use Illuminate\Support\Facades\Http;
use Kyledoesdev\Essentials\Services\TimezoneService;

beforeEach(fn () => Http::preventStrayRequests());

describe('TimezoneService', function () {
    it('returns default timezone when ip is null', function () {
        request()->server->set('REMOTE_ADDR', null);

        $service = new TimezoneService();

        expect($service->detect())->toBe(config('app.timezone', 'UTC'));
    });

    it('returns default timezone for localhost ipv4', function () {
        request()->server->set('REMOTE_ADDR', '127.0.0.1');

        $service = new TimezoneService();

        expect($service->detect())->toBe(config('app.timezone', 'UTC'));
    });

    it('returns default timezone for localhost ipv6', function () {
        request()->server->set('REMOTE_ADDR', '::1');

        $service = new TimezoneService();

        expect($service->detect())->toBe(config('app.timezone', 'UTC'));
    });

    it('returns default timezone when in configured local environment', function () {
        config(['essentials.timezone.local_envs' => ['testing']]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        $service = new TimezoneService();

        expect($service->detect())->toBe(config('app.timezone', 'UTC'));
    });

    it('fetches timezone from ip-api for valid ip', function () {
        config(['essentials.timezone.local_envs' => []]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        Http::fake([
            'ip-api.com/*' => Http::response(['timezone' => 'America/New_York']),
        ]);

        $service = new TimezoneService();

        expect($service->detect())->toBe('America/New_York');
    });

    it('sanitizes Europe/Kiev to Europe/Kyiv', function () {
        config(['essentials.timezone.local_envs' => []]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        Http::fake([
            'ip-api.com/*' => Http::response(['timezone' => 'Europe/Kiev']),
        ]);

        $service = new TimezoneService();

        expect($service->detect())->toBe('Europe/Kyiv');
    });

    it('returns default timezone when api request fails', function () {
        config(['essentials.timezone.local_envs' => []]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        Http::fake([
            'ip-api.com/*' => Http::response(null, 500),
        ]);

        $service = new TimezoneService();

        expect($service->detect())->toBe(config('app.timezone', 'UTC'));
    });

    it('returns default timezone when api returns null timezone', function () {
        config(['essentials.timezone.local_envs' => []]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        Http::fake([
            'ip-api.com/*' => Http::response(['status' => 'fail']),
        ]);

        $service = new TimezoneService();

        expect($service->detect())->toBe(config('app.timezone', 'UTC'));
    });
});

describe('Timezone Helper Function', function () {
    it('returns timezone from service', function () {
        config(['essentials.timezone.local_envs' => []]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        Http::fake([
            'ip-api.com/*' => Http::response(['timezone' => 'America/Chicago']),
        ]);

        expect(timezone())->toBe('America/Chicago');
    });

    it('caches result via once', function () {
        config(['essentials.timezone.local_envs' => []]);
        request()->server->set('REMOTE_ADDR', '8.8.8.8');

        Http::fake([
            'ip-api.com/*' => Http::response(['timezone' => 'America/Denver']),
        ]);

        $first = timezone();
        $second = timezone();

        expect($first)->toBe($second);
        Http::assertSentCount(1);
    });
});