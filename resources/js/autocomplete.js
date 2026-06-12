export default function autocomplete(config) {
    return {
        // --- config ---
        serverSearch: config.serverSearch,
        componentKey: config.componentKey,
        options: config.options || [],
        searchKeys: config.searchKeys || ['label'],
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

        init() {
            // Reflect any pre-existing state value into the visible input.
            if (this.state) {
                this.query = this.state;
            }
        },

        search() {
            clearTimeout(this.debounceTimer);
            this.error = null;

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
            try {
                const response = await this.$wire.callSchemaComponentMethod(
                    this.componentKey,
                    'search',
                    { search: this.query },
                );
                this.results = response.results || [];
                this.error = response.error || null;
            } catch (e) {
                this.results = [];
                this.error = e?.message || 'Search failed';
            } finally {
                this.isLoading = false;
                this.activeIndex = -1;
            }
        },

        navigateDown() {
            if (this.activeIndex < this.results.length - 1) this.activeIndex++;
        },

        navigateUp() {
            if (this.activeIndex > 0) this.activeIndex--;
        },

        selectActive() {
            if (this.activeIndex >= 0 && this.activeIndex < this.results.length) {
                this.select(this.results[this.activeIndex]);
            }
        },

        select(item) {
            this.state = item.value ?? item.label;
            this.query = item.label;
            this.close();
        },

        close() {
            this.isOpen = false;
            this.activeIndex = -1;
        },
    };
}
