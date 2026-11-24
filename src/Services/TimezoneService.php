<?php

namespace Kyledoesdev\Essentials\Services;

use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use IntlTimeZone;

final class TimezoneService
{
    private const CACHE_PREFIX = 'essentials:tz:';

    public function detect(): string
    {
        return $this->normalize(Auth::user()?->timezone)
            ?? $this->fromIpAddress()
            ?? $this->fallback();
    }

    private function fromIpAddress(): ?string
    {
        $ip = request()->ip();

        if (! $this->canLookup($ip)) {
            return null;
        }

        $key = self::CACHE_PREFIX.$ip;
        $cached = Cache::get($key);

        if ($cached) {
            return $cached;
        }

        $timezone = $this->normalize($this->lookup($ip));

        Cache::put($key, $timezone ?? $this->fallback(), $this->ttlFor($timezone));

        return $timezone;
    }

    private function lookup(string $ip): ?string
    {
        $timezone = rescue(fn () => Http::timeout(3)
            ->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,timezone'])
            ->json('timezone')
        );

        return is_string($timezone) ? $timezone : null;
    }

    private function canLookup(?string $ip): bool
    {
        return filled($ip)
            && ! in_array($ip, ['127.0.0.1', '::1'], true)
            && ! in_array(app()->environment(), (array) config('essentials.timezone.local_envs', []), true);
    }

    private function ttlFor(?string $timezone): int
    {
        return is_null($timezone)
            ? (int) config('essentials.timezone.retry_after', 300)
            : (int) config('essentials.timezone.ttl', 86400);
    }

    private function normalize(?string $timezone): ?string
    {
        $timezone = match ($timezone) {
            'Europe/Kiev' => 'Europe/Kyiv',
            default => $timezone,
        };

        if (blank($timezone)) {
            return null;
        }

        return $this->identifier($timezone) ?? $this->identifier($this->canonical($timezone));
    }

    private function identifier(?string $timezone): ?string
    {
        if (blank($timezone)) {
            return null;
        }

        try {
            return (new DateTimeZone($timezone))->getName();
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Legacy names like US/Eastern only work when the server has the
     * tzdata-legacy package, which Ubuntu no longer installs by default.
     * ICU ships its own data, so it can map them to a current name.
     */
    private function canonical(string $timezone): ?string
    {
        if (! class_exists(IntlTimeZone::class)) {
            return null;
        }

        $canonical = IntlTimeZone::getCanonicalID($timezone);

        return is_string($canonical) && $canonical !== $timezone ? $canonical : null;
    }

    private function fallback(): string
    {
        return config('app.timezone', 'UTC');
    }
}
