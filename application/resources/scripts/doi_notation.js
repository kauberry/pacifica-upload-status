$(function(){
    window.setInterval(get_doi_release_data, 30000);
});

var set_clipboard_function = function() {
    var clipboard = new ClipboardJS(".doi_linking_button");
    clipboard.off().on("success", function(e) {
        var notif = $(e.trigger).parent("div").find(".copied_notification");
        notif.fadeIn().delay(500).fadeOut();
    });

};

var release_state_presets = {
    "saved": {
        "span_class": "doi_reserved",
        "display_text": "DOI Reserved",
        "link_text": "DOI Reserved, but not Published"
    },
    "pending": {
        "span_class": "doi_pending",
        "display_text": "DOI Pending",
        "link_text": "DOI Awaiting approval at Datacite"
    },
    "completed": {
        "span_class": "doi_minted",
        "display_text": "DOI Minted",
        "link_text": "DOI Awaiting approval at Datacite"
    },
    "not_released": {
        "span_class": "not_released",
        "display_text": "Not Released",
        "link_text": "Item awaiting release approval"
    },
    "released": {
        "span_class": "released",
        "display_text": "Released",
        "link_text": "Item Released to Public"
    },
    "staged": {
        "span_class": "staged",
        "display_text": "Staged",
        "link_text": "Staged for Release to Public"
    }
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
    var doi_reference_copy_button = el.find(".doi_reference_button");
    if(!doi_reference_copy_button.length){
        doi_reference_copy_button = $("<button>", {
            "class": "doi_linking_button",
            "style": "z-index: 4;",
            "id": "doi_reference_button_" + transaction_id,
            "alt": "Copy DOI reference link to clipboard",
            "title": "Copy DOI reference link to clipboard",
            "name": "doi_reference_button_" + transaction_id,
            "data-clipboard-text": doi_reference,
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
    $.each(metadata_object, function(index, item) {
        if(item.release_doi_entries && item.release_doi_entries.length > 0) {
            var doi_object = item.release_doi_entries[0];
            var upload_item_container = $("#fieldset_container_" + index);
            var doi_staging_button_container = upload_item_container.find(".staging_buttons");
            if(!doi_staging_button_container.length){
                doi_staging_button_container = $("<div/>", {
                    "class": "staging_buttons buttons"
                });
                upload_item_container.find("legend").after(doi_staging_button_container);
            }
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
            dsbc = doi_staging_button_container.clone().empty();
            dsbc = add_link_copy_info(
                upload_item_container,
                dsbc,
                setup_doi_reference_copy_button,
                format_doi_ref(doi_object.doi_reference)
            );
            doi_staging_button_container.replaceWith(dsbc);
        }
    });
    set_clipboard_function();
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
        if(release_info.release_state == "released"){
            //add doi staging button
            release_state = release_state_presets[release_info.release_state];
            el.find(".upload_url").attr({"href": external_release_base_url + "released_data/" + txn_id});
            el.find(".release_date").val(release_info.release_date);
            if(release_info.release_doi_entries && release_info.release_doi_entries.length > 0){
                item = release_info.release_doi_entries[0];
                release_state = release_state_presets[item.doi_status];
            }else{
                if (typeof setup_doi_staging_button === "function") {
                    if (release_info.release_state == "released"){
                        setup_doi_staging_button(el, transaction_id);
                    }
                }
            }

        }else{
            var current_session_contents = JSON.parse(sessionStorage.getItem("staged_releases"));
            if(!$.isEmptyObject(current_session_contents) && txn_id in current_session_contents){
                release_state = release_state_presets.staged;
            }else{
                release_state = release_state_presets.not_released;
                if (typeof build_staging_button === "function") {
                    var content = build_staging_button(txn_id, el);
                    el.find("legend").after(content);
                    el.find(".staging_button").off().on("click", function(event){
                        stage_transaction($(event.target));
                    });
                }

            }
        }
        el.find(".release_state").next("td.metadata_item").text(release_state.span_class);
        el.find(".release_state_display").next("td.metadata_item").text(release_state.display_text);
        var oldBannerClass = _.difference(ribbon_el.attr("class").split(" "), ["ribbon"])[0] || null;
        if(oldBannerClass != release_state.span_class){
            ribbon_el.removeClass(oldBannerClass).addClass(release_state.span_class);
            ribbon_el.find("span").remove();
            ribbon_el.append($("<span>", {
                "text": release_state.display_text,
                "title": release_state.link_text
            }));
        }
    });
};
