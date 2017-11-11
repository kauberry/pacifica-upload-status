var current_proposal_id;
var current_instrument_id;
var current_timeframe;
// var currently_updating = false;
var spinner_opts = {
    lines: 9, // The number of lines to draw
    length: 4, // The length of each line
    width: 2, // The line thickness
    radius: 4, // The radius of the inner circle
    corners: 1, // Corner roundness (0..1)
    rotate: 0, // The rotation offset
    direction: 1, // 1: clockwise, -1: counterclockwise
    color: "#111", // #rgb or #rrggbb or array of colors
    speed: 1, // Rounds per second
    trail: 60, // Afterglow percentage
    shadow: false, // Whether to render a shadow
    hwaccel: true, // Whether to use hardware acceleration
    className: "spinner", // The CSS class to assign to the spinner
    zIndex: 2e9, // The z-index (defaults to 2000000000)
    top: "50%", // Top position relative to parent
    left: "50%" // Left position relative to parent
};
var cookie_base = "myemsl_status_last_";


$(function() {
    current_proposal_id = $.cookie(cookie_base + "proposal_selector");
    current_proposal_id = current_proposal_id != "null" ? current_proposal_id : -1;
    current_instrument_id = $.cookie(cookie_base + "instrument_selector");
    current_instrument_id = current_instrument_id != "null" ? current_instrument_id : -1;
    current_timeframe = $.cookie(cookie_base + "timeframe_selector");
    current_timeframe = current_timeframe == undefined ? 2 : current_timeframe;

    setup_selectors(true);
    if ($("#proposal_selector").val()) {
        current_proposal_id = $("#proposal_selector").val();
        get_instrument_list(current_proposal_id);
    }
    cart_status();
});


var setup_selectors = function(initial_load) {
    $("#timeframe_selector")
        .select2({
            placeholder: "Select a Time Frame..."
        })
        .off("change")
        .on("change", update_content);

    if (current_proposal_id == undefined || initial_load) {
        $("#instrument_selector")
            .select2({
                placeholder: "Select an Instrument..."
            });
    }

    $("#proposal_selector")
        .select2({
            ajax: {
                dataType: "json",
                delay: 250,
                cache: true,
                url: function(params) {
                    var myURL = base_url + "ajax_api/get_proposals_by_name/";
                    if (params.term != undefined) {
                        myURL += params.term;
                    }
                    return myURL;

                },
                data: function(params) {
                    return "";
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page * 300) < data.total_count
                        }
                    };
                }
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            templateResult: formatProposal,
            templateSelection: formatProposalSelection,
            placeholder: "Please Select an EUS Proposal...",
        })
        .off("change")
        .on("change", update_content);
};

var formatProposal = function(item) {
    var markup = false;
    var start_date = moment(item.start_date);
    var end_date = moment(item.end_date);
    var start_date_string = start_date.isValid() ? start_date.format("MM/DD/YYYY") : "&mdash;&mdash;";
    var end_date_string = end_date.isValid() ? end_date.format("MM/DD/YYYY") : "&mdash;&mdash;";

    if (item.loading) return item.text;
    if (item.id.length > 0) {
        markup = "<div id=\"prop_info_" + item.id + " class=\"prop_info\">";
        markup += "   <div class=\"";
        markup += item.currently_active == "yes" ? "active" : "inactive";
        markup += "_proposal\"><strong>Proposal " + item.id + "</strong>";
        markup += "   </div>";
        markup += "   <div style=\"float:right;\">";
        markup += "     <span class=\"active_dates\">";
        if (item.currently_active == "yes" && item.state == "active") {
            markup += "Active Through " + end_date_string;
        } else if (item.currently_active == "no") {
            if (item.state == "preactive") {
                markup += "Inactive Until " + start_date_string;
            } else {
                if (!start_date.isValid() || !end_date.isValid()) {
                    markup += "Invalid Start/End Dates";
                } else {
                    markup += "Inactive Since " + end_date_string;
                }
            }
        }
        markup += "     </span>";
        markup += "   </div>";
        markup += "</div>";
        markup += "<div class=\"prop_description\">" + item.title + "</div>";
    }
    return markup;
};

var formatProposalSelection = function(item) {
    var markup = "Please Select an EUS Proposal...";
    if (item.id.length > 0) {
        markup = "<span title=\"" + item.title + "\">" + item.text + "</span>";
    }
    return markup;
};

var get_instrument_list = function(proposal_id) {
    $("#instrument_selector").off("change");
    var inst_url = base_url + "ajax_api/get_instruments_for_proposal/" + proposal_id;
    var target = document.getElementById("instrument_selector_spinner");
    var spinner = new Spinner(spinner_opts).spin(target);
    $("#instrument_selector").empty();
    $.getJSON(
        inst_url,
        function(data) {
            $("#instrument_selector").select2({
                data: data.items,
                placeholder: "Select an Instrument...",
                templateResult: formatInstrument,
                templateSelection: formatInstrumentSelection,
                matcher: my_matcher,
                escapeMarkup: function(markup) {
                    return markup;
                }
            });
            $("#instrument_selector").enable();
            initial_instrument_list = [];

            $.each(
                data.items,
                function(index, item) {
                    initial_instrument_list.push(item.id);
                }
            );
            spinner.stop();
            if (initial_instrument_list.indexOf(current_instrument_id) < 0) {
                $("#instrument_selector").val("").trigger("change");
            } else {
                $("#instrument_selector").val(parseInt(current_instrument_id, 10)).trigger("change");
                // update_content();
            }
        }
    );
    $("#instrument_selector").on("change", update_content);
};

