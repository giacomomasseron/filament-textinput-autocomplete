<?php

namespace GiacomoMasseroni\TextInputAutocomplete\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Renderless;
use Stringable;

class AutocompleteInput extends Field
{
    protected string $view = 'filament-textinput-autocomplete::forms.components.autocomplete-input';

    /** @var array<int, array<string, mixed>>|Closure|null */
    protected array|Closure|null $options = null;

    /** @var array<int, string>|Closure */
    protected array|Closure $searchKeys = ['label'];

    protected ?Closure $getSearchResultsUsing = null;

    protected string|Closure|null $itemView = null;

    protected string|Closure $optionLabel = 'label';

    protected string|Closure $optionValue = 'value';

    protected int|Closure $minChars = 1;

    protected int|Closure $searchDebounce = 300;

    protected int|Closure $maxResults = 10;

    protected string|Closure $noResultsMessage = 'No results found';

    protected string|Closure $loadingMessage = 'Loading...';

    protected string|Closure|null $placeholder = null;

    /**
     * @param  array<int, array<string, mixed>>|Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  array<int, string>|Closure  $keys
     */
    public function searchKeys(array|Closure $keys): static
    {
        $this->searchKeys = $keys;

        return $this;
    }

    public function getSearchResultsUsing(Closure $callback): static
    {
        $this->getSearchResultsUsing = $callback;

        return $this;
    }

    public function itemView(string|Closure $view): static
    {
        $this->itemView = $view;

        return $this;
    }

    public function optionLabel(string|Closure $key): static
    {
        $this->optionLabel = $key;

        return $this;
    }

    public function optionValue(string|Closure $key): static
    {
        $this->optionValue = $key;

        return $this;
    }

    public function minChars(int|Closure $count): static
    {
        $this->minChars = $count;

        return $this;
    }

    public function searchDebounce(int|Closure $ms): static
    {
        $this->searchDebounce = $ms;

        return $this;
    }

    public function maxResults(int|Closure $count): static
    {
        $this->maxResults = $count;

        return $this;
    }

    public function noResultsMessage(string|Closure $message): static
    {
        $this->noResultsMessage = $message;

        return $this;
    }

