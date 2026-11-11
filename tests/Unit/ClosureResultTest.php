<?php

use GiacomoMasseroni\TextInputAutocomplete\Forms\Components\AutocompleteInput;
use Illuminate\Support\HtmlString;

it('coerces numeric strings returned from closures to integers', function () {
    $field = AutocompleteInput::make('q')
        ->minChars(fn () => '3')
        ->searchDebounce(fn () => '250')
        ->maxResults(fn () => '7');

    expect($field->getMinChars())->toBe(3)
        ->and($field->getSearchDebounce())->toBe(250)
        ->and($field->getMaxResults())->toBe(7);
});

it('accepts scalar and stringable closure results for text settings', function () {
    $field = AutocompleteInput::make('q')
        ->optionValue(fn () => 0)
        ->noResultsMessage(fn () => new HtmlString('Nothing here'))
        ->placeholder(fn () => null)
        ->itemView(fn (array $item) => new HtmlString("<b>{$item['label']}</b>"))
        ->options([['label' => 'Spain', 0 => 'es']]);

    expect($field->getOptionValue())->toBe('0')
        ->and($field->getNoResultsMessage())->toBe('Nothing here')
        ->and($field->getPlaceholder())->toBeNull()
        ->and($field->getOptionsForJs()[0])->toMatchArray(['value' => 'es', 'html' => '<b>Spain</b>']);
});

it('throws a descriptive error when a closure returns the wrong type for a setting', function (Closure $configure, string $setting) {
    $configure(AutocompleteInput::make('q'));
})->with([
    'minChars' => [fn (AutocompleteInput $f) => $f->minChars(fn () => 'many')->getMinChars(), 'minChars'],
    'loadingMessage' => [fn (AutocompleteInput $f) => $f->loadingMessage(fn () => ['a'])->getLoadingMessage(), 'loadingMessage'],
    'placeholder' => [fn (AutocompleteInput $f) => $f->placeholder(fn () => ['a'])->getPlaceholder(), 'placeholder'],
    'searchKeys' => [fn (AutocompleteInput $f) => $f->searchKeys(fn () => 'label')->getSearchKeys(), 'searchKeys'],
    'options' => [fn (AutocompleteInput $f) => $f->options(fn () => 'nope')->getOptions(), 'options'],
])->throws(InvalidArgumentException::class);

it('names the setting in the error message', function () {
    AutocompleteInput::make('q')->minChars(fn () => 'many')->getMinChars();
})->throws(InvalidArgumentException::class, 'AutocompleteInput minChars() must resolve to an integer, string given.');

it('rejects options that are not arrays', function () {
    AutocompleteInput::make('q')->options(fn () => [['label' => 'Spain'], 'France'])->getOptions();
})->throws(InvalidArgumentException::class, 'AutocompleteInput options() must resolve to a list of arrays, string given.');

it('explains which search result has the wrong shape', function () {
    $out = AutocompleteInput::make('q')
        ->getSearchResultsUsing(fn (string $search) => [['label' => 'ok'], 'just-a-string'])
        ->search('x');

    expect($out['results'])->toBe([])
        ->and($out['error'])->toBe('Each AutocompleteInput search result must be an array, string given.');
});
