<?php

use Illuminate\Support\Facades\Http;

if (! function_exists('timezone')) {
    /**
     * Get the timezone based on the current request's IP address.
     */
    function timezone(): string
    {
        $tz = config('app.timezone', 'UTC');

        if (in_array(app()->environment(), config('essentials.timezone.local_envs'))) {
            return $tz;
        }

        $response = Http::timeout(3)
            ->retry(1, 200)
            ->get('http://ip-api.com/json/'.request()->ip());

        return $response->successful()
            ? $response->json('timezone', $tz)
            : $tz;
    }
}

if (! function_exists('zuck')) {
    /**
     * Get the user packet data
     */
    function zuck(): array
    {
        $response = Http::timeout(3)
            ->retry(1, 200)
            ->get('http://ip-api.com/json/'.request()->ip());

        return $response->successful()
            ? $response->json()
            : [];
    }
}
