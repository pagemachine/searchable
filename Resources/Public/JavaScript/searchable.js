
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

	$('.js-typeahead').typeahead({
	    minLength: 1,
	    offset: true,
	    hint: false,
	    dynamic: true,
	    filter: false,
	    source: {
	        hits : {
	            ajax: {
	                type: "GET",
	                url: "/?eID=searchable_search",
	                data: {
	                	term: "{{query}}"
	                },
	            },
	        }
	    },
	    template: function(query, item){

	    	content = "<span class='searchable-result-title'>{{_source.searchable_meta.linkTitle}}</span>";

	    	if (item.highlight != undefined)
	    	{
	    		content += "<div class='result-highlight'>...";
				for (var i = 0, len = item.highlight.searchable_highlight.length; i < len; i++) {
					content += item.highlight.searchable_highlight[i];
					content += "...";
				}
				content += "</div>";
	    	}
	    	return content;

	    },
	    emptyTemplate: 'No result for "{{query}}"',
	    href: "{{_source.searchable_meta.renderedLink}}",
	    selector: {
	        container: "searchable-typeahead",
	        result: "searchable-typeahead-field",
	        list: "list-group searchable-typeahead-list",
	        group: "searchable-typeahead-group",
	        item: "list-group-item searchable-typeahead-item",
	        empty: "searchable-typeahead-empty",
	        display: "searchable-typeahead-display",
	        query: "searchable-typeahead-query",
	        filter: "searchable-typeahead-filter",
	        filterButton: "searchable-typeahead-filter-button",
	        dropdown: "searchable-typeahead-dropdown",
	        dropdownItem: "searchable-typeahead-dropdown-item",
	        button: "searchable-typeahead-button",
	        backdrop: "searchable-typeahead-backdrop",
	        hint: "searchable-typeahead-hint",
	        cancelButton: "searchable-typeahead-cancel-button"
    	},
    	callback: {
    		onSubmit: function(node, form, item, event) {

    			event.preventDefault();
    		}
    	}
	});
});
