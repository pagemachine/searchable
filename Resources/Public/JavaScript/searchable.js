
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
			uri.setQuery("tx_searchable[term]", suggestion.value);

			window.location.href = uri.toString();
		},
		triggerSelectOnValidInput: false
	});


});
