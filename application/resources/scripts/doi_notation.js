var set_clipboard_function = function() {
    var clipboard = new ClipboardJS(".doi_linking_button");
    clipboard.off().on("success", function(e) {
        var notif = $(e.trigger).parent("div").find(".copied_notification");
        notif.fadeIn().delay(500).fadeOut();
    });

};


var get_doi_release_data = function() {
    var release_check_url = base_url + "ajax_api/get_release_states";
    var my_transactions = $(".fieldset_container .transaction_identifier").map(function() {
        return $(this).val();
    }).toArray();
    $.post(
        release_check_url, JSON.stringify(my_transactions)
    )
        .done(
            function(data) {
                if (data) {
                    add_doi_notations(data);
                    set_release_state_banners(data, ".fieldset_container");
                }
            }
        )
        .fail(
            function(jqxhr, error, message) {
                alert("A problem occurred getting release data for DOIs.\n[" + message + "]");
            }
        );

};

var format_doi_ref = function(doi_reference){
    return "https://dx.doi.org/" + doi_reference;
};

var setup_doi_reference_copy_button = function(el, doi_reference) {
    var transaction_id = el.find(".transaction_identifier").val();
    var doi_link = format_doi_ref(doi_reference);
    var doi_reference_copy_button = el.find(".doi_reference_button");
    if(!doi_reference_copy_button.length){
        doi_reference_copy_button = $("<button>", {
            "class": "doi_linking_button",
            "style": "z-index: 4;",
            "id": "doi_reference_button_" + transaction_id,
            "alt": "Copy DOI reference link to clipboard",
            "title": "Copy DOI reference link to clipboard",
            "name": "doi_reference_button_" + transaction_id,
            "data-clipboard-text": doi_link,
            "data-clipboard-action": "copy",
            "text": "Copy DOI Reference Link"
        });
    }
    return doi_reference_copy_button;
};

var setup_doi_copied_notification = function(el) {
    var copied_notification = el.find(".copied_notification");
    var transaction_id = el.find(".transaction_identifier").val();
    if(!copied_notification.length){
        copied_notification = $("<button>", {
            "class": "copied_notification",
            "id": "copied_notification_" + transaction_id,
            "name": "copied_notification_" + transaction_id,
            "text": "Link Copied!",
            "style": "margin-right: 6px; display:none; transition: none;"
        });
    }
    return copied_notification;
};


var add_doi_notations = function(metadata_object) {
    var doi_staging_button_container = $("<div/>", {
        "class": "staging_buttons buttons"
    });
    $.each(metadata_object, function(index, item) {
        if(item.release_doi_entries && item.release_doi_entries.length > 0) {
            var doi_object = item.release_doi_entries[0];
            var upload_item_container = $("#fieldset_container_" + index);
            var md_table = upload_item_container.find(".metadata_description_table > tbody");
            var doi_entry_new = md_table.find("tr:last-child").clone();
            doi_entry_new.find(".metadata_header")
                .empty()
                .addClass("doi_reference")
                .text("DOI Reference");
            doi_entry_new.find(".metadata_item")
                .empty()
                .append($("<a>", {
                    "href": format_doi_ref(doi_object.doi_reference),
                    "text": doi_object.doi_reference,
                    "alt": "Link to DOI"
                }));
            md_table.append(doi_entry_new);
            if(upload_item_container.find(".staging_buttons").length == 0){
                dsbc = doi_staging_button_container.clone();
                dsbc = add_link_copy_info(
                    upload_item_container,
                    dsbc,
                    setup_doi_reference_copy_button,
                    format_doi_ref(doi_object.doi_reference)
                );
                upload_item_container.find("legend").after(dsbc);
                set_clipboard_function();
            }else{
                dsbc = upload_item_container.find(".staging_buttons");
            }
        }
    });
};

var add_link_copy_info = function(el, button_container, button_setup_function, copy_link) {
    var doi_linking_button = button_setup_function(el, copy_link);
    button_container.append(doi_linking_button);

    var copied_notification = setup_doi_copied_notification(el);
    button_container.append(copied_notification);
    return button_container;
};

var set_release_state_banners = function(release_states, selector){
    $(selector).each(function(index, el){
        el = $(el);
        var txn_id = el.find(".transaction_identifier").val();
        var ribbon_el = el.find(".ribbon");
        var release_info = release_states[txn_id];
        var transaction_id = release_info.transaction;
        var doi_release_state = "";
        if(release_info.release_state == "released"){
            //add doi staging button
            doi_release_state = "released";
            doi_display_state = "Released";
            el.find(".upload_url").attr({"href": external_release_base_url + "released_data/" + txn_id});
            el.find(".release_date").val(release_info.release_date);
            // var pub_status_block = el.next(".publication_status_block");
            if(release_info.release_doi_entries && release_info.release_doi_entries.length > 0){
                item = release_info.release_doi_entries[0];
                doi_reference = item.doi_reference;
                if (item.doi_status == "saved") {
                    doi_release_state = "doi_pending";
                    doi_display_state = "DOI Pending";
                    link_text = "Submitted (Pending Release)";
                    link = doi_ui_base + "registrations/" + item.metadata.minting_api_id;
                } else if (item.doi_status == "pending") {
                    doi_release_state = "doi_pending";
                    doi_display_state = "DOI Pending";
                    link_text = "Awaiting Approval at Datacite";
                } else {
                    doi_release_state = "minted";
                    doi_display_state = "DOI Minted";
                    link = format_doi_ref(item.doi_reference);
                    link_text = item.doi_reference;
                }
            }

            if (typeof setup_doi_staging_button === "function") {
                if (doi_release_state == "released"){
                    setup_doi_staging_button(el, transaction_id);
                }
            }
        }else{
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
            doi_release_state = release_info.release_state;
            doi_display_state = release_info.display_state;
        }
        el.find(".release_state").next("td.metadata_item").text(doi_release_state);
        el.find(".release_state_display").next("td.metadata_item").text(doi_display_state);
        ribbon_el.removeClass().addClass("ribbon").addClass(doi_release_state);
        ribbon_el.find("span").text(doi_display_state);

    });
};
