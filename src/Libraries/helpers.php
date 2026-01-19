<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Kyledoesdev\Essentials\Services\TimezoneService;

if (! function_exists('timezone')) {
    /**
     * Get the timezone based on the current request's IP address.
     */
    function timezone(): string
    {
        return once(fn () => app(TimezoneService::class)->detect());
    }
}

if (! function_exists('zuck')) {
    /**
     * Get the user packet data
     */
    function zuck(): array
    {
        return rescue(fn () => Http::timeout(3)
            ->get("http://ip-api.com/json/".  request()->ip())
            ->json()
        ) ?? [];
    }
}

if (! function_exists('toSafeFileName')) {
    /**
     * convert a string to a safe string for file download string
     */
    function toSafeFileName(string $string): string
    {
        return Str::of($string)
            ->ascii()
            ->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-')
            ->trim()
            ->toString();
    }
}
