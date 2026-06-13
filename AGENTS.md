# AGENTS.md

Guidance for AI coding assistants using **giacomomasseron/filament-textinput-autocomplete**.

## What this package is

A Filament v5 form field, `AutocompleteInput`, that renders a free-text input with a
suggestion dropdown. Suggestions come from either a static list (filtered in the browser) or a
server-side closure (run over Livewire, which may call an external API). Each suggestion's HTML
is rendered in PHP via a single `itemView()` setter.

Use it anywhere a Filament field is valid: resource forms, schemas, custom Livewire components
with `InteractsWithForms`.

```php
use Giacomo\TextInputAutocomplete\Forms\Components\AutocompleteInput;
```

> Note: the Composer package is `giacomomasseron/filament-textinput-autocomplete`, but the PHP
> namespace is `Giacomo\TextInputAutocomplete\` (note: NOT `Giacomomasseron\`). Import the field
> from the namespace shown above.

## Setup

```bash
composer require giacomomasseron/filament-textinput-autocomplete
php artisan filament:assets   # required — publishes the Alpine component + CSS. Re-run on deploy.
```

If suggestions appear unstyled or the dropdown never opens, the assets were not published — run
`php artisan filament:assets`.

## The two modes (pick exactly one)

`options()` and `getSearchResultsUsing()` are **mutually exclusive**. Setting both throws
`InvalidArgumentException` at render/search time.

### Static (client-side filtering)

```php
AutocompleteInput::make('country')
    ->options([
        ['value' => 'es', 'label' => 'Spain', 'description' => 'Country'],
        ['value' => 'fr', 'label' => 'France', 'description' => 'Country'],
    ])
    ->searchKeys(['label', 'description']) // which item keys are matched while typing
    ->itemView(fn (array $item) => "<strong>{$item['label']}</strong> — {$item['description']}");
```

### Server search (over Livewire; may hit an external API)

```php
use Illuminate\Support\Facades\Http;

AutocompleteInput::make('repo')
    ->getSearchResultsUsing(function (string $search) {
        $items = Http::get('https://api.github.com/search/repositories', ['q' => $search])
            ->json('items', []);

        return collect($items)
            ->map(fn (array $r) => ['value' => $r['id'], 'label' => $r['full_name']])
            ->all();
    })
    ->minChars(2)
    ->searchDebounce(300);
```

## Item shape (important)

Every suggestion — static or from the server closure — must be an **associative array**. The
closure may return a plain array OR a `Collection` of such arrays (it is coerced to an array).

- The key named by `optionValue()` (default `'value'`) is stored as the field's form state.
- The key named by `optionLabel()` (default `'label'`) is shown in the input after selection.
- Items lacking those keys degrade gracefully (value → `null`, label → `''`), so include them.
- Do NOT return scalars (e.g. a list of strings) — wrap each as `['value' => ..., 'label' => ...]`.

## Rendering each suggestion: `itemView()`

Resolved by argument type:

| You pass | Behavior |
|---|---|
| a `Closure` `fn (array $item) => '...'` | return value is rendered as **raw HTML — you must escape** |
| an existing Blade view name (string) | rendered with the item available as `$item` |
| any other string, e.g. `'{label} — {city}'` | `{key}` tokens are replaced from the item, **escaped** |
| nothing (omitted) | the `optionLabel` value is shown as escaped text |

## Full config reference

| Method | Default | Purpose |
|---|---|---|
| `options(array\|Closure)` | — | Static client-side source |
| `searchKeys(array)` | `['label']` | Item keys matched in static mode |
| `getSearchResultsUsing(Closure)` | — | Server source; receives `string $search` |
| `itemView(string\|Closure)` | escaped label | View name, raw-HTML closure, or `{key}` template |
| `optionLabel(string)` | `'label'` | Item key shown in the input on select |
| `optionValue(string)` | `'value'` | Item key stored as form state |
| `minChars(int)` | `1` | Minimum characters before searching |
| `searchDebounce(int)` | `300` | Debounce (ms) for server search |
| `maxResults(int)` | `10` | Max suggestions returned (must be ≥ 1) |
| `placeholder(string\|Closure)` | — | Input placeholder |
| `noResultsMessage(string)` | `'No results found'` | Empty-state text |
| `loadingMessage(string)` | `'Loading...'` | Loading-state text |

There is **no** `afterSelected()` / selection-event hook. Do not call methods not in this table.

## Styling

A self-contained default stylesheet ships as a registered Filament CSS asset and is scoped to
`.fi-ac-wrapper`, `.fi-ac-input`, `.fi-ac-dropdown`, `.fi-ac-item` (active: `.fi-ac-item--active`),
and `.fi-ac-empty` (error: `.fi-ac-empty--error`). To restyle, override those classes in your own
CSS. The markup inside each suggestion is whatever `itemView()` returns — you control it.

## Common mistakes to avoid

- Setting both `options()` and `getSearchResultsUsing()` → throws. Choose one.
- Returning scalars or non-array items from the search closure → no usable suggestions.
- Forgetting `php artisan filament:assets` → field loads but is unstyled / inert.
- Expecting `itemView()` closure output to be auto-escaped → it is raw HTML; escape it yourself.
  (The `{token}` template form and the default DO escape.)
- Importing from a `Giacomomasseron\...` namespace → wrong. Use `Giacomo\TextInputAutocomplete\`.
