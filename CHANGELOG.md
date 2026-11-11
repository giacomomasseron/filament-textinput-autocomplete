# Changelog

All notable changes to `filament-textinput-autocomplete` will be documented in this file.

## v1.2.0 - 2026-11-11

### Upgrade notes

- Requires Filament 5.11 or newer (`filament/filament: ^5.11`). If you're on an older Filament 5 version, run `composer update filament/filament` first.
- A closure that returns the wrong type for a setting (e.g. `minChars(fn () => 'abc')`) now throws an `InvalidArgumentException` that names the setting, instead of a generic `TypeError`.

### Changes

- Use Filament's `#[Exposed]` attribute for the server search method, replacing the deprecated `#[ExposedLivewireMethod]`.
- Check values returned by configuration closures. Numeric strings are still accepted for number settings, and scalars, `Stringable` and `Htmlable` values for text settings.
- Static options or server search results that aren't arrays now produce an error saying so.

### Maintenance

- Run PHPStan at max level, on pull requests as well as pushes.
- Add a CHANGELOG.
- Stop tracking the PHPStan cache in git.

## v1.1.0 - 2026-11-11

### Fixes

- Fix a PHP parse error caused by unresolved merge markers in `AutocompleteInput` that shipped in v1.0.2.
- Typing or clearing the input now updates the field's state. Picking a suggestion still saves its value and shows its label (#6).
- Saved static values now display their option label (e.g. `es` shows as `Spain`), and external state changes and form resets update the input (#6).
- Server search no longer shows outdated results: older responses are ignored, old suggestions are cleared before each search, and pending searches are cancelled when the dropdown closes (#6).

### Upgrade notes

- Text typed into the field is now saved as its value, not only values picked from the suggestions. If a field must only hold one of its options, validate it on the server, e.g. with an `in` rule.

### Maintenance

- Add JavaScript regression tests and a CI workflow for them (#6).
- Pin GitHub Actions to commit SHAs, add a Dependabot cooldown and a security policy.
- Bump GitHub Actions dependencies.

## v1.0.2 - 2026-06-19

### Features

- Fix PHPStan errors

## v1.0.1 - 2026-06-19

### Features

- Use the same filament style

## v1.0 - 2026-06-13

### Features

- First version
