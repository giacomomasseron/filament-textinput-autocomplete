# Filament TextInput Autocomplete Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a distributable Filament v5 Composer package providing an `AutocompleteInput` form field with static (client-side) and server (Livewire) suggestion sourcing and PHP-rendered item markup.

**Architecture:** A custom field class extends `Filament\Forms\Components\Field`. PHP always renders each suggestion's HTML (via a single `itemView()` setter resolving view name / closure / template string), and an async-loaded Alpine component injects that HTML with `x-html`. Static mode pre-renders all options at mount; server mode calls an `#[ExposedLivewireMethod]` `search()` over `$wire.callSchemaComponentMethod`.

**Tech Stack:** PHP 8.2+, Filament v5 (`filament/filament: ^5`), `spatie/laravel-package-tools`, Alpine.js, esbuild, Pest + `orchestra/testbench`.

**Spec:** `docs/superpowers/specs/2026-06-12-filament-textinput-autocomplete-design.md`

---

## File Structure

| File | Responsibility |
|---|---|
| `composer.json` | Package metadata, autoload, deps, scripts |
| `src/TextInputAutocompleteServiceProvider.php` | Register view namespace + Alpine asset |
| `src/Forms/Components/AutocompleteInput.php` | Field class: config, itemView resolution, static prep, server `search()` |
| `resources/views/forms/components/autocomplete-input.blade.php` | Input + dropdown markup, async-load Alpine, entangle state |
| `resources/js/autocomplete.js` | Alpine component source |
| `dist/components/autocomplete.js` | Built JS (committed) |
| `package.json`, `bin/build.js` | esbuild bundling |
| `tests/TestCase.php` | Testbench base with Filament providers |
| `tests/Pest.php` | Pest bootstrap |
| `tests/Unit/AutocompleteInputTest.php` | Field unit tests |
| `phpunit.xml` | PHPUnit/Pest config |
| `README.md` | Install + usage |

---

## Task 1: Package scaffolding & green test harness

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml`
- Create: `tests/TestCase.php`
- Create: `tests/Pest.php`
- Create: `tests/Unit/SmokeTest.php`

- [ ] **Step 1: Write `composer.json`**

```json
{
    "name": "giacomo/filament-textinput-autocomplete",
    "description": "A Filament v5 TextInput field with autocomplete (static + server search).",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "filament/filament": "^5.0",
        "spatie/laravel-package-tools": "^1.16"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Giacomo\\TextInputAutocomplete\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Giacomo\\TextInputAutocomplete\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Giacomo\\TextInputAutocomplete\\TextInputAutocompleteServiceProvider"
            ]
        }
    },
    "config": {
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        },
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

- [ ] **Step 2: Create a minimal service provider so autoload/discovery resolves**

Create `src/TextInputAutocompleteServiceProvider.php`:

```php
<?php

namespace Giacomo\TextInputAutocomplete;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TextInputAutocompleteServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-textinput-autocomplete')
            ->hasViews('filament-textinput-autocomplete');
    }
}
```

- [ ] **Step 3: Write `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 4: Write `tests/TestCase.php`**

```php
<?php

namespace Giacomo\TextInputAutocomplete\Tests;

