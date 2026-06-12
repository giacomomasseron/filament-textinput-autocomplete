<?php

use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

it('returns an empty array when no static options are set', function () {
    expect(AutocompleteInput::make('q')->getOptionsForJs())->toBe([]);
});

it('maps options to value, label, html and searchable keys', function () {
    $field = AutocompleteInput::make('q')
        ->options([
            ['value' => 1, 'label' => 'Spain', 'description' => 'Country'],
        ])
        ->searchKeys(['label', 'description'])
        ->itemView(fn (array $item) => "<b>{$item['label']}</b>");

    expect($field->getOptionsForJs())->toBe([
        [
            'value' => 1,
            'label' => 'Spain',
            'html' => '<b>Spain</b>',
            'keys' => ['spain', 'country'],
        ],
    ]);
});
