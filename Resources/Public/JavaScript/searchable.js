(function( $ ) {

    // Override Mustache tags since "{" and "}" are evaluated by Fluid
    Mustache.tags = ['[[', ']]'];

    $.fn.searchable = function(options){

        var settings = $.extend({
            input: "#term",
            qa_input: "#qa-searchterm",
            qabutton: "#qa-chat-button",
            result: "#searchable-results",
            morebutton: "#searchable-loadmore",
            noresults: "#searchable-noresults",
            template: $("#searchable-result-template").html(),
            delay: 300,
            infiniteScroll: true,
            callbacks: {
                searchSuccess: false, //Callback on xhr search success
                modifyResultList: false, //Callback after the results are fetched
                modifySingleResult: false //Callback before a single result is rendered. Takes one argument: "data" and should return it
            }

        }, options);

        var formObject = $(this);

        var lang = formObject.data('lang');
        var features = formObject.data('features');

        var lastPage = 1;
        var currentPage = 1;

        var searchTerm = "";
        var qasearchTerm = "";
        var lastTerm = "";

        var result = [];

        var timer;

        var template = "";

        /**
         * Initializes the search plugin
         *
         */
        function init() {

            if (formObject.length != 0) {

                template = settings.template;

                //Prevent form submit
                formObject.on("submit", function(e) {

                    e.preventDefault();
                });

                //Setup more button
                $(settings.morebutton).on("click", function(){

                    currentPage = currentPage + 1;
                });

                //Reset the timer each time a keyup is registered
                $(settings.input).on("keyup", function(){

                    clearTimeout(timer);
                    timer = setTimeout(function(){callAjaxSearch("input")}, settings.delay);
                });

                $(settings.qabutton).on("click",function(){
                    let qa_term = $(settings.qa_input).val()
                    if (qa_term.trim() !== "") {
                        sendchatmessage(qa_term, "user")
                    }
                    $("#loading-dots-field").css("display", "block");
                    $("#chat").animate({ scrollTop: $("#chat")[0].scrollHeight }, 500);
                    callAjaxSearch("button");
                })

                function switchSection() {
                    $("#main, #qa").toggle();
                }
                $("#switch-button-main").on("click", switchSection);
                $("#switch-button-qa").on("click", switchSection);
            }


        }

        function sendchatmessage(message, role) {
            let newElement = $('<div class="' + role + '"></div>');
            
            newElement.text(message);
        
            let children = $("#chat").children();
            if (children.length > 1) {
                children.eq(-1).before(newElement);
            } else {
                $("#chat").append(newElement);
            }
        }
        

        /**
         * Calls the ajax search in fixed intervals
         *
         */
        function callAjaxSearch(call) {
            searchTerm = $(settings.input).val();

            if (call == "button"){

                qasearchTerm = $(settings.qa_input).val();
                if (qasearchTerm.trim() !== "") {
                    search(call);
                }
                else{
                    sendchatmessage("Bitte gib eine Frage ein, damit ich antworten kann.", "bot");
                    $("#loading-dots-field").last().css("display","none");
                    $("#chat").animate({ scrollTop: $("#chat")[0].scrollHeight }, 500);
                }
                return

            }
            //No term - clear results
            if (searchTerm == "") {
                clear();
                resetPage();
                updateUI();

            }
            //Different term than last time - clear everything and start search
            else if ((searchTerm != lastTerm)) {
                
                clear();
                resetPage();
                search(call);

            }
            //Same term but different page - append content (if infinite scroll is active)
            else if (currentPage != lastPage) {

                if (!settings.infiniteScroll) {

                    clear();
                }

                search(call);

            }

            lastTerm = searchTerm;
            lastPage = currentPage;
            timer = setTimeout(function(){callAjaxSearch(call)}, settings.delay);
            
        }

        function clear() {

            $(settings.result).empty();
            result = [];
        }

        function resetPage() {

            currentPage = 1;
            lastPage = 1;
        }

        function search(call) {
            xhr = $.ajax("/?eID=searchable_search", {
                dataType: 'json',
                data: {
                    term: call === "button" ? qasearchTerm : searchTerm,
                    options: {
                        page : currentPage,
                        lang : lang,
                        features : features
                    }
                },
                success: function(data, textStatus, jqXHR) {

                    if (typeof(settings.callbacks.searchSuccess) === "function") {

                        settings.callbacks.searchSuccess({
                            term: searchTerm,
                            page: currentPage,
                            lang: lang,
                            features: features,
                        }, data, this);
                    }
                    result = data;
                    if(call == "button"){
                        qaajaxcall(qasearchTerm, result, $("#qa-lang").val());
                    }
                    else{
                        populate();
                        updateUI();
                    }
                }
            });
        }

        function populate() {
            if (typeof(settings.callbacks.modifyResultList) === "function") {
                result = settings.callbacks.modifyResultList(result);
            }

            if (result && result.results.hits.hits.length > 0) {
                $(settings.noresults).hide();
                for (var i=0; i < result.results.hits.hits.length; i++) {

                    if (typeof(settings.callbacks.modifySingleResult) === "function") {

                        data = settings.callbacks.modifySingleResult(result.results.hits.hits[i]);
                    }
                    else {
                        data = result.results.hits.hits[i];
                    }
                    $(settings.result).append(renderResult(data));

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

        function qaajaxcall(question, data, lang) {
            $.ajax({
                url: "/?eID=searchable_qa",
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    question: question,
                    data: { results: data.results },
                    lang: lang
                }),
                success: function(response) {
                    try {
                        var message = response.response.replace(/\n/g, "<br />");
                        console.log(message);
                        var hits = data.results.hits.hits;
        
                        if (response.index !== undefined && hits[response.index]) {
                            message += `<p style="font-size: smaller;">Quelle:</p>
                                <div style="margin-top: 10px;">
                                    <h3>
                                        <a href="${hits[response.index]._source.searchable_meta.renderedLink}" target="_blank">
                                            ${hits[response.index]._source.searchable_meta.linkTitle}
                                        </a>
                                    </h3>
                                    <p>${hits[response.index]._source.searchable_meta.preview}</p>
                                </div>
                            `;
                        }

                        let newElement = $('<div class="bot"><p>' + message + '</p></div>');
        
                        let children = $("#chat").children();
                        if (children.length > 1) {
                            children.eq(-1).before(newElement);
                        } else {
                            $("#chat").append(newElement);
                        }
                        $("#loading-dots-field").last().css("display","none");
                        $("#chat").animate({ scrollTop: $("#chat")[0].scrollHeight }, 500);
                    } catch (error) {
                        console.error("Fehler beim Verarbeiten der Antwort:", error);
                    }
                },
                error: function(xhr, status, error) {
                    if(xhr.status == 422){
                        $("#qa-article-content").html(`
                            <div style="margin-top: 10px;">
                                <p>Keine gültige Frage erkannt. Bitte geben Sie eine vollständige und verständliche Frage ein.</p>
                            </div>
                        `);
                    }
                    else{
                        console.error("AJAX-Fehler:", status, error);
                    }
                }
            });
        }

        /**
         * Renders results
         *
         * @param  {Object} data Raw data
         * @return {String} The rendered result template
         */
        function renderResult(data) {

            var output = Mustache.render(template, data);

            return output;
        }
        init();
        return $(this);
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
