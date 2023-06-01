<?php

namespace Pepijnolivier\EloquentModelGenerator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Pepijnolivier\EloquentModelGenerator\Commands\EloquentModelGeneratorCommand;

class EloquentModelGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('eloquent-model-generator')
            ->hasConfigFile()
            ->hasCommand(EloquentModelGeneratorCommand::class);
    }
}
