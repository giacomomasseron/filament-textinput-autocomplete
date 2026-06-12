# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

This is a **greenfield project in the spec/prototype stage**. There is no plugin code, build tooling, dependency manifest (`composer.json`), or test suite yet. The repository currently contains only:

- `PROD.md` — the product spec (the source of truth for what to build).
- `examples/` — two standalone Alpine.js prototype HTML files that demonstrate the intended autocomplete behavior and serve as the reference implementation for the frontend logic.

When asked to "build", "test", or "run" anything, be aware these targets do not exist yet — they must be created. Do not invent commands; scaffold the missing tooling first (and update this file once it exists).

## What is being built

A FilamentPHP (v5+) plugin that **overrides/extends Filament's `TextInput` field** to add an autocomplete dropdown. Per `PROD.md`, the field must support:

1. Autocomplete entries sourced **statically** (a fixed in-memory list) **or from an API** (async fetch).
2. Per-entry rendering via **a Blade view, a plain string, or raw HTML**.

Because Filament's frontend is Livewire + Alpine, the autocomplete UI is expected to be implemented as an Alpine component — the `examples/` files are the working blueprint for that component.

## Reference implementation (examples/)

Both example files implement the same `Alpine.data('autocomplete', ...)` component with two data-sourcing modes. Mirror this behavior in the plugin's Alpine asset:

- **`alpine_autocomplete.html`** — static/local mode. Filters a provided `data` array client-side across `searchKeys`, gated by `minChars`, capped at `maxResults`.
- **`alpine_autocomplete_api.html`** — API mode. Debounced (`debounceTime`) `fetch` against `apiUrl` (`GET` puts the query in `queryParam`; `POST` sends it in the JSON body), with `transformResponse` mapping the raw response into `{ id, title, description, type }` items, plus `isLoading` / `error` state.

Shared component contract (keep these names/semantics when porting to the plugin):

- **State:** `query`, `results`, `isOpen`, `activeIndex`, `selected`.
- **Methods:** `search()`, `navigateDown()` / `navigateUp()`, `selectActive()`, `select(item)`, `close()`.
- **Keyboard:** ArrowDown/ArrowUp navigate, Enter selects the active item, Escape closes; dropdown also closes on outside click.
- **Selection:** `select()` sets `selected`, writes `item.title` back into `query`, closes the dropdown, and fires the `onSelect` callback.

The PHP side of the plugin should expose configuration that maps onto this component's config (`data`/`searchKeys`/`minChars`/`maxResults` for static mode; `apiUrl`/`queryParam`/`apiMethod`/`debounceTime`/`transformResponse` for API mode) and the per-entry view/string/HTML formatting required by the spec.
