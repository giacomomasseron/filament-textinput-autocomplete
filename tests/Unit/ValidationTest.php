<?php

use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

it('throws when both static and server sources are set', function () {
    $field = AutocompleteInput::make('q')
        ->options([['value' => 1, 'label' => 'A']])
        ->getSearchResultsUsing(fn (string $s) => []);

    $field->getOptionsForJs();
})->throws(InvalidArgumentException::class, 'both');

it('throws when maxResults is below one', function () {
    $field = AutocompleteInput::make('q')
        ->options([['value' => 1, 'label' => 'A']])
        ->maxResults(0);

    $field->getOptionsForJs();
})->throws(InvalidArgumentException::class, 'maxResults');
