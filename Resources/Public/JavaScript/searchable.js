(function( $ ) {

    $.fn.searchable = function(options){

        var settings = $.extend({
            input : "#term",
            result: "#searchable-results",
            template: {
                id : "#searchable-result-template"
            },
            delay: 500
        }, options);

        var formObject = this;

        /**
         * Initializes the search plugin
         *
         */
        function init() {

            //Prevent form submit
            formObject.on("submit", function(e) {

                e.preventDefault();
            });

            //Start AJAX Search loop
            callAjaxSearch("", options);
        }

        /**
         * Calls the ajax search in fixed intervals
         *
         * @param  {String} prevTerm
         * @param  {Object} options
         */
        function callAjaxSearch(prevTerm, options) {

            var searchTerm = $(options.input).val();

            if (searchTerm != prevTerm) {

                $(options.result).empty();
            }
            if (searchTerm.length > 0 && searchTerm != prevTerm) {

                $.ajax("/?eID=searchable_search", {
                    dataType: 'json',
                    data: {
                        term: searchTerm
                    },
                    success: function(data, textStatus, jqXHR) {

                        for (var i=0; i < data.length; i++) {
                            $(options.result).append(renderResult(data[i], options));

                        }

                        setTimeout(callAjaxSearch(searchTerm, options), options.delay);
                    }
                });
            }
            else {

                setTimeout(function(){callAjaxSearch(searchTerm, options)}, options.delay);
            }
        }

        /**
         * Renders results
         *
         * @param  {Object} data Raw data
         * @param  {Object} options
         * @return {String} The rendered result template
         */
        function renderResult(data, options) {

            var template = $(options.template.id).html();

            template = template.replaceSearchableMarker("renderedLink", data._source.searchable_meta.renderedLink);
            template = template.replaceSearchableMarker("linkTitle", data._source.searchable_meta.linkTitle);
            template = template.replaceSearchableMarker("preview", data._source.searchable_meta.preview);
            template = template.replaceSearchableMarker("highlight", renderHighlight(data));
            template = template.replaceSearchableMarker("score", data._score);

            return template;
        }

        function renderHighlight(data) {

            if (data.highlight) {
                return "..." + data.highlight.searchable_highlight.join("...") + "...";
            }
            return "";
        }

        init();
        return this;
    }

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

    $("#searchable-ajaxform").searchable({
        input : "#term",
        result: "#searchable-results",
        template: {
            id : "#searchable-result-template"
        },
        delay: 500
    });

});
