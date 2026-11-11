# Changelog

All notable changes to `filament-textinput-autocomplete` will be documented in this file.

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
