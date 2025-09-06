<?php

namespace Kyledoesdev\Essentials\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Carbon::macro('inUserTimezone', function () {
            $user = auth()->user();
            
            return $this->tz($user?->timezone ?? timezone());
        });
    }
}
