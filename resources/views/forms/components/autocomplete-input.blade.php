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
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('autocomplete', \Giacomo\TextInputAutocomplete\TextInputAutocompleteServiceProvider::$assetPackageName) }}"
        x-data="autocomplete({
            state: $wire.$entangle('{{ $statePath }}'),
            componentKey: @js($key),
            serverSearch: @js($field->hasServerSearch()),
            options: @js($field->getOptionsForJs()),
            minChars: @js($field->getMinChars()),
            debounceTime: @js($field->getSearchDebounce()),
            maxResults: @js($field->getMaxResults()),
            noResultsMessage: @js($field->getNoResultsMessage()),
            loadingMessage: @js($field->getLoadingMessage()),
        })"
        class="fi-ac-wrapper"
        @click.outside="close()"
    >
        <input
            type="text"
            class="fi-ac-input"
            x-model="query"
            @input="search()"
            @focus="search()"
            @keydown.down.prevent="navigateDown()"
            @keydown.up.prevent="navigateUp()"
            @keydown.enter.prevent="selectActive()"
            @keydown.escape="close()"
            autocomplete="off"
            placeholder="{{ $getPlaceholder() }}"
            @disabled($isDisabled())
        />

        <div
            x-show="isOpen"
            x-transition
            class="fi-ac-dropdown"
            x-cloak
        >
            <template x-if="isLoading">
                <div class="fi-ac-empty" x-text="loadingMessage"></div>
            </template>

            <template x-for="(item, index) in results" :key="index">
                <div
                    @click="select(item)"
                    @mouseenter="activeIndex = index"
                    :class="index === activeIndex ? 'fi-ac-item--active' : ''"
                    class="fi-ac-item"
                    x-html="item.html"
                ></div>
            </template>

            <template x-if="! isLoading && ! results.length && error">
                <div class="fi-ac-empty fi-ac-empty--error" x-text="error"></div>
            </template>

            <template x-if="! isLoading && ! results.length && ! error && query.length >= minChars">
                <div class="fi-ac-empty" x-text="noResultsMessage"></div>
            </template>
        </div>
    </div>
</x-dynamic-component>
