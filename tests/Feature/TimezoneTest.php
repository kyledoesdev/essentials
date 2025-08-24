<?php

use Illuminate\Support\Facades\Http;

test('correct timezone returned based off of request ip', function () {
    app()['env'] = 'production';

    Http::fake([
        'http://ip-api.com/json/*' => Http::response([
            'timezone' => 'Europe/London',
        ], 200),
    ]);

    expect(timezone())->toBe('Europe/London');
});

test('correct timezone returned when geolocation service fails', function () {
    app()['env'] = 'production';

    Http::fake([
        'http://ip-api.com/json/*' => Http::response(null, 500),
    ]);

    expect(timezone())->toBe(config('app.timezone'));
});
