/*
    data staging logic
 */
var data_identifier = 0;

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

var submit_release_selections = function(event){
    var el = $(event.target);
    var table_rows = el.parents(".transfer_cart").find("table > tbody > tr");
    var pg_hider = $("#page_hider_working");
    var lb = $("#doi_loading_status_text");
    pg_hider.fadeIn();
    lb.text("Preparing Release Submission...");
    setTimeout(function(){
        lb.text("Setting Release State...");
        table_rows.each(function(index, item){
            var transaction_id = parseInt($(item).find(".upload_id").text(), 10);
            var release_url = base_url + "ajax_api/set_release_state/" + transaction_id + "/released";
            $.get(release_url, function(data){
                var fieldset_container = $("#fieldset_container_" + transaction_id);
                var ribbon = fieldset_container.find(".ribbon");
                ribbon.removeClass().addClass("ribbon").addClass(data.release_state);
                ribbon.find("span").text(data.display_state);
            });
        });
        setTimeout(function(){
            lb.text("Receiving Updated State Information...");
            setTimeout(function(){
                clear_release_selections();
                setup_staging_buttons();
            }, 1000);
            pg_hider.fadeOut("slow");
        }, 1000);
    }, 1000);
};

var clear_release_selections = function(){
    sessionStorage.removeItem("staged_releases");
    update_staged_transactions_view();
};

// var set_staged_transaction_completed = function(upload_id){
//     var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
//     delete current_session_contents[upload_id];
//     sessionStorage.setItem("staged_releases", JSON.stringify(current_session_contents));
// };

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
    if(!_.size(current_session_contents)){
        clear_release_selections();
    }else{
        update_staged_transactions_view();
    }
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

var set_release_state_banners = function(release_states, selector){
    $(selector).each(function(index, el){
        el = $(el);
        var txn_id = el.find(".transaction_identifier").val();
        var ribbon_el = el.find(".ribbon");
        var release_info = release_states[txn_id];
        // var transaction_id = release_info.transaction;
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
            el.find(".upload_url").attr({"href": external_release_base_url + "released_data/" + txn_id});
            el.find(".release_date").val(release_info.release_date);
        }
        el.find(".release_state").next("td.metadata_item").text(release_info.release_state);
        el.find(".release_state_display").next("td.metadata_item").text(release_info.display_state);
        var release_date_line = $("<tr/>", {"class": "metadata_description_list"})
            .append($("<td/>", {
                "class": "metadata_header release_date",
                "text": "Release Date"
            }))
            .append($("<td/>", {
                "class": "metadata_item",
                "text": moment(release_info.release_date).format("YYYY-MM-DDTHH:mm:ss")
            }));
        el.find(".release_state_display").parents("tr").after(release_date_line);
        ribbon_el.removeClass().addClass("ribbon").addClass(release_info.release_state);
        ribbon_el.find("span").text(release_info.display_state);

    });
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
        $("#data_release_cart").show();
    }else{
        $("#data_release_cart").hide();
    }
    setup_staging_buttons();
};

var setup_staging_buttons = function(){
    var release_check_url = base_url + "ajax_api/get_release_states";
    if(data_identifier.length > 0){
        release_check_url += "/" + data_identifier;
    }
    var my_transactions = $(".fieldset_container .transaction_identifier").map(function(){
        return $(this).val();
    }).toArray();
    $.post(
        release_check_url, JSON.stringify(my_transactions)
    )
        .done(
            function(data){
                if(data){
                    set_release_state_banners(data, ".fieldset_container");
                }
            }
        )
        .fail(
            function(jqxhr, error, message){
                alert("A problem occurred creating your cart.\n[" + message + "]");
            }
        );

};

$(function(){
    update_staged_transactions_view();
    $(".transfer_cart_action_buttons_container input.cancel").off().on("click", function(){
        clear_release_selections();
    });
    $(".transfer_cart_action_buttons_container input.submit").off().on("click", function(event){
        submit_release_selections(event);
    });

});
