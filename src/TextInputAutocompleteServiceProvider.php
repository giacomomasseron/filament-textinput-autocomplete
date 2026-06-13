<?php

namespace GiacomoMasseroni\TextInputAutocomplete;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TextInputAutocompleteServiceProvider extends PackageServiceProvider
{
    public static string $assetPackageName = 'giacomomasseron/filament-textinput-autocomplete';

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
            Css::make('autocomplete', __DIR__ . '/../dist/autocomplete.css'),
        ], package: static::$assetPackageName);
    }
}
