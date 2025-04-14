// Override Mustache tags since "{" and "}" are evaluated by Fluid
Mustache.tags = ['[[', ']]'];

class Searchable {
    constructor(formElement, options = {}) {
        this.settings = {
            input: "#term",
            result: "#searchable-results",
            morebutton: "#searchable-loadmore",
            noresults: "#searchable-noresults",
            template: document.querySelector("#searchable-result-template")?.innerHTML,
            delay: 300,
            infiniteScroll: true,
            callbacks: {
                searchSuccess: false, //Callback on xhr search success
                modifyResultList: false, //Callback after the results are fetched
                modifySingleResult: false //Callback before a single result is rendered. Takes one argument: "data" and should return it
            },
            ...options
        };

        this.form = formElement;
        this.lang = this.form.dataset.lang;
        this.features = JSON.parse(this.form.dataset.features);
        this.lastPage = 1;
        this.currentPage = 1;
        this.searchTerm = "";
        this.lastTerm = "";
        this.result = [];
        this.timer = null;
        this.template = "";

        this.init();
    }

    init() {
        if (!this.form) return;

        this.template = this.settings.template;

        // Prevent form submit
        this.form.addEventListener('submit', (e) => e.preventDefault());

        // Setup more button
        const moreButton = document.querySelector(this.settings.morebutton);
        moreButton?.addEventListener('click', () => {
            this.currentPage = this.currentPage + 1;
        });

        // Setup search input
        const input = document.querySelector(this.settings.input);
        input?.addEventListener('keyup', () => {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.callAjaxSearch(), this.settings.delay);
        });
    }

    async callAjaxSearch() {
        const input = document.querySelector(this.settings.input);
        this.searchTerm = input.value;

        if (this.searchTerm === "") {
            // No term - clear results
            this.clear();
            this.resetPage();
            this.updateUI();
        } else if (this.searchTerm !== this.lastTerm) {
            // Different term than last time - clear everything and start search
            this.clear();
            this.resetPage();
            await this.search();
        } else if (this.currentPage !== this.lastPage) {
            // Same term but different page - append content (if infinite scroll is active)
            if (!this.settings.infiniteScroll) {
                this.clear();
            }
            await this.search();
        }

        this.lastTerm = this.searchTerm;
        this.lastPage = this.currentPage;
        this.timer = setTimeout(() => this.callAjaxSearch(), this.settings.delay);
    }

    clear() {
        const resultElement = document.querySelector(this.settings.result);
        if (resultElement) resultElement.innerHTML = '';
        this.result = [];
    }

    resetPage() {
        this.currentPage = 1;
        this.lastPage = 1;
    }

    async search() {
        try {
            const response = await fetch(
                '/?' + encodeNestedObject({
                    eID: 'searchable_search',
                    term: this.searchTerm,
                    options: {
                        page: this.currentPage,
                        lang: this.lang,
                        features: this.features
                    }
                }).join('&'),
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                }
            );

            const data = await response.json();

            if (typeof this.settings.callbacks.searchSuccess === "function") {
                this.settings.callbacks.searchSuccess({
                    term: this.searchTerm,
                    page: this.currentPage,
                    lang: this.lang,
                    features: this.features,
                }, data, this);
            }

            this.result = data;
            this.populate();
            this.updateUI();
        } catch (error) {
            console.error('Search failed:', error);
        }
    }

    populate() {
        if (typeof this.settings.callbacks.modifyResultList === "function") {
            this.result = this.settings.callbacks.modifyResultList(this.result);
        }

        const resultElement = document.querySelector(this.settings.result);
        const noResultsElement = document.querySelector(this.settings.noresults);

        if (this.result && this.result.results.hits.hits.length > 0) {
            noResultsElement.style.display = 'none';
            this.result.results.hits.hits.forEach(hit => {
                let data = hit;
                if (typeof this.settings.callbacks.modifySingleResult === "function") {
                    data = this.settings.callbacks.modifySingleResult(hit);
                }
                resultElement.insertAdjacentHTML('beforeend', this.renderResult(data));
            });
        } else {
            noResultsElement.style.display = 'block';
        }
    }

    updateUI() {
        const moreButton = document.querySelector(this.settings.morebutton);
        if (moreButton) {
            if (this.result && this.result.totalPages > this.currentPage) {
                moreButton.style.display = 'block';
            } else {
                moreButton.style.display = 'none';
            }
        }
    }

    renderResult(data) {
        return Mustache.render(this.template, data);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector("#searchable-ajaxform");
    if (form) new Searchable(form);
});

function encodeNestedObject(obj, prefix = '') {
  const params = [];

  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      const paramKey = prefix ? `${prefix}[${key}]` : key;

      if (typeof obj[key] === 'object' && obj[key] !== null) {
        params.push(...encodeNestedObject(obj[key], paramKey));
      } else {
        params.push(`${encodeURIComponent(paramKey)}=${encodeURIComponent(obj[key])}`);
      }
    }
  }

  return params;
}
