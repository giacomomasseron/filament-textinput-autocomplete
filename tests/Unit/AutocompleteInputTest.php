<?php

use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

it('can be instantiated with a name', function () {
    $field = AutocompleteInput::make('country');

    expect($field)->toBeInstanceOf(AutocompleteInput::class)
        ->and($field->getName())->toBe('country');
});

it('stores static options', function () {
    $options = [['value' => 1, 'label' => 'Spain']];
    $field = AutocompleteInput::make('country')->options($options);

    expect($field->getOptions())->toBe($options);
});

it('stores search keys with a sensible default', function () {
    $field = AutocompleteInput::make('country');
    expect($field->getSearchKeys())->toBe(['label']);

    $field->searchKeys(['label', 'description']);
    expect($field->getSearchKeys())->toBe(['label', 'description']);
});

it('stores numeric config with defaults', function () {
    $field = AutocompleteInput::make('country');

    expect($field->getMinChars())->toBe(1)
        ->and($field->getSearchDebounce())->toBe(300)
        ->and($field->getMaxResults())->toBe(10);

    $field->minChars(3)->searchDebounce(500)->maxResults(5);

    expect($field->getMinChars())->toBe(3)
        ->and($field->getSearchDebounce())->toBe(500)
        ->and($field->getMaxResults())->toBe(5);
});

it('stores option label and value keys with defaults', function () {
    $field = AutocompleteInput::make('country');
    expect($field->getOptionLabel())->toBe('label')
        ->and($field->getOptionValue())->toBe('value');

    $field->optionLabel('name')->optionValue('id');
    expect($field->getOptionLabel())->toBe('name')
        ->and($field->getOptionValue())->toBe('id');
});

it('stores messages with defaults', function () {
    $field = AutocompleteInput::make('country');
    expect($field->getNoResultsMessage())->toBe('No results found')
        ->and($field->getLoadingMessage())->toBe('Loading...');

    $field->noResultsMessage('Nothing')->loadingMessage('Wait');
    expect($field->getNoResultsMessage())->toBe('Nothing')
        ->and($field->getLoadingMessage())->toBe('Wait');
});

it('reports whether server search is configured', function () {
    $field = AutocompleteInput::make('country');
    expect($field->hasServerSearch())->toBeFalse();

    $field->getSearchResultsUsing(fn (string $search) => []);
    expect($field->hasServerSearch())->toBeTrue();
});
