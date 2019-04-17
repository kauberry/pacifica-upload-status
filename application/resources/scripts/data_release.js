/*
    data staging logic
 */
var data_identifier = 0;

var build_staging_button = function(transaction_id, el){
    var content = el.find(".staging_buttons");
    if(!content.length){
        content = $("<div/>", {
            "class": "staging_buttons buttons"
        });
        el.find("legend").after(content);
    }

    var staging_button = $("<input>", {
        "value": "Stage for Release",
        "class": "staging_button"
    }).attr({
        "type": "button",
        "id" : "staging_" + transaction_id,
        "name": "staging_" + transaction_id
    });
    if(project_list && !project_list.includes(el.find(".project_identifier").val())){
        staging_button.attr({
            "title": "You do not have permissions to release this transaction"
        });
        staging_button
            .prop("disabled", true)
            .css("background-color", "rgb(200,200,200)")
            .css("border-color", "rgb(150,150,150)");

    }
    content.empty().append(staging_button);
    return content;
};

var submit_release_selections = function(event){
    var el = $(event.target);
    var table_rows = el.parents(".transfer_cart").find("table > tbody > tr");
    var pg_hider = $("#page_hider_working");
    var lb = $("#doi_loading_status_text");
    pg_hider.fadeIn();
    lb.text("Preparing Release Submission...");
    var errored_transactions = [];
    setTimeout(function(){
        lb.text("Setting Release State...");
        table_rows.each(function(index, item){
            var transaction_id = parseInt($(item).find(".upload_id").text(), 10);
            var release_url = base_url + "ajax_api/set_release_state/" + transaction_id + "/released";
            var releaser = $.get(release_url, function(data){
                var ribbon = $("#fieldset_container_" + transaction_id).find(".ribbon");
                var oldBannerClass = _.difference(ribbon.attr("class").split(" "), ["ribbon"])[0];
                if(oldBannerClass != data.release_state){
                    ribbon.removeClass(oldBannerClass).addClass(data.release_state);
                    ribbon.find("span").remove();
                    ribbon.append($("<span>", {"text": data.display_state}));
                }
                setTimeout(function(){
                    lb.text("Receiving Updated State Information...");
                }, 1000);
            });
            releaser.fail(function(){
                errored_transactions.push(transaction_id);
            });
            releaser.always(function(){
                setTimeout(function(){
                    var error_list_len = errored_transactions.length;
                    if(error_list_len){
                        var error_text = "You do not have proper permissions to release transaction";
                        if(error_list_len > 1){
                            error_text += "s";
                        }
                        error_text += ": " + errored_transactions.join(", ");
                        $(".error_dialog_inner").text(error_text);
                        $("#error_dialog")
                            .find(".error_dialog_dismisser")
                            .on("click", function(){
                                $("#error_dialog").fadeOut("fast");
                            });
                        $("#error_dialog").fadeIn();
                    }else{
                        $("#error_dialog").hide();
                    }
                    clear_release_selections();
                    setup_staging_buttons();
                    pg_hider.fadeOut("slow");
                }, 1000);
            });
        });
    }, 1000);
};

var clear_release_selections = function(){
    sessionStorage.removeItem("staged_releases");
    update_staged_transactions_view();
};

var unstage_transaction = function(el){
    el = $(el);
    var release_state = release_state_presets.not_released;
    var container = $("#fieldset_" + txn_id).parents(".fieldset_container");
    var txn_id = parseInt($(el).parents("tr").find(".upload_id").text(),10);
    if(container){
        var banner = container.find(".ribbon");
        var oldBannerClass = _.difference(banner.attr("class").split(" "), ["ribbon"])[0];
        if(oldBannerClass != release_state.span_class){
            banner.removeClass(oldBannerClass).addClass(release_state.span_class);
            banner.find("span").remove();
            banner.append($("<span>", {
                "text": release_state.display_text,
                "title": release_state.link_text
            }));
        }
    }
    remove_transaction_from_staging(txn_id);
};

var stage_transaction = function(el){
    var container = el.parents(".transaction_container");
    var release_state = release_state_presets.staged;
    var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
    var txn_id = container.find(".transaction_identifier").val();
    var new_info = {
        "upload_id": txn_id,
        "project_id": container.find(".project_identifier").val(),
        "project_name": container.find(".project_identifier").prop("title"),
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
    var oldBannerClass = _.difference(banner.attr("class").split(" "), ["ribbon"])[0];
    banner.find("span").remove();
    banner.append($("<span>", {
        "text": release_state.display_text,
        "title": release_state.link_text
    }));
    banner.removeClass(oldBannerClass).addClass(release_state.span_class);
    container.find(".staging_button").remove();
};

var remove_transaction_from_staging = function(transaction_id){
    var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
    if(current_session_contents){
        delete current_session_contents[transaction_id];
        sessionStorage.setItem("staged_releases", JSON.stringify(current_session_contents));
    }
    if(!_.size(current_session_contents)){
        sessionStorage.removeItem("staged_releases");
    }
    update_staged_transactions_view();
};

var update_staged_transactions_view = function(){
    var tbody_el = $(".transfer_cart_container table tbody");
    var current_session_data = JSON.parse(sessionStorage.getItem("staged_releases"));
    tbody_el.empty();
    $.each(current_session_data, function(index, el){
        var row = $("<tr>", {"id": "upload_row_" + el.upload_id, "class": "upload_row"});
        row.append($("<td>", {"text": el.upload_id, "class": "upload_id"}));
        row.append($("<td>", {"text": el.project_id + " ", "class": "project_name", "title": el.project_name})
            .append($("<span>", {
                "class": "fa fa-lg fa-info-circle info_icon",
                "title": el.project_name,
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
