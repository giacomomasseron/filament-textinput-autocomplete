<?php

use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

function callRenderItem(AutocompleteInput $field, array $item): string
{
    $method = new ReflectionMethod($field, 'renderItem');
    $method->setAccessible(true);

    return $method->invoke($field, $item);
}

it('renders the escaped label by default', function () {
    $field = AutocompleteInput::make('q');
    $html = callRenderItem($field, ['label' => 'A & B']);

    expect($html)->toBe('A &amp; B');
});

it('renders a closure as raw html', function () {
    $field = AutocompleteInput::make('q')
        ->itemView(fn (array $item) => "<b>{$item['label']}</b>");

    expect(callRenderItem($field, ['label' => 'Spain']))->toBe('<b>Spain</b>');
});

it('renders a matching blade view with the item', function () {
    $field = AutocompleteInput::make('q')->itemView('test-fixtures::item');
    $html = callRenderItem($field, ['label' => 'Spain']);

    expect(trim($html))->toContain('class="fixture"')->toContain('Spain');
});

it('renders a template string by replacing tokens', function () {
    $field = AutocompleteInput::make('q')
        ->itemView('<div>{label} — {type}</div>');

    expect(callRenderItem($field, ['label' => 'Spain', 'type' => 'Country']))
        ->toBe('<div>Spain — Country</div>');
});

it('escapes token values in template strings', function () {
    $field = AutocompleteInput::make('q')->itemView('<div>{label}</div>');

    expect(callRenderItem($field, ['label' => '<x>']))->toBe('<div>&lt;x&gt;</div>');
});

it('casts non-string token values to string when rendering templates', function () {
    $field = AutocompleteInput::make('q')->itemView('<div>{label} #{id}</div>');

    expect(callRenderItem($field, ['label' => 'Spain', 'id' => 42]))
        ->toBe('<div>Spain #42</div>');
});

it('uses the configured optionLabel in the default render', function () {
    $field = AutocompleteInput::make('q')->optionLabel('name');

    expect(callRenderItem($field, ['name' => 'Spain']))->toBe('Spain');
});

it('returns an empty string when the default label key is missing', function () {
    $field = AutocompleteInput::make('q');

    expect(callRenderItem($field, ['other' => 'x']))->toBe('');
});
