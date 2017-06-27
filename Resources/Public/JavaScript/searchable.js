
$(document).ready(function(){

	$(".tx-searchable #term").autocomplete({
		serviceUrl: '/',
		paramName: 'term',
		params: {
			eID: 'searchable_autosuggest'
		},
		dataType: 'json',
		deferRequestBy: 200,
		containerClass: 'searchable-autocomplete-suggestions'
	});


});
