(function( $ ) {

    $.fn.searchable = function(options){

        var settings = $.extend({
            input : "#term",
            result: "#searchable-results",
            morebutton: "#searchable-loadmore",
            noresults: "#searchable-noresults",
            template: {
                id : "#searchable-result-template"
            },
            delay: 500,
            infiniteScroll: true
        }, options);

        var formObject = this;

        var lastPage = 1;
        var currentPage = 1;

        var searchTerm = "";
        var lastTerm = "";

        var result = [];

        var timer;

        /**
         * Initializes the search plugin
         *
         */
        function init() {

            //Prevent form submit
            formObject.on("submit", function(e) {

                e.preventDefault();
            });

            //Setup more button
            $(settings.morebutton).on("click", function(){

                currentPage = currentPage + 1;
            })

            //Reset the timer each time a keyup is registered
            $(settings.input).on("keyup", function(){

                clearTimeout(timer);
                timer = setTimeout(function(){callAjaxSearch()}, settings.delay);
            })
        }

        /**
         * Calls the ajax search in fixed intervals
         *
         */
        function callAjaxSearch() {

            searchTerm = $(settings.input).val();

            //No term - clear results
            if (searchTerm == "") {

                clear();
                resetPage();
                updateUI();
            }
            //Different term than last time - clear everything and start search
            else if (searchTerm != lastTerm) {

                clear();
                resetPage();
                search();

            }
            //Same term but different page - append content (if infinite scroll is active)
            else if (currentPage != lastPage) {

                if (!settings.infiniteScroll) {

                    clear();
                }

                search();
            }

            timer = setTimeout(function(){callAjaxSearch()}, settings.delay);
        }

        function clear() {

            $(settings.result).empty();
            result = [];
        }

        function resetPage() {

            currentPage = 1;
            lastPage = 1;
        }

        function search() {

            xhr = $.ajax("/?eID=searchable_search", {
                dataType: 'json',
                data: {
                    term: searchTerm,
                    options: {
                        page : currentPage
                    }
                },
                success: function(data, textStatus, jqXHR) {

                    lastTerm = searchTerm;
                    lastPage = currentPage;
                    result = data;
                    populate();
                    updateUI();
                }
            });
        }

        function populate() {

            if (result && result.results.hits.hits.length > 0) {
                $(settings.noresults).hide();
                for (var i=0; i < result.results.hits.hits.length; i++) {
                    $(settings.result).append(renderResult(result.results.hits.hits[i]));

                }
            } else {
                $(settings.noresults).show();
            }
        }

        function updateUI() {

            if (result && result.totalPages > currentPage) {

                $(settings.morebutton).show();
            }
            else {

                $(settings.morebutton).hide();
            }
        }

        /**
         * Renders results
         *
         * @param  {Object} data Raw data
         * @return {String} The rendered result template
         */
        function renderResult(data) {

            var template = $(settings.template.id).html();

            template = template.replaceSearchableMarker("renderedLink", data._source.searchable_meta.renderedLink);
            template = template.replaceSearchableMarker("linkTitle", data._source.searchable_meta.linkTitle);
            template = template.replaceSearchableMarker("preview", data._source.searchable_meta.preview);
            template = template.replaceSearchableMarker("highlight", renderHighlight(data));
            template = template.replaceSearchableMarker("score", data._score);

            return template;
        }

        /**
         * Renders the result highlight
         *
         * @param  {Object} data
         * @return {String}
         */
        function renderHighlight(data) {

            if (data.highlight) {
                return "..." + data.highlight.searchable_highlight.join("...") + "...";
            }
            return "";
        }

        init();
        return this;
    }

    /**
     * Replaces markers in strings
     *
     * @param  {String} marker
     * @param  {String} value
     * @return {String}
     */
    String.prototype.replaceSearchableMarker = function(marker, value) {

        return this.replace("[[" + marker + "]]", value);
    }

}( jQuery ));

$(document).ready(function(){

    $(".tx-searchable .searchable-autosuggest").autocomplete({
        serviceUrl: '/',
        paramName: 'term',
        params: {
            eID: 'searchable_autosuggest'
        },
        dataType: 'json',
        deferRequestBy: 200,
        containerClass: 'searchable-autocomplete-suggestions',
        onSelect: function(suggestion)
        {
            var uri = new URI(window.location.href);
            uri.setQuery("tx_searchable[term]", suggestion.value)
                .removeQuery("cHash");

            window.location.href = uri.toString();
        },
        triggerSelectOnValidInput: false
    });

    $("#searchable-ajaxform").searchable();

});
