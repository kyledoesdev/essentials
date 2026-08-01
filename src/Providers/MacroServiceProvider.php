<?php

namespace Kyledoesdev\Essentials\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootCarbonMacros();
    }

    private function bootCarbonMacros(): void
    {
        Carbon::macro('inUserTimezone', fn () => $this->tz(timezone()));
        CarbonImmutable::macro('inUserTimezone', fn () => $this->tz(timezone()));
    }
}
