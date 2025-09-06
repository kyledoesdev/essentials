<?php

namespace Kyledoesdev\Essentials;

use Kyledoesdev\Essentials\Commands\MakeActionCommand;
use Kyledoesdev\Essentials\Providers\MacroServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EssentialsServiceProvider extends PackageServiceProvider
{
    public function register()
    {
        parent::register();

        $this->app->register(MacroServiceProvider::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('essentials')
            ->hasConfigFile()
            ->hasCommands([
                MakeActionCommand::class,
            ]);
    }
}
