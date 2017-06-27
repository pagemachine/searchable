
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
		onSelect: function(suggestion) {
			console.log(suggestion);
			var newURL = updateURLParameter(window.location.href, encodeURIComponent('tx_searchable[term]'), suggestion.value);

			window.location.href = newURL;

		},
		triggerSelectOnValidInput: false
	});


});

/**
 * Changes one parameter in a given URL
 * Used to replace the term when selecting a suggestion
 *
 * @see https://stackoverflow.com/a/10997390
 *
 */
function updateURLParameter(url, param, paramVal)
{
    var TheAnchor = null;
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";

    if (additionalURL) 
    {
        var tmpAnchor = additionalURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor = tmpAnchor[1];
        if(TheAnchor)
            additionalURL = TheParams;

        tempArray = additionalURL.split("&");

        for (var i=0; i<tempArray.length; i++)
        {
            if(tempArray[i].split('=')[0] != param)
            {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }        
    }
    else
    {
        var tmpAnchor = baseURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor  = tmpAnchor[1];

        if(TheParams)
            baseURL = TheParams;
    }

    if(TheAnchor)
        paramVal += "#" + TheAnchor;

    var rows_txt = temp + "" + param + "=" + paramVal;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}
