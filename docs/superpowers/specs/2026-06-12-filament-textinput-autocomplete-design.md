# Filament TextInput Autocomplete — Design

**Date:** 2026-06-12
**Status:** Approved

## Summary

A FilamentPHP **v5** plugin that ships an `AutocompleteInput` form field: a free-text
input with a suggestion dropdown. Entries can be sourced **statically** (a fixed list,
filtered client-side) or via **server search** (a closure invoked through Livewire, which
may itself call an external HTTP API). Each suggestion's markup is produced by a single
`itemView()` setter that accepts a Blade view name, a raw-HTML closure, or a plain string
template.

This is a distributable Composer package (PSR-4 `src/`, service provider, Blade view,
compiled Alpine asset, Pest test suite), installable via `composer require`.

## Decisions (locked)

1. **Target:** Filament v5 (`filament/filament: ^5`).
2. **Base class:** Extend `Filament\Forms\Components\Field` with a custom Blade view
   (Approach A). Behaves as a drop-in TextInput replacement; we re-expose only the
   TextInput-style chainables we need rather than inheriting TextInput's fixed, affix-heavy
   view.
3. **Data sourcing:** Static (client-side filter) **and** server search via Livewire
   (`getSearchResultsUsing` closure). External APIs are reachable from inside that closure
   using Laravel's HTTP client — we do not bind to a raw `apiUrl` in JS.
4. **Item rendering:** Single `itemView()` setter, resolved by type.
5. **Unifying rule:** **PHP always renders each item's HTML.** Alpine never builds item
   markup; it injects the pre-rendered `html` field with `x-html`. This holds for both
   static and server modes, satisfying "use a view, a string, or HTML to format any entry."

## Architecture

### Components

- **`AutocompleteInput`** (`src/Forms/Components/AutocompleteInput.php`) — the Filament
  field class. Holds configuration, resolves `itemView`, exposes the server `search()`
  method, and prepares data for the Blade view.
- **`autocomplete-input.blade.php`** (`resources/views/forms/components/`) — wraps the
  field, renders the `<input>` + dropdown, async-loads the Alpine component, and entangles
  state.
- **`autocomplete.js`** (`resources/js/`, built to `dist/components/autocomplete.js`) — the
  Alpine component: query state, debounce, client-side filtering, keyboard navigation,
  selection, loading/error state. Ported from the `examples/` prototypes.
- **`TextInputAutocompleteServiceProvider`** (`src/`) — registers the view namespace and
  the Alpine asset via `FilamentAsset`.

### Data flow

**Static mode** — `->options([...])`:
1. At mount, `AutocompleteInput` renders each option's HTML via `itemView` and passes the
   full list (`[{ value, label, html, ...searchKeys }]`) to the Alpine component as a prop.
2. Alpine filters client-side across `searchKeys`, gated by `minChars`, capped at
   `maxResults`.

**Server mode** — `->getSearchResultsUsing(fn (string $search) => ...)`:
1. Alpine debounces input (`searchDebounce` ms); when `query.length >= minChars`, it calls
   `await $wire.callSchemaComponentMethod($getKey(), 'search', { search: query })`.
2. The field's `search(string $search): array` method — marked
   `#[ExposedLivewireMethod] #[Renderless]` — invokes the closure (which may hit an
   external API), maps each result, renders each via `itemView`, and returns
   `[{ value, label, html }]`, capped at `maxResults`.
3. Alpine renders the returned items with `x-html`.

**State & selection:**
- Visible `query` is local Alpine state. The field's form state is entangled via
  `$wire.$entangle($getStatePath())`.
- On select: form state ← `item.value`, `query` ← `item.label`, dropdown closes, optional
  `afterSelected` hook fires.
- Keyboard: ↑/↓ navigate, Enter selects active, Esc closes; outside-click closes. (Ported
  from prototypes.)

## Field API

```php
AutocompleteInput::make('field')
    ->options(array | Closure)                 // static client-side source
    ->searchKeys(['label', 'description'])      // item keys matched in static mode
    ->getSearchResultsUsing(Closure)            // server mode; receives string $search
    ->itemView(string | Closure)                // view name | raw-HTML closure | string template
    ->optionLabel('label')                      // item key shown in the input on select
    ->optionValue('value')                      // item key stored as form state
    ->minChars(1)
    ->searchDebounce(300)
    ->maxResults(10)
    ->placeholder('...')
    ->noResultsMessage('No results found')
    ->loadingMessage('Loading...')
    ->afterSelected(Closure);                   // optional; receives selected item
```

**`itemView` resolution (by type):**
1. `Closure` → called with `$item`; return value is treated as raw HTML.
2. `string` that matches an existing Blade view (`view()->exists($value)`) → rendered with
   `['item' => $item]`.
3. any other `string` → template string; `{key}` tokens replaced from item attributes,
   remaining markup passed through as HTML.

Default (no `itemView`): render `optionLabel` as escaped text.

**Mode resolution:** if `getSearchResultsUsing` is set → server mode; else if `options` is
set → static mode; setting both is a configuration error (throw).

## Package layout

```
composer.json
src/
  TextInputAutocompleteServiceProvider.php
  Forms/Components/AutocompleteInput.php
resources/
  views/forms/components/autocomplete-input.blade.php
  js/autocomplete.js
dist/components/autocomplete.js     # esbuild output (committed for composer installs)
package.json
bin/build.js                        # esbuild config
tests/
  TestCase.php
  Unit/AutocompleteInputTest.php
docs/superpowers/specs/2026-06-12-filament-textinput-autocomplete-design.md
```

## Service provider & assets

Use `spatie/laravel-package-tools`:

- Register the `filament-textinput-autocomplete` view namespace.
- In `packageBooted()`:
  ```php
  FilamentAsset::register([
      AlpineComponent::make('autocomplete', __DIR__ . '/../dist/components/autocomplete.js'),
  ], package: 'giacomo/filament-textinput-autocomplete');
  ```
- The Blade view async-loads the component:
  ```blade
  <div
      x-load
      x-load-src="@js(\Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('autocomplete', 'giacomo/filament-textinput-autocomplete'))"
      x-data="autocomplete({ ...config })"
  >
  ```

## Build tooling (JS)

- `package.json` with an esbuild dev dependency and a `build` script.
- `bin/build.js` bundles `resources/js/autocomplete.js` → `dist/components/autocomplete.js`
  (minified, IIFE/ESM per Filament's loader expectation).
- `dist/` is committed so the package works on `composer require` without an npm step.

## Testing

Pest + `orchestra/testbench`:

- Field API setters/getters store and return values correctly.
- `itemView` resolution for all three input types (closure, view name, template string) plus
  the default.
- `search()` runs the closure, maps results, renders HTML per item, and caps at
  `maxResults`.
- Static-mode mount pre-renders option HTML.
- Mode-resolution error when both `options` and `getSearchResultsUsing` are set.

JS/Alpine behavior is validated manually for v1; a browser-test pass (e.g. Playwright) is
explicitly out of scope for the first iteration (YAGNI).

## Error handling

- Server `search()` wraps the closure in try/catch; on failure it returns an empty list and
  signals an error flag that the Alpine component surfaces (mirrors the prototype's
  `error` / `isLoading` states).
- The Alpine `$wire` call is wrapped in try/catch for transport/Livewire failures, setting
  the same error state.
- Invalid configuration (both data sources, or `maxResults` < 1) throws early with a clear
  message.

## Out of scope (v1)

- Multi-select / tag input.
- Caching of server search results.
- Browser/E2E test automation.
- A bundled external-API HTTP wrapper (users call HTTP inside their own closure).
