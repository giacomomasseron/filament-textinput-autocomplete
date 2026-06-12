<?php

use Giacomo\TextInputAutocomplete\Tests\Fixtures\AutocompleteTestComponent;
use Livewire\Livewire;

it('renders the static field markup in a Filament schema', function () {
    Livewire::test(AutocompleteTestComponent::class)
        ->assertOk()
        ->assertSee('fi-ac-wrapper', escape: false)
        ->assertSee('fi-ac-input', escape: false)
        ->assertSee('fi-ac-dropdown', escape: false)
        ->assertSee('x-load-src', escape: false);
});
