<?php

namespace Kyledoesdev\Essentials\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Carbon::macro('inUserTimezone', function () {
            $timezone = auth()->user()?->timezone ?? session()->get('____tz');

            if (is_null($timezone)) {
                $timezone = timezone();

                session()->put(['____tz' => $timezone]);
            }

            return $this->tz($timezone);
        });
    }
}
