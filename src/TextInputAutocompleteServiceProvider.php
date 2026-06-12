<?php

namespace Giacomo\TextInputAutocomplete;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TextInputAutocompleteServiceProvider extends PackageServiceProvider
{
    public static string $assetPackageName = 'giacomo/filament-textinput-autocomplete';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-textinput-autocomplete')
            ->hasViews('filament-textinput-autocomplete');
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make('autocomplete', __DIR__ . '/../dist/components/autocomplete.js'),
        ], package: static::$assetPackageName);
    }
}
