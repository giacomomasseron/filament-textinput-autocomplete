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

        syncState(value) {
            if (value === this.query || (this.selected && value === (this.selected.value ?? this.selected.label))) {
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
