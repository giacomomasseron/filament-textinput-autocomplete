<?php

use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

it('can be instantiated with a name', function () {
    $field = AutocompleteInput::make('country');

    expect($field)->toBeInstanceOf(AutocompleteInput::class)
        ->and($field->getName())->toBe('country');
});
