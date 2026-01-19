<?php

namespace Kyledoesdev\Essentials\Services;

use Illuminate\Support\Facades\Http;

final class TimezoneService
{
    public function detect(): string
    {
        $ip = request()->ip();

        if (!$ip || $this->inDevEnv($ip)) {
            return $this->default();
        }

        return $this->fetchTimezone($ip);
    }

    private function fetchTimezone(string $ip): string
    {
        $tz = rescue(fn () => Http::timeout(3)
            ->get("http://ip-api.com/json/{$ip}")
            ->json('timezone')
        );

        return $tz ? $this->sanitize($tz) : $this->default();
    }

    private function sanitize(string $timezone): string
    {
        return match ($timezone) {
            'Europe/Kiev' => 'Europe/Kyiv',
            default => $timezone,
        };
    }

    private function inDevEnv(string $ip): bool
    {
        return
            in_array(app()->environment(), config('essentials.timezone.local_envs', [])) ||
            in_array($ip, ['127.0.0.1', '::1']);
    }

    private function default(): string
    {
        return config('app.timezone', 'UTC');
    }
}