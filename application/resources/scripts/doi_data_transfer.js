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

/*
    data staging logic
 */
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

var submit_submission_selections = function(){
    var submit_url = base_url + "ajax_api/publish_resource_to_doi/" + data_identifier;
    var submit_data = [];
    var session_data = JSON.parse(sessionStorage.getItem("items_to_publish"));
    $.each(session_data, function(index, item){
        submit_data.push({
            "transaction_id": item.upload_id,
            "release_name": item.release_name,
            "release_description": item.release_description
        });
    });
    sessionStorage.removeItem("items_to_publish");
    var pg_hider = $("#page_hider_working");
    var lb = $("#doi_loading_status_text");
    pg_hider.fadeIn();
    lb.text("Preparing DOI Submission...");
    setTimeout(function(){
        lb.text("Contacting DRHub Servers...");
        $.post(
            submit_url, JSON.stringify(submit_data)
        )
            .done(
                function(){
                    lb.text("Receiving Updated State Information...");
                    setTimeout(function(){
                        setup_staging_buttons();
                        update_publishing_view();
                        pg_hider.fadeOut("slow");
                    }, 2000);
                }
            )
            .fail(
                function(jqxhr, error, message){
                    alert("A problem occurred creating your cart.\n[" + message + "]");
                }
            );}, 2000);

};

