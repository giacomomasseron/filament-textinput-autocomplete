/**
 * Alpine component behind the autocomplete text input.
 *
 * @param {Object} config
 * @param {*} config.state Entangled Livewire state for the field.
 * @param {string} config.componentKey Schema component key used to call the server-side `search` method.
 * @param {boolean} config.serverSearch Fetch suggestions from the server instead of filtering `options`.
 * @param {Array<{value: *, label: string, html: string, keys: string[]}>} [config.options] Static options; `keys` are lowercase search terms.
 * @param {number} [config.minChars=1] Minimum query length before searching.
 * @param {number} [config.debounceTime=300] Delay in milliseconds before a server search runs.
 * @param {number} [config.maxResults=10] Maximum number of static results shown.
 * @param {string} [config.noResultsMessage='No results found']
 * @param {string} [config.loadingMessage='Loading...']
 */
export default function autocomplete(config) {
    return {
        // --- config ---
        serverSearch: config.serverSearch,
        componentKey: config.componentKey,
        options: config.options || [],
        minChars: config.minChars ?? 1,
        debounceTime: config.debounceTime ?? 300,
        maxResults: config.maxResults ?? 10,
        noResultsMessage: config.noResultsMessage || 'No results found',
        loadingMessage: config.loadingMessage || 'Loading...',

        // --- state ---
        state: config.state,
        query: '',
        results: [],
        isOpen: false,
        isLoading: false,
        error: null,
        activeIndex: -1,
        debounceTimer: null,
        requestId: 0,
        selected: null,

        init() {
            this.syncState(this.state);
            this.$watch('state', (value) => this.syncState(value));
        },

        /**
         * Reflect a state value (initial, external or form reset) into the visible input.
         *
         * Static options resolve the value to their label; anything else is shown as-is.
         * The echo of the component's own change is ignored: the current selection, or
         * an unchanged free-text edit, so typing is never swapped for an option label.
         *
         * @param {*} value The new state value.
         */
        syncState(value) {
            const unchanged = this.selected
                ? value !== null && value !== undefined && String(value) === String(this.selected.value ?? this.selected.label)
                : value === this.query;

            if (unchanged) {
                return;
            }

            this.selected = value === null || value === undefined || value === ''
                ? null
                : this.options.find((item) => String(item.value ?? item.label) === String(value)) ?? null;
            this.query = this.selected ? this.selected.label : String(value ?? '');
            this.close();
        },

        onInput(value) {
            this.selected = null;
            this.query = value;
            this.state = value;
            this.search();
        },

        /**
         * Start a search for the current query.
         *
         * Cancels any queued or in-flight search and clears old suggestions first. Below
         * `minChars` the dropdown closes; in server mode the fetch is debounced, otherwise
         * the static options are filtered immediately.
         */
        search() {
            clearTimeout(this.debounceTimer);
            this.requestId++;
            this.error = null;
            this.results = [];
            this.activeIndex = -1;

            if (this.query.length < this.minChars) {
                this.results = [];
                this.isOpen = false;
                this.isLoading = false;
                return;
            }

            if (this.serverSearch) {
                this.isOpen = true;
                this.isLoading = true;
                this.debounceTimer = setTimeout(() => this.fetchResults(), this.debounceTime);
                return;
            }

            this.results = this.filterStatic();
            this.activeIndex = -1;
            this.isOpen = true;
        },

        filterStatic() {
            const term = this.query.toLowerCase();

            return this.options
                .filter((item) => item.keys.some((value) => value.includes(term)))
                .slice(0, this.maxResults);
        },

        async fetchResults() {
            const requestId = ++this.requestId;

            try {
                const response = await this.$wire.callSchemaComponentMethod(
                    this.componentKey,
                    'search',
                    { search: this.query },
                );

                if (requestId !== this.requestId) {
                    return; // a newer request superseded this one
                }

                this.results = response.results || [];
                this.error = response.error || null;
            } catch (e) {
                if (requestId !== this.requestId) {
                    return;
                }
                this.results = [];
                this.error = e?.message || 'Search failed';
            } finally {
                if (requestId === this.requestId) {
                    this.isLoading = false;
                    this.activeIndex = -1;
                }
            }
        },

        navigateDown() {
            if (this.activeIndex < this.results.length - 1) this.activeIndex++;
        },

        navigateUp() {
            if (this.activeIndex > -1) this.activeIndex--;
        },

        selectActive() {
            if (this.activeIndex >= 0 && this.activeIndex < this.results.length) {
                this.select(this.results[this.activeIndex]);
            }
        },

        select(item) {
            this.selected = item;
            this.state = item.value ?? item.label;
            this.query = item.label;
            this.close();
        },

        close() {
            clearTimeout(this.debounceTimer);
            this.requestId++;
            this.isLoading = false;
            this.results = [];
            this.isOpen = false;
            this.activeIndex = -1;
        },

        destroy() {
            this.close();
        },
    };
}
