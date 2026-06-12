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
        x-load-src="@js(\Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('autocomplete', \Giacomo\TextInputAutocomplete\TextInputAutocompleteServiceProvider::$assetPackageName))"
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
