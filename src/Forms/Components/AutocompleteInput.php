<?php

namespace GiacomoMasseroni\TextInputAutocomplete\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;

class AutocompleteInput extends Field
{
    protected string $view = 'filament-textinput-autocomplete::forms.components.autocomplete-input';

    protected array|Closure|null $options = null;

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

    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

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

    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder);
    }

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

    #[ExposedLivewireMethod]
    #[Renderless]
    public function search(string $search): array
    {
        $this->validateConfiguration();

        if ($this->getSearchResultsUsing === null) {
            return ['results' => [], 'error' => null];
        }

        try {
            $raw = collect($this->evaluate($this->getSearchResultsUsing, ['search' => $search]) ?? [])->all();

            $results = array_map(
                fn (array $item) => $this->mapItem($item),
                array_slice(array_values($raw), 0, $this->getMaxResults()),
            );
        } catch (\Throwable $e) {
            return ['results' => [], 'error' => $e->getMessage()];
        }

        return ['results' => $results, 'error' => null];
    }

    public function getOptionsForJs(): array
    {
        $this->validateConfiguration();

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

    protected function renderItem(array $item): string
    {
        $itemView = $this->itemView;

        if ($itemView instanceof Closure) {
            return (string) $this->evaluate($itemView, ['item' => $item]);
        }

        if (is_string($itemView)) {
            if (view()->exists($itemView)) {
                return view($itemView, ['item' => $item])->render();
            }

            return preg_replace_callback(
                '/\{(\w+)\}/',
                fn (array $m) => e((string) ($item[$m[1]] ?? '')),
                $itemView,
            );
        }

        $label = $this->getOptionLabel();

        return e((string) ($item[$label] ?? ''));
    }
}
