<?php

namespace Kyledoesdev\Essentials\Providers;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Carbon::macro('inUserTimezone', function () {
            /** @var Carbon $this */
            /** @var ?Authenticatable $user */
            $user = auth()->user();
            
            return $this->tz($user?->timezone ?? timezone());
        });
    }
}
