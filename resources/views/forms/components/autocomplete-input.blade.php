@php
    $statePath = $getStatePath();
    $key = $getKey();
@endphp

@once
    <style>
        .fi-ac-wrapper {
            position: relative;
        }

        .fi-ac-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            outline: none;
            background: #fff;
            transition: border-color 0.2s;
        }

        .fi-ac-input:focus {
            border-color: #667eea;
        }

        .fi-ac-input:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }

        .fi-ac-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 8px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .fi-ac-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f5f5f5;
            transition: background-color 0.15s;
        }

        .fi-ac-item:last-child {
            border-bottom: none;
        }

        .fi-ac-item:hover,
        .fi-ac-item.fi-ac-item--active {
            background-color: #f8f9ff;
        }

        .fi-ac-empty {
            padding: 20px;
            text-align: center;
            color: #999;
        }

        .fi-ac-empty--error {
            color: #e53e3e;
        }
    </style>
@endonce

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
