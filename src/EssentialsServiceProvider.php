<?php

namespace Kyledoesdev\Essentials;

use Kyledoesdev\Essentials\Commands\MakeActionCommand;
use Kyledoesdev\Essentials\Commands\PublishStubsCommand;
use Kyledoesdev\Essentials\Providers\MacroServiceProvider;
use Kyledoesdev\Essentials\Services\PublishStubsService;
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
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('essentials')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_users_table'
            ])
            ->hasCommands([
                MakeActionCommand::class,
                PublishStubsCommand::class,
            ]);
    }
}
