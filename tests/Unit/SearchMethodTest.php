<?php

use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

it('returns mapped, html-rendered, capped results', function () {
    $field = AutocompleteInput::make('q')
        ->maxResults(2)
        ->itemView(fn (array $item) => "<i>{$item['label']}</i>")
        ->getSearchResultsUsing(fn (string $search) => [
            ['value' => 1, 'label' => $search . '-a'],
            ['value' => 2, 'label' => $search . '-b'],
            ['value' => 3, 'label' => $search . '-c'],
        ]);

    $out = $field->search('x');

    expect($out['error'])->toBeNull()
        ->and($out['results'])->toHaveCount(2)
        ->and($out['results'][0])->toMatchArray([
            'value' => 1,
            'label' => 'x-a',
            'html' => '<i>x-a</i>',
        ]);
});

it('returns an error and empty results when the closure throws', function () {
    $field = AutocompleteInput::make('q')
        ->getSearchResultsUsing(function (string $search) {
            throw new RuntimeException('boom');
        });

    $out = $field->search('x');

    expect($out['results'])->toBe([])
        ->and($out['error'])->toBe('boom');
});

it('returns empty results without server search configured', function () {
    $out = AutocompleteInput::make('q')->search('x');

    expect($out['results'])->toBe([])->and($out['error'])->toBeNull();
});

it('accepts a Collection returned from the search closure', function () {
    $field = AutocompleteInput::make('q')
        ->itemView(fn (array $item) => "<i>{$item['label']}</i>")
        ->getSearchResultsUsing(fn (string $search) => collect([
            ['value' => 1, 'label' => $search . '-a'],
            ['value' => 2, 'label' => $search . '-b'],
        ]));

    $out = $field->search('x');

    expect($out['error'])->toBeNull()
        ->and($out['results'])->toHaveCount(2)
        ->and($out['results'][1])->toMatchArray(['value' => 2, 'label' => 'x-b']);
});