use Giacomo\TextInputAutocomplete\TextInputAutocompleteServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\HeroiconsServiceProvider::class,
            TextInputAutocompleteServiceProvider::class,
        ];
    }
}
```

> Note: If a provider class above does not exist in the installed Filament v5 build, run
> `composer show filament/filament` and inspect `vendor/filament/*/src/*ServiceProvider.php`
> to correct the namespace. The field unit tests only need Support + Forms + Schemas booted.

- [ ] **Step 5: Write `tests/Pest.php`**

```php
<?php

use Giacomo\TextInputAutocomplete\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
```

- [ ] **Step 6: Write the smoke test** — `tests/Unit/SmokeTest.php`

```php
<?php

it('boots the package service provider', function () {
    expect(class_exists(\Giacomo\TextInputAutocomplete\TextInputAutocompleteServiceProvider::class))->toBeTrue();
});
```

- [ ] **Step 7: Install dependencies**

Run: `composer install`
Expected: dependencies resolve; `vendor/` created. If Filament v5 is not yet on Packagist's stable channel, set `"minimum-stability": "dev"` temporarily and re-run.

- [ ] **Step 8: Run the smoke test**

Run: `vendor/bin/pest tests/Unit/SmokeTest.php`
Expected: PASS (1 passed). This confirms testbench can boot the Filament providers + our provider.

- [ ] **Step 9: Commit**

```bash
git add composer.json phpunit.xml tests/ src/TextInputAutocompleteServiceProvider.php
git commit -m "chore: scaffold package, test harness, and service provider"
```

---

## Task 2: Field class skeleton

**Files:**
- Create: `src/Forms/Components/AutocompleteInput.php`
- Test: `tests/Unit/AutocompleteInputTest.php`

- [ ] **Step 1: Write the failing test** — `tests/Unit/AutocompleteInputTest.php`

```php
<?php

use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

it('can be instantiated with a name', function () {
    $field = AutocompleteInput::make('country');

    expect($field)->toBeInstanceOf(AutocompleteInput::class)
        ->and($field->getName())->toBe('country');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/AutocompleteInputTest.php`
Expected: FAIL — class `AutocompleteInput` not found.

- [ ] **Step 3: Write minimal implementation** — `src/Forms/Components/AutocompleteInput.php`

```php
<?php

namespace Giacomo\TextInputAutocomplete\Forms\Components;

use Filament\Forms\Components\Field;

class AutocompleteInput extends Field
{
    protected string $view = 'filament-textinput-autocomplete::forms.components.autocomplete-input';
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/AutocompleteInputTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Forms/Components/AutocompleteInput.php tests/Unit/AutocompleteInputTest.php
git commit -m "feat: add AutocompleteInput field class skeleton"
```

---

## Task 3: Configuration setters & getters

Adds all chainable config with getters. Each property supports a static value or a Closure
evaluated through Filament's `evaluate()`.

**Files:**
- Modify: `src/Forms/Components/AutocompleteInput.php`
- Test: `tests/Unit/AutocompleteInputTest.php`

- [ ] **Step 1: Write the failing tests** (append to `tests/Unit/AutocompleteInputTest.php`)

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/AutocompleteInputTest.php`
Expected: FAIL — `getOptions()` etc. undefined.

- [ ] **Step 3: Implement the config** — replace the body of `AutocompleteInput`:

```php
<?php

namespace Giacomo\TextInputAutocomplete\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class AutocompleteInput extends Field
{
    protected string $view = 'filament-textinput-autocomplete::forms.components.autocomplete-input';

    protected array | Closure | null $options = null;

    protected array | Closure $searchKeys = ['label'];

    protected ?Closure $getSearchResultsUsing = null;

    protected string | Closure | null $itemView = null;

    protected string | Closure $optionLabel = 'label';

    protected string | Closure $optionValue = 'value';

    protected int | Closure $minChars = 1;

    protected int | Closure $searchDebounce = 300;

    protected int | Closure $maxResults = 10;

    protected string | Closure $noResultsMessage = 'No results found';

    protected string | Closure $loadingMessage = 'Loading...';

    protected ?Closure $afterSelected = null;

    public function options(array | Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function searchKeys(array | Closure $keys): static
    {
        $this->searchKeys = $keys;

        return $this;
    }

    public function getSearchResultsUsing(Closure $callback): static
    {
        $this->getSearchResultsUsing = $callback;

        return $this;
    }

    public function itemView(string | Closure $view): static
    {
        $this->itemView = $view;

        return $this;
    }

    public function optionLabel(string | Closure $key): static
    {
        $this->optionLabel = $key;

        return $this;
    }

    public function optionValue(string | Closure $key): static
    {
        $this->optionValue = $key;

        return $this;
    }

    public function minChars(int | Closure $count): static
    {
        $this->minChars = $count;

        return $this;
    }

    public function searchDebounce(int | Closure $ms): static
    {
        $this->searchDebounce = $ms;

        return $this;
    }

    public function maxResults(int | Closure $count): static
    {
        $this->maxResults = $count;

        return $this;
    }

    public function noResultsMessage(string | Closure $message): static
    {
        $this->noResultsMessage = $message;

        return $this;
    }

    public function loadingMessage(string | Closure $message): static
    {
        $this->loadingMessage = $message;

        return $this;
    }

    public function afterSelected(Closure $callback): static
    {
        $this->afterSelected = $callback;

        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->evaluate($this->options);
    }

    public function getSearchKeys(): array
    {
        return $this->evaluate($this->searchKeys);
    }

    public function hasServerSearch(): bool
    {
        return $this->getSearchResultsUsing !== null;
    }

    public function getOptionLabel(): string
    {
        return $this->evaluate($this->optionLabel);
    }

    public function getOptionValue(): string
    {
        return $this->evaluate($this->optionValue);
    }

    public function getMinChars(): int
    {
        return $this->evaluate($this->minChars);
    }

    public function getSearchDebounce(): int
    {
        return $this->evaluate($this->searchDebounce);
    }

    public function getMaxResults(): int
    {
        return $this->evaluate($this->maxResults);
    }

    public function getNoResultsMessage(): string
    {
        return $this->evaluate($this->noResultsMessage);
    }

    public function getLoadingMessage(): string
    {
        return $this->evaluate($this->loadingMessage);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/AutocompleteInputTest.php`
Expected: PASS (all config tests green).

- [ ] **Step 5: Commit**

```bash
git add src/Forms/Components/AutocompleteInput.php tests/Unit/AutocompleteInputTest.php
git commit -m "feat: add AutocompleteInput configuration API"
```

---

## Task 4: `itemView` resolution (render one item to HTML)

A protected `renderItem(array $item): string` resolving by type:
1. Closure → call with item, treat return as raw HTML.
2. String matching an existing view → render with `['item' => $item]`.
3. Other string → template; replace `{key}` tokens from item attributes.
4. Null (default) → escaped `optionLabel` value.

**Files:**
- Modify: `src/Forms/Components/AutocompleteInput.php`
- Create: `tests/views/item.blade.php` (a fixture view)
- Test: `tests/Unit/RenderItemTest.php`

- [ ] **Step 1: Register the fixture view directory in TestCase**

Modify `tests/TestCase.php` — add a `getEnvironmentSetUp` method inside the class:

```php
    protected function defineEnvironment($app): void
    {
        $app['view']->addNamespace('test-fixtures', __DIR__ . '/views');
    }
```

Create `tests/views/item.blade.php`:

```blade
<span class="fixture">{{ $item['label'] }}</span>
```

- [ ] **Step 2: Write the failing test** — `tests/Unit/RenderItemTest.php`

```php
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
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/RenderItemTest.php`
Expected: FAIL — `renderItem` does not exist.

- [ ] **Step 4: Implement `renderItem`** — add to `AutocompleteInput`:

```php
    protected function renderItem(array $item): string
    {
        $itemView = $this->itemView;

        if ($itemView instanceof \Closure) {
            return (string) $this->evaluate($itemView, ['item' => $item]);
        }

        if (is_string($itemView)) {
            if (view()->exists($itemView)) {
                return view($itemView, ['item' => $item])->render();
            }

            return preg_replace_callback(
                '/\{(\w+)\}/',
                fn (array $m) => e($item[$m[1]] ?? ''),
                $itemView,
            );
        }

        return e($item[$this->getOptionLabel()] ?? '');
    }
```

> Note: `evaluate()` with named arg `['item' => $item]` injects `$item` into the closure if
> it type-hints/names that parameter; the closures in tests use `array $item`, which
> Filament resolves by name.

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/RenderItemTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Forms/Components/AutocompleteInput.php tests/Unit/RenderItemTest.php tests/views/item.blade.php tests/TestCase.php
git commit -m "feat: resolve itemView to HTML (closure, view, template, default)"
```

---

## Task 5: Static-mode option preparation

`getOptionsForJs(): array` maps each static option to `['value' => ..., 'label' => ...,
'html' => ..., 'keys' => [...searchable values...]]` for the Alpine component.

**Files:**
- Modify: `src/Forms/Components/AutocompleteInput.php`
- Test: `tests/Unit/PrepareOptionsTest.php`

- [ ] **Step 1: Write the failing test** — `tests/Unit/PrepareOptionsTest.php`

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/PrepareOptionsTest.php`
Expected: FAIL — `getOptionsForJs` undefined.

- [ ] **Step 3: Implement** — add to `AutocompleteInput`. Factor a shared `mapItem` helper
(also reused by the server method in Task 6):

```php
    public function getOptionsForJs(): array
    {
        $options = $this->getOptions() ?? [];

        return array_map(fn (array $item) => $this->mapItem($item), $options);
    }

    protected function mapItem(array $item): array
    {
        $searchable = array_map(
            fn (string $key) => mb_strtolower((string) ($item[$key] ?? '')),
            $this->getSearchKeys(),
        );

        return [
            'value' => $item[$this->getOptionValue()] ?? null,
            'label' => (string) ($item[$this->getOptionLabel()] ?? ''),
            'html' => $this->renderItem($item),
            'keys' => array_values($searchable),
        ];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/PrepareOptionsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Forms/Components/AutocompleteInput.php tests/Unit/PrepareOptionsTest.php
git commit -m "feat: prepare static options for the Alpine component"
```

---

## Task 6: Server `search()` method (Livewire-exposed)

Exposed to JS via `#[ExposedLivewireMethod] #[Renderless]`. Runs the `getSearchResultsUsing`
closure, maps each result with `mapItem`, caps at `maxResults`, and returns
`['results' => [...], 'error' => null|string]`. Closure exceptions are caught → empty
results + error message.

**Files:**
- Modify: `src/Forms/Components/AutocompleteInput.php`
- Test: `tests/Unit/SearchMethodTest.php`

- [ ] **Step 1: Write the failing test** — `tests/Unit/SearchMethodTest.php`

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/SearchMethodTest.php`
Expected: FAIL — `search` undefined.

- [ ] **Step 3: Implement** — add imports and the method to `AutocompleteInput`.

Add at the top (with the other `use` statements):

```php
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;
```

Add the method:

```php
    #[ExposedLivewireMethod]
    #[Renderless]
    public function search(string $search): array
    {
        if ($this->getSearchResultsUsing === null) {
            return ['results' => [], 'error' => null];
        }

        try {
            $raw = $this->evaluate($this->getSearchResultsUsing, ['search' => $search]) ?? [];
        } catch (\Throwable $e) {
            return ['results' => [], 'error' => $e->getMessage()];
        }

        $results = array_map(
            fn (array $item) => $this->mapItem($item),
            array_slice(array_values($raw), 0, $this->getMaxResults()),
        );

        return ['results' => $results, 'error' => null];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/SearchMethodTest.php`
Expected: PASS.

> Note: If `Livewire\Attributes\Renderless` cannot be found, confirm Livewire 3 is installed
> (`composer show livewire/livewire`). The attribute lives in that namespace in Livewire v3.

- [ ] **Step 5: Commit**

```bash
git add src/Forms/Components/AutocompleteInput.php tests/Unit/SearchMethodTest.php
git commit -m "feat: add Livewire-exposed server search method"
```

---

## Task 7: Configuration validation

Throw early on invalid combinations: both `options` and `getSearchResultsUsing` set, or
`maxResults < 1`. Validate lazily via a `validateConfiguration()` called from the data
accessors used at render time (`getOptionsForJs`) and from `search()`.

**Files:**
- Modify: `src/Forms/Components/AutocompleteInput.php`
- Test: `tests/Unit/ValidationTest.php`

- [ ] **Step 1: Write the failing test** — `tests/Unit/ValidationTest.php`

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/ValidationTest.php`
Expected: FAIL — no exception thrown.

- [ ] **Step 3: Implement** — add the guard and call it.

Add the method:

```php
    protected function validateConfiguration(): void
    {
        if ($this->options !== null && $this->getSearchResultsUsing !== null) {
            throw new \InvalidArgumentException(
                'AutocompleteInput cannot use both static options() and getSearchResultsUsing(); choose one source.',
            );
        }

        if ($this->getMaxResults() < 1) {
            throw new \InvalidArgumentException('AutocompleteInput maxResults() must be at least 1.');
        }
    }
```

Call `$this->validateConfiguration();` as the first line of both `getOptionsForJs()` and
`search()`:

```php
    public function getOptionsForJs(): array
    {
        $this->validateConfiguration();

        $options = $this->getOptions() ?? [];
        // ...unchanged...
    }
```

```php
    public function search(string $search): array
    {
        $this->validateConfiguration();

        if ($this->getSearchResultsUsing === null) {
            // ...unchanged...
    }
```

- [ ] **Step 4: Run all unit tests**

Run: `vendor/bin/pest tests/Unit`
Expected: PASS (all suites including the new validation tests).

- [ ] **Step 5: Commit**

```bash
git add src/Forms/Components/AutocompleteInput.php tests/Unit/ValidationTest.php
git commit -m "feat: validate AutocompleteInput configuration"
```

---

## Task 8: Blade view

Renders the field wrapper, input, dropdown, and a config payload for Alpine. Async-loads the
Alpine component and entangles form state.

**Files:**
- Create: `resources/views/forms/components/autocomplete-input.blade.php`

- [ ] **Step 1: Write the view**

```blade
@php
    $statePath = $getStatePath();
    $key = $getKey();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-load
        x-load-src="@js(\Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('autocomplete', 'giacomo/filament-textinput-autocomplete'))"
        x-data="autocomplete({
            state: $wire.$entangle('{{ $statePath }}'),
            componentKey: @js($key),
            serverSearch: @js($field->hasServerSearch()),
            options: @js($field->getOptionsForJs()),
            searchKeys: @js($field->getSearchKeys()),
            minChars: @js($field->getMinChars()),
            debounceTime: @js($field->getSearchDebounce()),
            maxResults: @js($field->getMaxResults()),
            noResultsMessage: @js($field->getNoResultsMessage()),
            loadingMessage: @js($field->getLoadingMessage()),
        })"
        class="fi-ac-wrapper"
        style="position: relative;"
        @click.outside="close()"
    >
        <input
            type="text"
            x-model="query"
            @input="search()"
            @focus="search()"
            @keydown.down.prevent="navigateDown()"
            @keydown.up.prevent="navigateUp()"
            @keydown.enter.prevent="selectActive()"
            @keydown.escape="close()"
            autocomplete="off"
            placeholder="{{ $getPlaceholder() }}"
            {{ $isDisabled() ? 'disabled' : '' }}
            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-1.5 text-base shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:ring-white/20"
        />

        <div
            x-show="isOpen"
            x-transition
            class="fi-ac-dropdown absolute z-10 mt-1 w-full overflow-auto rounded-lg bg-white shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/20"
            style="max-height: 20rem;"
            x-cloak
        >
            <template x-if="isLoading">
                <div class="px-3 py-2 text-sm text-gray-500" x-text="loadingMessage"></div>
            </template>

            <template x-for="(item, index) in results" :key="index">
                <div
                    @click="select(item)"
                    @mouseenter="activeIndex = index"
                    :class="index === activeIndex ? 'bg-primary-50 dark:bg-white/5' : ''"
                    class="fi-ac-item cursor-pointer px-3 py-2"
                    x-html="item.html"
                ></div>
            </template>

            <template x-if="! isLoading && ! results.length && error">
                <div class="px-3 py-2 text-sm text-danger-600" x-text="error"></div>
            </template>

            <template x-if="! isLoading && ! results.length && ! error && query.length >= minChars">
                <div class="px-3 py-2 text-sm text-gray-500" x-text="noResultsMessage"></div>
            </template>
        </div>
    </div>
</x-dynamic-component>
```

- [ ] **Step 2: Verify the view file parses (compile check)**

Run: `php -l resources/views/forms/components/autocomplete-input.blade.php`
Expected: "No syntax errors detected" (lints the raw PHP in `@php` blocks; full Blade
compilation is exercised by the render test in Task 11).

- [ ] **Step 3: Commit**

```bash
git add resources/views/forms/components/autocomplete-input.blade.php
git commit -m "feat: add autocomplete field blade view"
```

---

## Task 9: Alpine component source

The client component: query state, debounce, client-side filtering (static mode), server
calls (server mode), keyboard navigation, selection.

**Files:**
- Create: `resources/js/autocomplete.js`

- [ ] **Step 1: Write the component**

```js
export default function autocomplete(config) {
    return {
        // --- config ---
        serverSearch: config.serverSearch,
        componentKey: config.componentKey,
        options: config.options || [],
        searchKeys: config.searchKeys || ['label'],
        minChars: config.minChars ?? 1,
        debounceTime: config.debounceTime ?? 300,
        maxResults: config.maxResults ?? 10,
        noResultsMessage: config.noResultsMessage || 'No results found',
        loadingMessage: config.loadingMessage || 'Loading...',

        // --- state ---
        state: config.state,
        query: '',
        results: [],
        isOpen: false,
        isLoading: false,
        error: null,
        activeIndex: -1,
        debounceTimer: null,

        init() {
            // Reflect any pre-existing state value into the visible input.
            if (this.state) {
                this.query = this.state;
            }
        },

        search() {
            clearTimeout(this.debounceTimer);
            this.error = null;

            if (this.query.length < this.minChars) {
                this.results = [];
                this.isOpen = false;
                this.isLoading = false;
                return;
            }

            if (this.serverSearch) {
                this.isOpen = true;
                this.isLoading = true;
                this.debounceTimer = setTimeout(() => this.fetchResults(), this.debounceTime);
                return;
            }

            this.results = this.filterStatic();
            this.activeIndex = -1;
            this.isOpen = true;
        },

        filterStatic() {
            const term = this.query.toLowerCase();

            return this.options
                .filter((item) => item.keys.some((value) => value.includes(term)))
                .slice(0, this.maxResults);
        },

        async fetchResults() {
            try {
                const response = await this.$wire.callSchemaComponentMethod(
                    this.componentKey,
                    'search',
                    { search: this.query },
                );
                this.results = response.results || [];
                this.error = response.error || null;
            } catch (e) {
                this.results = [];
                this.error = e?.message || 'Search failed';
            } finally {
                this.isLoading = false;
                this.activeIndex = -1;
            }
        },

        navigateDown() {
            if (this.activeIndex < this.results.length - 1) this.activeIndex++;
        },

        navigateUp() {
            if (this.activeIndex > 0) this.activeIndex--;
        },

        selectActive() {
            if (this.activeIndex >= 0 && this.activeIndex < this.results.length) {
                this.select(this.results[this.activeIndex]);
            }
        },

        select(item) {
            this.state = item.value ?? item.label;
            this.query = item.label;
            this.close();
        },

        close() {
            this.isOpen = false;
            this.activeIndex = -1;
        },
    };
}
```

- [ ] **Step 2: Lint the JS for syntax (Node parse check)**

Run: `node --check resources/js/autocomplete.js`
Expected: no output, exit 0 (valid syntax). If `--check` rejects ESM `export`, that is
expected on older Node; proceed — esbuild (Task 10) is the real validator.

- [ ] **Step 3: Commit**

```bash
git add resources/js/autocomplete.js
git commit -m "feat: add Alpine autocomplete component source"
```

---

## Task 10: Build tooling (esbuild → dist)

**Files:**
- Create: `package.json`
- Create: `bin/build.js`

- [ ] **Step 1: Write `package.json`**

```json
{
    "name": "filament-textinput-autocomplete",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "node bin/build.js"
    },
    "devDependencies": {
        "esbuild": "^0.23.0"
    }
}
```

- [ ] **Step 2: Write `bin/build.js`**

```js
import * as esbuild from 'esbuild';

await esbuild.build({
    entryPoints: ['resources/js/autocomplete.js'],
    outfile: 'dist/components/autocomplete.js',
    bundle: true,
    minify: true,
    format: 'esm',
    platform: 'browser',
});

console.log('Built dist/components/autocomplete.js');
```

- [ ] **Step 3: Install and build**

Run: `npm install && npm run build`
Expected: "Built dist/components/autocomplete.js"; file `dist/components/autocomplete.js`
exists and is non-empty.

- [ ] **Step 4: Verify the build output exists**

Run: `node -e "const s=require('fs').statSync('dist/components/autocomplete.js'); if(!s.size) process.exit(1)"`
Expected: exit 0.

- [ ] **Step 5: Commit (including built asset)**

```bash
git add package.json bin/build.js dist/components/autocomplete.js
git commit -m "build: add esbuild tooling and compiled Alpine asset"
```

---

## Task 11: Register the asset & render the field

Wire the Alpine component into `FilamentAsset` and confirm the field renders end-to-end
inside a Filament Livewire schema.

**Files:**
- Modify: `src/TextInputAutocompleteServiceProvider.php`
- Test: `tests/Feature/RendersFieldTest.php`

- [ ] **Step 1: Update the service provider**

```php
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
```

- [ ] **Step 2: Write the failing render test** — `tests/Feature/RendersFieldTest.php`

```php
<?php

use Filament\Forms\Components\Field;
use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;
use Livewire\Livewire;

it('renders the static field markup in a Filament schema', function () {
    Livewire::test(\Giacomo\TextInputAutocomplete\Tests\Fixtures\AutocompleteTestComponent::class, [
        'field' => AutocompleteInput::make('country')
            ->options([['value' => 1, 'label' => 'Spain']]),
    ])
        ->assertOk()
        ->assertSee('fi-ac-wrapper', escape: false);
});
```

- [ ] **Step 3: Create the Livewire test fixture** — `tests/Fixtures/AutocompleteTestComponent.php`

```php
<?php

namespace Giacomo\TextInputAutocomplete\Tests\Fixtures;

use Filament\Forms\Components\Field;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;

class AutocompleteTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public Field $field;

    public array $data = [];

    public function mount(Field $field): void
    {
        $this->field = $field;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([$this->field])
            ->statePath('data');
    }

    public function render()
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
        BLADE;
    }
}
```

> Note: In Filament v5 the form container type is `Filament\Schemas\Schema`. If the installed
> build still exposes `Filament\Forms\Form`, swap the type-hint and the `form()` signature
> accordingly (`composer show filament/filament`, inspect `HasForms`).

- [ ] **Step 4: Run the render test**

Run: `vendor/bin/pest tests/Feature/RendersFieldTest.php`
Expected: PASS — output contains `fi-ac-wrapper`, confirming the Blade view compiles, the
config payload serializes, and `getOptionsForJs()` runs under a real schema.

- [ ] **Step 5: Run the full suite**

Run: `vendor/bin/pest`
Expected: PASS (all unit + feature tests).

- [ ] **Step 6: Commit**

```bash
git add src/TextInputAutocompleteServiceProvider.php tests/Feature/RendersFieldTest.php tests/Fixtures/AutocompleteTestComponent.php
git commit -m "feat: register Alpine asset and verify field rendering"
```

---

## Task 12: README & usage documentation

**Files:**
- Create: `README.md`

- [ ] **Step 1: Write `README.md`**

````markdown
# Filament TextInput Autocomplete

A Filament v5 form field: a free-text input with an autocomplete dropdown. Suggestions can be
static (filtered in the browser) or fetched from the server through Livewire. Each suggestion's
markup is rendered by PHP via a single `itemView()` setter.

## Installation

```bash
composer require giacomo/filament-textinput-autocomplete
```

The compiled Alpine asset ships in `dist/` and is registered automatically — no npm step is
required to use the package.

## Usage

### Static options (filtered client-side)

```php
use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;

AutocompleteInput::make('country')
    ->options([
        ['value' => 'es', 'label' => 'Spain', 'description' => 'Country'],
        ['value' => 'fr', 'label' => 'France', 'description' => 'Country'],
    ])
    ->searchKeys(['label', 'description'])
    ->itemView(fn (array $item) => "<strong>{$item['label']}</strong> — {$item['description']}");
```

### Server search (via Livewire; may call an external API)

```php
use Illuminate\Support\Facades\Http;

AutocompleteInput::make('repo')
    ->getSearchResultsUsing(function (string $search) {
        return Http::get('https://api.github.com/search/repositories', ['q' => $search])
            ->json('items', [])
            ? collect(Http::get('https://api.github.com/search/repositories', ['q' => $search])->json('items'))
                ->map(fn ($r) => ['value' => $r['id'], 'label' => $r['full_name']])
                ->all()
            : [];
    })
    ->itemView('filament.autocomplete.repo') // a Blade view receiving $item
    ->minChars(2)
    ->searchDebounce(300);
```

## Configuration reference

| Method | Default | Purpose |
|---|---|---|
| `options(array\|Closure)` | — | Static client-side source |
| `searchKeys(array)` | `['label']` | Item keys matched in static mode |
| `getSearchResultsUsing(Closure)` | — | Server source; receives `string $search` |
| `itemView(string\|Closure)` | label (escaped) | View name, raw-HTML closure, or `{key}` template |
| `optionLabel(string)` | `'label'` | Item key shown in the input on select |
| `optionValue(string)` | `'value'` | Item key stored as form state |
| `minChars(int)` | `1` | Minimum characters before searching |
| `searchDebounce(int)` | `300` | Debounce (ms) for server search |
| `maxResults(int)` | `10` | Max suggestions returned |
| `noResultsMessage(string)` | `'No results found'` | Empty-state text |
| `loadingMessage(string)` | `'Loading...'` | Loading-state text |

`options()` and `getSearchResultsUsing()` are mutually exclusive.

## Development

```bash
composer install
npm install
npm run build      # rebuild dist/components/autocomplete.js after editing resources/js
vendor/bin/pest    # run the test suite
```

## License

MIT
````

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: add README with installation and usage"
```

---

## Self-Review Notes

- **Spec coverage:** static mode (Tasks 5, 8, 9) ✓; server search via Livewire (Task 6, 9, 11) ✓; itemView view/string/HTML (Task 4) ✓; PHP-always-renders-HTML (Tasks 4–6) ✓; full Composer package + provider + assets + tests (Tasks 1, 10, 11) ✓; config validation & error handling (Tasks 6, 7) ✓; keyboard nav & selection (Task 9) ✓.
- **Type consistency:** item shape `['value','label','html','keys']` is produced by `mapItem` (Task 5) and consumed identically by `search()` (Task 6), the Blade payload (Task 8), and the Alpine component (Task 9). `search()` returns `['results','error']`, matched by `fetchResults()`.
- **Known verification points (flagged inline, not placeholders):** exact Filament v5 provider class names (Task 1), `Schema` vs `Form` container type (Task 11), and `Livewire\Attributes\Renderless` availability (Task 6) — each has an inline check command.
```