var submit_release_selections = function(event){
    var el = $(event.target);
    var table_rows = el.parents(".transfer_cart").find("table > tbody > tr");
    table_rows.each(function(index, item){
        var transaction_id = parseInt($(item).find(".upload_id").text(), 10);
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

var clear_release_selections = function(){
    sessionStorage.removeItem("staged_releases");
    update_staged_transactions_view();
};

var set_release_state_banners = function(release_states, selector){
    $(selector).each(function(index, el){
        el = $(el);
        var txn_id = el.find(".transaction_identifier").val();
        var ribbon_el = el.find(".ribbon");
        var release_info = release_states[txn_id];
        var transaction_id = release_info.transaction;
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
            //add doi staging button
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
                // pub_status_block.show();
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
                // pub_status_block.show();
            }
            el.find(".release_date").val(release_info.release_date);
            if(release_info.transient_info.node_id && release_info.transient_info.data_set_node_id == data_identifier){
                release_info["release_state"] = "doi_pending";
                release_info["display_state"] = "DOI Pending";
                el.find(".doi_staging_button").remove();
            }else{
                setup_doi_staging_button(el, transaction_id);
            }
        }
        el.find(".release_state").next("td.metadata_item").text(release_info.release_state);
        el.find(".release_state_display").next("td.metadata_item").text(release_info.display_state);
        ribbon_el.removeClass().addClass("ribbon").addClass(release_info.release_state);
        ribbon_el.find("span").text(release_info.display_state);

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
    if(!_.size(current_session_contents)){
        clear_release_selections();
    }
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

/* doi staging setup code */
var setup_doi_staging_button = function(el) {
    var current_session_contents = JSON.parse(sessionStorage.getItem("items_to_publish"));
    var transaction_id = el.find(".transaction_identifier").val();
    var doi_staging_button = el.find(".doi_staging_button");
    var should_be_disabled = _.keys(current_session_contents).includes(transaction_id);

    if(!doi_staging_button.length){
        doi_staging_button = $("<input>", {
            "type": "button",
            "value": "Add to Dataset",
            "class": "transfer_cart_button transfer_cart_headers doi_staging_button",
            "style": "display: none;z-index: 4;"
        });
        doi_staging_button.on("click", function(event){
            create_doi_data_resource($(event.target));
        }).appendTo(el.find("fieldset"));
    }
    if(should_be_disabled){
        doi_staging_button.attr("disabled", "disabled").addClass("disabled");
    }else{
        doi_staging_button.removeAttr("disabled").removeClass("disabled");
    }
    if(!doi_staging_button.is(":visible")){
        doi_staging_button.fadeIn("slow");
    }
};

var format_doi_ref = function(doi_reference){
    return "https://dx.doi.org/" + doi_reference;
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

var clear_submission_selections = function(){
    sessionStorage.removeItem("items_to_publish");
    update_publishing_view();
};

var create_doi_data_resource = function(el) {
    doi_resource_info_dialog
        .data("entry_button", $(el))
        .dialog("open");
};

var publish_released_data = function(el, form_data) {
    var container = el.parents(".transaction_container");
    var current_session_contents = JSON.parse(sessionStorage.getItem("items_to_publish"));
    var upload_id = parseInt(container.find(".transaction_identifier").val(), 10);
    var new_info = {
        "upload_id": container.find(".transaction_identifier").val(),
        "release_name": form_data.doi_rsrc_name,
        "release_date": moment(container.find(".release_date").val()).toISOString(),
        "file_size": container.find(".total_file_size_bytes").val(),
        "file_count": container.find(".total_file_count").val(),
        "release_description": form_data.doi_rsrc_desc
    };
    if(current_session_contents == null){
        current_session_contents = {};
    }
    current_session_contents[upload_id] = new_info;
    sessionStorage.setItem("items_to_publish", JSON.stringify(current_session_contents));
    setup_doi_staging_button(container);
    update_publishing_view();
};

var unstage_publish_data = function(el) {
    el = $(el.target);
    var txn_id = parseInt(el.parents("tr").find(".upload_id").text(), 10);
    var current_session_contents = JSON.parse(sessionStorage.getItem("items_to_publish"));
    delete current_session_contents[txn_id];
    sessionStorage.setItem("items_to_publish", JSON.stringify(current_session_contents));
    var container = $("#fieldset_" + txn_id);
    setup_doi_staging_button(container);
    update_publishing_view();
};

var edit_published_data = function(event) {
    var el = $(event.target);
    var txn_id = parseInt(el.parents("tr").find(".upload_id").text(), 10);
    var current_session_data = JSON.parse(sessionStorage.getItem("items_to_publish"));
    var item_data = current_session_data[release_id];
    doi_resource_edit_dialog
        .data("resource_name", item_data["release_name"])
        .data("resource_desc", item_data["release_description"])
        .data("transaction_id", txn_id)
        .dialog("open");
};

var update_publishing_view = function(){
    var tbody_el = $(".doi_cart_container table tbody");
    var current_session_data = JSON.parse(sessionStorage.getItem("items_to_publish"));
    tbody_el.empty();
    $.each(current_session_data, function(index, el){
        var row = $("<tr>", {"id": "publish_row_" + el.upload_id, "class": "publish_row"});
        el.file_stats = el.file_count + " files (" + myemsl_size_format(el.file_size) + ")";
        var desc = el["release_description"];
        el.release_date = moment(el.release_date).fromNow() + " (" + moment(el.release_date).format("LL") + ")";
        var header_el = $(".doi_cart_container table thead th").filter(function(){
            return $(this).prop("id").length > 0;
        });
        $.each(header_el, function(index, item){
            var val_name = $(item).prop("id").replace("th_", "");
            row.append($("<td>", {"text": el[val_name], "class": val_name}));
        });
        row.attr("title", desc);
        var control_element = $("<td>", {"class": "transfer_item_controls", "style": "padding-left: 1em;" });
        $("<span>", {
            "class": "fa fa-2x fa-pencil transfer_item_edit_button",
            "aria-hidden": "true",
            "title": "View/Edit Upload Metadata"
        }).appendTo(control_element);
        $("<span>", {
            "class": "fa fa-2x fa-minus-circle transfer_item_delete_button",
            "aria-hidden": "true",
            "title": "Unstage this transaction"
        }).appendTo(control_element);
        row.append(control_element);
        tbody_el.append(row);
    });
    if(_.size(current_session_data)){
        tbody_el.find(".transfer_item_delete_button").off().on("click", unstage_publish_data);
        tbody_el.find(".transfer_item_edit_button").off().on("click", edit_published_data);
        $("#doi_submission_cart").show();
    }else{
        $("#doi_submission_cart").hide();
    }
};


var myemsl_size_format = function(bytes) {
    var suffixes = ["B", "KB", "MB", "GB", "TB", "EB"];
    if (bytes == 0) {
        suffix = "B";
    } else {
        var order = Math.floor(Math.log(bytes) / Math.log(10) / 3);
        bytes = (bytes / Math.pow(1024, order)).toFixed(1);
        suffix = suffixes[order];
    }
    return bytes + " " + suffix;
};

$(function(){
    update_staged_transactions_view();
    update_publishing_view();
    $(".transfer_cart_action_buttons_container input.cancel").off().on("click", function(){
        clear_release_selections();
    });
    $(".transfer_cart_action_buttons_container input.submit").off().on("click", function(event){
        submit_release_selections(event);
    });
    $(".submission_cart_action_buttons_container input.cancel").off().on("click", function(){
        clear_submission_selections();
    });
    $(".submission_cart_action_buttons_container input.submit").off().on("click", function(event){
        submit_submission_selections(event);
    });
    doi_resource_edit_dialog = $("#doi-resource-edit-form").dialog({
        autoOpen: false,
        width: "40%",
        dialogClass: "drop_shadow_dialog",
        modal: true,
        buttons: {
            "OK": function() {
                f = $(this).find("form");
                var empty_req_fields = f.find("input:invalid, textarea:invalid");
                if(empty_req_fields.length > 0){
                    $.each(empty_req_fields, function(index, item){
                        $(item).next(".pure-form-message-inline").fadeIn("fast");
                    });
                    return false;
                }else{
                    var current_session_data = JSON.parse(sessionStorage.getItem("items_to_publish"));
                    var transaction_id = $(this).data("transaction_id");
                    current_session_data[transaction_id]["release_name"] = $("#doi_rsrc_name").val();
                    current_session_data[transaction_id]["release_description"] = $("#doi_rsrc_desc").val();
                    sessionStorage.setItem("items_to_publish", JSON.stringify(current_session_data));
                    $(this).dialog("close");
                    update_publishing_view();
                }
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        },
        open: function() {
            $("#doi_rsrc_name").val($(this).data("resource_name"));
            $("#doi_rsrc_desc").val($(this).data("resource_desc"));
        }
    });
    doi_resource_info_dialog = $("#doi-resource-info-form").dialog({
        autoOpen: false,
        width: "40%",
        dialogClass: "drop_shadow_dialog",
        modal: true,
        buttons: {
            "Create": function() {
                f = $(this).find("form");
                var empty_req_fields = f.find("input:invalid, textarea:invalid");
                if(empty_req_fields.length > 0){
                    $.each(empty_req_fields, function(index, item){
                        $(item).next(".pure-form-message-inline").fadeIn("fast");
                    });
                    return false;
                }else{
                    //all req'd fields filled out
                    var entry_button = $(this).data("entry_button");
                    // var resource_name = $(this).data("resource_name");
                    // var resource_desc = $(this).data("resource_desc");
                    publish_released_data(entry_button, f.serializeFormJSON());
                    doi_resource_info_dialog.dialog("close");
                }

            },
            Cancel: function() {
                // doi_resource_info_dialog.reset();
                doi_resource_info_dialog.dialog("close");
            }
        },
        open: function() {
            var req_fields = $(this).find("input:required, textarea:required");
            req_fields.on("keyup", function(event){
                var el = $(event.target);
                var req_notifier = el.next(".pure-form-message-inline");
                if(el.is(":invalid") && req_notifier.is(":hidden")){
                    req_notifier.fadeIn("fast");
                }else if(el.is(":valid") && req_notifier.is(":visible")) {
                    req_notifier.fadeOut("fast");
                }
            });
        },
        close: function() {

        }
    });
});
