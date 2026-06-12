<?php

namespace Giacomo\TextInputAutocomplete;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TextInputAutocompleteServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-textinput-autocomplete')
            ->hasViews('filament-textinput-autocomplete');
    }
}