var formatInstrument = function(item) {
    if (item.loading) return item.text;
    var markup = false;
    var current_proposal_id = $("#proposal_selector").val();
    var active = item.active == "Y" ? "active" : "inactive";
    if (item.id) {
        if (item.id > 0) {
            markup = "<div id=\"inst_info_" + item.id + "\" class=\"inst_info\">";
            markup += "  <div class=\"" + active + "_instrument\">";
            markup += "     <strong>Instrument " + item.id + "</strong>";
            markup += "  </div>";
            markup += "  <div class=\"inst_description\">" + item.name + "</div>";
            markup += "</div>";
        } else if (item.id == -1) {
            markup = "<div id=\"inst_info_" + item.id + "\" class=\"inst_info\">";
            markup += "<strong>All Instruments for Proposal " + current_proposal_id + "</strong>";
            markup += "</div>";
        }
    }

    return markup;
};

var formatInstrumentSelection = function(item) {
    var markup = "Select an Instrument...";
    var current_proposal_id = $("#proposal_selector").val();
    if (item.id > 0) {
        markup = item.text;
    } else if (item.id < 0) {
        markup = "All Instruments for Proposal " + current_proposal_id;
    }
    return markup;
};

var my_matcher = function(params, data) {
    // Always return the object if there is nothing to compare
    // data.text should only be blank for the placeholder, return the item
    if ($.trim(params.term) === "" || $.trim(data.text) === "") {
        return data;
    }

    var original = data.text.toUpperCase();
    var termstring = params.term.toUpperCase();

    var terms = termstring.split(" ");
    terms = $.isArray(terms) ? terms : [terms];
    var is_match = false;

    $.each(
        terms,
        function(index, term) {
            // Check if the text contains the term
            if (original.indexOf(term) > -1) {
                is_match = true;
            } else {
                is_match = false;
            }
            return is_match;
        }
    );

    if (is_match === true) {
        return data;
    }

    // If it doesn't contain the term, don't return anything
    return null;

};

var update_content = function(event) {
    var ts = moment().format("YYYYMMDDHHmmss");
    current_proposal_id = $("#proposal_selector").val() != null ? $("#proposal_selector").val() : current_proposal_id;
    current_instrument_id = $("#instrument_selector").val() != null ? $("#instrument_selector").val() : current_instrument_id;
    current_timeframe = $("#timeframe_selector").val() != null ? $("#timeframe_selector").val() : current_timeframe;
    setup_selectors(false);

    if (event) {
        var el = $(event.target);
        if (el.val() != null) {
            if (el.prop("id") == "proposal_selector" && el.val() != null) {
                get_instrument_list(el.val());
            }
            $.cookie(cookie_base + el.prop("id"), el.val());
        }
    }

    if (current_proposal_id != 0 && current_instrument_id != 0 && current_timeframe > 0) {
        var url = base_url + "status_api/overview_worker/" + current_proposal_id + "/" + current_instrument_id + "/" + current_timeframe + "?ovr_" + ts;
        $("#item_info_container").hide();
        $("#loading_status").fadeIn(
            "slow",
            function() {
                // $(".criterion_selector").off("change");
                var getting = $.get(url);
                getting.done(
                    function(data) {
                        if (data) {
                            $("#loading_status").fadeOut(
                                200,
                                function() {
                                    $("#item_info_container").html(data);
                                    $("#item_info_container").fadeIn(
                                        "slow",
                                        function() {
                                            setup_tree_data();
                                            setup_metadata_disclosure();
                                            setup_hover_info();
                                        }
                                    );
                                }
                            );
                        }
                    }
                );
                getting.fail(
                    function(jqxhr, textStatus, error) {
                        $("#loading_status").fadeOut(
                            200,
                            function() {
                                $("#info_message_container h2").html("An Error occurred during refresh");
                                $("#info_message_container").append("<span class=\"fineprint\">" + error + "</span>");
                                $("#info_message_container").show();
                            }
                        );
                    }
                );
            }
        );
    }
};

var setup_hover_info = function() {
    $(".status_item_bar").each(
        function(index, item) {
            var el = $(item);
            var block_type = "status_block_" + el.prop("id").slice(-1);
            var status = el.hasClass("red_bar_end") ? "success" : "failure";
            // var info = item_info_obj[block_type][status];
            el.attr("title", item_info_obj[block_type]["title"]);
        }
    );
    $(".status_item_bar").tooltip({
        show: true,
        position: {
            my: "right top+10",
            at: "right center"
        }
    });

};
