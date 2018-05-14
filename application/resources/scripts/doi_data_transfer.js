var setup_metadata_disclosure = function(){ // eslint-disable-line no-unused-vars
    $("ul.metadata_container").hide();
    $(".disclosure_button").off("click").on("click",
        function(){
            var el = $(this);
            var container = el.parentsUntil("div").siblings(".metadata_container");
            if(el.hasClass("dc_up")) {
                //view is rolled up and hidden
                el.removeClass("dc_up").addClass("dc_down");
                container.slideDown(200);
            }else {
                //view is open and visible
                el.removeClass("dc_down").addClass("dc_up");
                container.slideUp(200);
            }
        }
    );

};

var setup_tree_data = function(){ // eslint-disable-line no-unused-vars
    $(".tree_holder").each(
        function(index, el){
            if($(el).find("ul.ui-fancytree").length == 0) {
                var el_id = $(el).prop("id").replace("tree_","");
                $(el).fancytree(
                    {
                        selectMode: 3,
                        lazyLoad: function(event, data){
                            var node = data.node;
                            data.result = {
                                url: base_url + "file_tree",
                                data: {mode: "children", parent: node.key},
                                method:"POST",
                                cache: false
                            };
                        },
                        cookieId: "fancytree_tx_" + el_id,
                        idPrefix: "fancytree_tx_" + el_id + "-"
                    }
                );
            }
        }
    );
};

var build_staging_button = function(transaction_id){
    var content =
        $("<div>", {
            "class": "staging_buttons buttons"
        });
    var staging_button = $("<input>", {
        "value": "Stage for Release",
        "class": "staging_button"
    }).attr({
        "type": "button",
        "id" : "staging_" + transaction_id,
        "name": "staging_" + transaction_id
    });
    content.append(staging_button);
    return content;
};

var set_release_state_banners = function(release_states, selector){
    $(selector).each(function(index, el){
        el = $(el);
        var txn_id = el.find(".transaction_identifier").val();
        var ribbon_el = el.find(".ribbon");
        var release_info = release_states[txn_id];
        if(release_info.release_state == "not_released"){
            var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
            if(!$.isEmptyObject(current_session_contents) && txn_id in current_session_contents){
                release_info.release_state = "staged";
                release_info.display_state = "Staged";
            }else{
                release_info.release_state = "not_released";
                release_info.display_state = "Not Released";
                var content = build_staging_button(txn_id);
                el.find("legend").after(content);
                el.find(".staging_button").off().on("click", function(event){
                    stage_transaction($(event.target));
                });
            }
        }else{
            var pub_status_block = el.next(".publication_status_block");
            el.find(".upload_url").attr({"href": external_release_base_url + "released_data/" + txn_id});
            if(release_info.release_doi_entries != null){
                var lb = pub_status_block.find(".publication_left_block");
                lb.empty();
                lb.append($("<div>", {"class": "reference_header", "text": "Published DOI References"}));
                var list = $("<ul/>").appendTo(lb);
                $.each(release_info.release_doi_entries, function(index, item){
                    $("<li/>").appendTo(list).append($("<a/>", {
                        "href": format_doi_ref(item.doi_reference),
                        "text": item.doi_name,
                        "title": "DOI Reference: " + item.doi_reference
                    }));
                });
                pub_status_block.show();
            }
            if(release_info.release_citations != null){
                var rb = pub_status_block.find(".publication_right_block");
                rb.empty();
                rb.append($("<div>", {"class": "reference_header", "text": "Published Citations"}));
                list = $("<ul/>").appendTo(rb);
                $.each(release_info.release_citations, function(index, item){
                    $("<li/>").appendTo(list).append($("<a/>", {
                        "href": format_doi_ref(item.doi_reference),
                        "text": item.title + " " + item.title + " " + item.title,
                    }));
                });
                pub_status_block.show();
            }
        }
        el.find(".release_state").next("td.metadata_item").text(release_info.release_state);
        el.find(".release_state_display").next("td.metadata_item").text(release_info.display_state);
        ribbon_el.removeClass().addClass("ribbon").addClass(release_info.release_state);
        ribbon_el.find("span").text(release_info.display_state);

    });
};

var format_doi_ref = function(doi_reference){
    return "https://dx.doi.org/" + doi_reference;
};

var setup_staging_buttons = function(){
    var release_check_url = base_url + "ajax_api/get_release_states";
    var my_transactions = $(".fieldset_container .transaction_identifier").map(function(){
        return $(this).val();
    }).toArray();
    $.post(
        release_check_url, JSON.stringify(my_transactions)
    )
        .done(
            function(data){
                set_release_state_banners(JSON.parse(data), ".fieldset_container");
            }
        )
        .fail(
            function(jqxhr, error, message){
                alert("A problem occurred creating your cart.\n[" + message + "]");
            }
        );

};

