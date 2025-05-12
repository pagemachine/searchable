class SearchableAutosuggest {
    constructor(element) {
        this.input = element;
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'searchable-autocomplete-suggestions';
        this.input.parentNode.insertBefore(this.suggestionsContainer, this.input.nextSibling);
        this.debounceTimeout = null;

        this.setupEventListeners();
    }

    setupEventListeners() {
        this.input.addEventListener('input', () => {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => this.fetchSuggestions(), 200);
        });
    }

    async fetchSuggestions() {
        const term = this.input.value;
        if (!term) {
            this.suggestionsContainer.innerHTML = '';
            return;
        }

        const params = new URLSearchParams({
            term: term,
            options: {},
            eID: 'searchable_autosuggest'
        });

        try {
            const response = await fetch(`/?${params}`);
            const suggestions = await response.json();
            this.displaySuggestions(suggestions);
        } catch (error) {
            console.error('Error fetching suggestions:', error);
        }
    }

    displaySuggestions(suggestions) {
        this.suggestionsContainer.innerHTML = '';

        suggestions.suggestions.forEach(suggestion => {
            const div = document.createElement('div');
            div.className = 'autocomplete-suggestion';
            div.textContent = suggestion;
            div.addEventListener('click', () => this.onSelect(suggestion));
            this.suggestionsContainer.appendChild(div);
        });
    }

    onSelect(suggestion) {
        const url = new URL(window.location.href);
        url.searchParams.set('tx_searchable[term]', suggestion);
        url.searchParams.delete('cHash');
        window.location.href = url.toString();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tx-searchable .searchable-autosuggest').forEach(element => {
        new SearchableAutosuggest(element);
    });
});