    public function loadingMessage(string|Closure $message): static
    {
        $this->loadingMessage = $message;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return list<array<array-key, mixed>>|null
     */
    public function getOptions(): ?array
    {
        $options = $this->evaluate($this->options);

        if ($options === null) {
            return null;
        }

        return $this->ensureListOfArrays($options, 'AutocompleteInput options() must resolve to a list of arrays');
    }

    /**
     * @return list<string>
     */
    public function getSearchKeys(): array
    {
        $keys = $this->evaluate($this->searchKeys);
        $message = 'AutocompleteInput searchKeys() must resolve to a list of strings';

        if (! is_array($keys)) {
            throw $this->invalid($message, $keys);
        }

        $list = [];

        foreach ($keys as $key) {
            $list[] = is_string($key) || is_int($key) ? (string) $key : throw $this->invalid($message, $key);
        }

        return $list;
    }

    public function hasServerSearch(): bool
    {
        return $this->getSearchResultsUsing !== null;
    }

    public function getOptionLabel(): string
    {
        return $this->evaluateString($this->optionLabel, 'optionLabel');
    }

    public function getOptionValue(): string
    {
        return $this->evaluateString($this->optionValue, 'optionValue');
    }

    public function getMinChars(): int
    {
        return $this->evaluateInt($this->minChars, 'minChars');
    }

    public function getSearchDebounce(): int
    {
        return $this->evaluateInt($this->searchDebounce, 'searchDebounce');
    }

    public function getMaxResults(): int
    {
        return $this->evaluateInt($this->maxResults, 'maxResults');
    }

    public function getNoResultsMessage(): string
    {
        return $this->evaluateString($this->noResultsMessage, 'noResultsMessage');
    }

    public function getLoadingMessage(): string
    {
        return $this->evaluateString($this->loadingMessage, 'loadingMessage');
    }

    public function getPlaceholder(): ?string
    {
        $placeholder = $this->evaluate($this->placeholder);

        if ($placeholder === null) {
            return null;
        }

        return $this->toText($placeholder)
            ?? throw $this->invalid('AutocompleteInput placeholder() must resolve to a string', $placeholder);
    }

    protected function validateConfiguration(): void
    {
        if ($this->options !== null && $this->getSearchResultsUsing !== null) {
            throw new InvalidArgumentException(
                'AutocompleteInput cannot use both static options() and getSearchResultsUsing(); choose one source.',
            );
        }

        if ($this->getMaxResults() < 1) {
            throw new InvalidArgumentException('AutocompleteInput maxResults() must be at least 1.');
        }
    }

    /**
     * @return array{results: array<int, array<string, mixed>>, error: string|null}
     */
    #[Exposed]
    #[Renderless]
    public function search(string $search): array
    {
        $this->validateConfiguration();

        if ($this->getSearchResultsUsing === null) {
            return ['results' => [], 'error' => null];
        }

        try {
            $raw = $this->evaluate($this->getSearchResultsUsing, ['search' => $search]);

            $items = match (true) {
                is_array($raw) => $raw,
                $raw instanceof Collection => $raw->all(),
                default => [],
            };

            $results = array_map(
                fn (array $item) => $this->mapItem($item),
                $this->ensureListOfArrays(
                    array_slice(array_values($items), 0, $this->getMaxResults()),
                    'Each AutocompleteInput search result must be an array',
                ),
            );
        } catch (\Throwable $e) {
            return ['results' => [], 'error' => $e->getMessage()];
        }

        return ['results' => $results, 'error' => null];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOptionsForJs(): array
    {
        $this->validateConfiguration();

        $options = $this->getOptions() ?? [];

        return array_map(fn (array $item) => $this->mapItem($item), $options);
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @return array<string, mixed>
     */
    protected function mapItem(array $item): array
    {
        $searchable = array_map(
            fn (string $key): string => mb_strtolower($this->itemText($item, $key)),
            $this->getSearchKeys(),
        );

        return [
            'value' => $item[$this->getOptionValue()] ?? null,
            'label' => $this->itemText($item, $this->getOptionLabel()),
            'html' => $this->renderItem($item),
            'keys' => $searchable,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $item
     */
    protected function renderItem(array $item): string
    {
        $itemView = $this->itemView;

        if ($itemView instanceof Closure) {
            $html = $this->evaluate($itemView, ['item' => $item]) ?? '';

            return $this->toText($html)
                ?? throw $this->invalid('AutocompleteInput itemView() closure must return a string or Htmlable', $html);
        }

        if (is_string($itemView)) {
            if (view()->exists($itemView)) {
                return view($itemView, ['item' => $item])->render();
            }

            return preg_replace_callback(
                '/\{(\w+)\}/',
                fn (array $m): string => e($this->itemText($item, $m[1])),
                $itemView,
            ) ?? '';
        }

        return e($this->itemText($item, $this->getOptionLabel()));
    }

    /**
     * Read an option field as text; a missing or null field is an empty string.
     *
     * @param  array<array-key, mixed>  $item
     */
    protected function itemText(array $item, string $key): string
    {
        $value = $item[$key] ?? '';

        return $this->toText($value)
            ?? throw $this->invalid("AutocompleteInput option field [{$key}] must be a string", $value);
    }

    protected function evaluateString(string|Closure $value, string $setting): string
    {
        $result = $this->evaluate($value);

        return $this->toText($result)
            ?? throw $this->invalid("AutocompleteInput {$setting}() must resolve to a string", $result);
    }

    protected function evaluateInt(int|Closure $value, string $setting): int
    {
        $result = $this->evaluate($value);

        if (is_int($result)) {
            return $result;
        }

        $int = is_string($result) ? filter_var($result, FILTER_VALIDATE_INT) : false;

        return $int !== false ? $int : throw $this->invalid("AutocompleteInput {$setting}() must resolve to an integer", $result);
    }

    /**
     * Convert values PHP would accept as a string (scalars, Stringable, Htmlable); null for anything else.
     */
    protected function toText(mixed $value): ?string
    {
        return match (true) {
            is_string($value) => $value,
            $value instanceof Htmlable => $value->toHtml(),
            is_int($value), is_float($value), is_bool($value) => (string) $value,
            $value instanceof Stringable => (string) $value,
            default => null,
        };
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    protected function ensureListOfArrays(mixed $value, string $message): array
    {
        if (! is_array($value)) {
            throw $this->invalid($message, $value);
        }

        $list = [];

        foreach ($value as $item) {
            $list[] = is_array($item) ? $item : throw $this->invalid($message, $item);
        }

        return $list;
    }

    protected function invalid(string $message, mixed $value): InvalidArgumentException
    {
        return new InvalidArgumentException("{$message}, ".get_debug_type($value).' given.');
    }
}