var clear_selections = function(){
    sessionStorage.clear();
    update_staged_transactions_view();
};

var submit_selections = function(event){
    var el = $(event.target);
    var table_rows = el.parents(".transfer_cart").find("table > tbody > tr");
    table_rows.each(function(index, item){
        var transaction_id = parseInt($(item).find(".upload_id").text(),10);
        var release_url = base_url + "ajax_api/set_release_state/" + transaction_id + "/released";
        $.get(release_url, function(data){
            var ribbon = $("#fieldset_container_" + transaction_id + " .ribbon");
            ribbon.removeClass().addClass("ribbon").addClass(data.release_state);
            ribbon.find("span").text(data.display_state);
            set_staged_transaction_completed(transaction_id);
            update_staged_transactions_view();
        });
    });
};

var set_staged_transaction_completed = function(upload_id){
    var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
    delete current_session_contents[upload_id];
    sessionStorage.setItem("staged_releases", JSON.stringify(current_session_contents));
    update_staged_transactions_view();
};

var unstage_transaction = function(el){
    el = $(el);
    var txn_id = parseInt($(el).parents("tr").find(".upload_id").text(),10);
    var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
    delete current_session_contents[txn_id];
    sessionStorage.setItem("staged_releases", JSON.stringify(current_session_contents));
    var container = $("#fieldset_" + txn_id).parents(".fieldset_container");
    var banner = container.find(".ribbon");
    banner.removeClass().addClass("ribbon").addClass("not_released");
    banner.find("span").text("Not Released");
    update_staged_transactions_view();
};

var stage_transaction = function(el){
    var container = el.parents(".transaction_container");
    var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
    var txn_id = container.find(".transaction_identifier").val();
    var new_info = {
        "upload_id": txn_id,
        "proposal_id": container.find(".proposal_identifier").val(),
        "proposal_name": container.find(".proposal_identifier").prop("title"),
        "instrument_id": container.find(".instrument_identifier").val(),
        "instrument_name": container.find(".instrument_identifier").prop("title"),
        "date_uploaded": moment(container.find(".submit_time_identifier").val()).toISOString()
    };
    if(current_session_contents == null){
        current_session_contents = {};
    }
    current_session_contents[txn_id] = new_info;
    sessionStorage.setItem("staged_releases", JSON.stringify(current_session_contents));
    update_staged_transactions_view();
    var banner = container.parents(".fieldset_container").find(".ribbon");
    banner.removeClass().addClass("ribbon").addClass("staged");
    banner.find("span").text("Staged");
    container.find(".staging_button").remove();
};

var update_staged_transactions_view = function(){
    var tbody_el = $(".transfer_cart_container table tbody");
    var current_session_data = JSON.parse(sessionStorage.getItem("staged_releases"));
    tbody_el.empty();
    $.each(current_session_data, function(index, el){
        var row = $("<tr>", {"id": "upload_row_" + el.upload_id, "class": "upload_row"});
        row.append($("<td>", {"text": el.upload_id, "class": "upload_id"}));
        row.append($("<td>", {"text": el.proposal_id + " ", "class": "proposal_name", "title": el.proposal_name})
            .append($("<span>", {
                "class": "fa fa-lg fa-info-circle info_icon",
                "title": el.proposal_name,
                "aria-hidden": "true"
            }))
        );
        row.append($("<td>", {"text": el.instrument_name, "title": el.instrument_id, "class": "instrument_name"}));
        row.append($("<td>", {"text": moment(el.date_uploaded).format("LLL"), "class": "date_uploaded"}));
        row.append($("<td>", {"class": "transfer_item_controls" })
            .append($("<span>", {
                "class": "fa fa-2x fa-minus-circle transfer_item_delete_button",
                "aria-hidden": "true",
                "title": "Unstage this transaction"
            }))
        );
        tbody_el.append(row);
        tbody_el.find(".transfer_item_delete_button").off().on("click", function(){
            unstage_transaction(this);
        });
    });
    if(!$.isEmptyObject(current_session_data)){
        $("#doi_transfer_cart").show();
    }else{
        $("#doi_transfer_cart").hide();
    }
    setup_staging_buttons();
};

$(function(){
    update_staged_transactions_view();
    $(".transfer_cart_action_buttons_container input.cancel").off().on("click", function(){
        clear_selections();
    });
    $(".transfer_cart_action_buttons_container input.submit").off().on("click", function(event){
        submit_selections(event);
    });
});
