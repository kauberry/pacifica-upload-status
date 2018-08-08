var doi_ui_base = "https://data-doi.datahub.pnl.gov/";
var doi_url_base = "https://demoext2.datahub.pnl.gov/";
var doi_api_url_base = doi_url_base + "api/1.0/";

/* doi staging setup code */
var setup_doi_staging_button = function(el) {
    // var transaction_id = el.find(".transaction_identifier").val();
    var doi_staging_button = el.find(".doi_staging_button");

    if(!doi_staging_button.length){
        var doi_staging_button_container = $("<div/>", {
            "class": "staging_buttons buttons"
        });
        doi_staging_button = $("<input>", {
            "value": "Submit DOI",
            "class": "doi_staging_button",
            "style": "z-index: 4;"
        }).attr({
            "type": "button"
        });
        doi_staging_button_container.append(doi_staging_button);
        doi_staging_button.on("click", function(event){
            create_doi_data_resource($(event.target));
        });
        el.find("legend").after(doi_staging_button_container);
    }
    if(!doi_staging_button.is(":visible")){
        doi_staging_button.fadeIn("slow");
    }
};

var format_doi_ref = function(doi_reference){
    return "https://dx.doi.org/" + doi_reference;
};

var create_doi_data_resource = function(el) {
    doi_resource_info_dialog
        .data("entry_button", $(el))
        .data("upload_item", $(el).parents("fieldset"))
        .dialog("open");
};

var publish_released_data = function(el, form_data) {
    var container = el.parents(".transaction_container");
    // var upload_id = parseInt(container.find(".transaction_identifier").val(), 10);

    var new_info = {
        "title": form_data.doi_dataset_title,
        "description": form_data.doi_dataset_description,
        "language": "EN",
        "country": "US",
        "originating_research_org": ["PNNL"],
        "product_nos": [
            "Proposal ID: " + container.find(".proposal_identifier").val(),
            "Instrument ID: " + container.find(".instrument_identifier").val(),
            "Upload ID: " + container.find(".transaction_identifier").val()
        ],
        "contact_org": "EMSL",
        "contact_name": container.find(".contact_first_name").val() + " " + container.find(".contact_last_name").val(),
        "contact_email": container.find(".contact_email").val(),
        "contract_nos": [container.find(".contract_numbers").val()],
        "set_reserved": false,
        "publication_date": moment().format("YYYY-MM-DD"),
        "site_url": container.find(".site_url_identifier").val()
    };

    var file_size = myemsl_size_format(container.find(".total_file_size_bytes").val());
    var file_count = container.find(".total_file_count").val();
    var count_pluralizer = file_count == 1 ? "" : "s";
    new_info["dataset_size"] = file_count + " file" + count_pluralizer + " (" + file_size + ")";

    var prefill_url = doi_api_url_base + "prefill-registration";
    var pg_hider = $("#page_hider_working");
    var lb = $("#doi_loading_status_text");
    pg_hider.fadeIn();
    lb.text("Preparing DOI Submission");
    setTimeout(function() {
        lb.text("Contacting DOI Minting Service...");
        $.ajax({
            type: "POST",
            url: prefill_url,
            data: JSON.stringify(new_info),
            xhrFields: {
                withCredentials: true
            },
            headers: {
                "Content-Type": "application/json"
            }
        })
            .done (
                function(data){
                    var regID = data.id;
                    setTimeout(function () {
                        lb.text("Retrieving Record ID...");
                        var reg_info = {
                            "transaction_id": container.find(".transaction_identifier").val(),
                            "registration_id": regID,
                            "title": new_info.title,
                            "description": new_info.description,
                            "access_url": new_info.site_url
                        };
                        var transient_update_url = base_url + "update_local_records/" + regID;
                        $.ajax({
                            type: "POST",
                            url: transient_update_url,
                            data: JSON.stringify(reg_info),
                            headers: {
                                "Content-Type": "application/json"
                            }
                        })
                            .done (
                                function(data){
                                    setup_staging_buttons();
                                    window.open(doi_ui_base + "registrations/" + regID);
                                }
                            )
                            .always (
                                function() {
                                    pg_hider.fadeOut("slow");
                                }
                            );
                    }, 1000);
                }
            );
    }, 1000);


};

var build_metadata_for_display = function(el) {
    el = $(el);
    var display_elements = {};
    var item_list = el.find("[title]");
    item_list.each(function(index, item){
        item = $(item);
        display_elements[item.prop("class")] = {
            "display_name": item.prop("class").replace(/_/g, " "),
            "value": item.prop("title") + " (ID #" + item.val() + ")"
        };
    });

    item_list = el.find("input[class*='time']");
    item_list.each(function(index, item){
        item = $(item);
        display_elements[item.prop("class")] = {
            "display_name": item.prop("class").replace(/_/g, " "),
            "value": moment(item.val()).format("MMMM Do YYYY, h:mm:ss a")
        };
    });

    var file_size = myemsl_size_format(el.find(".total_file_size_bytes").val());
    var file_count = el.find(".total_file_count").val();
    var count_pluralizer = file_count == 1 ? "" : "s";
    display_elements["file_size"] = {
        "display_name": "Total File Size",
        "value": file_count + " file" + count_pluralizer + " (" + file_size + ")"
    };
    var rd = el.find(".release_date").val();
    display_elements["release_date"] = {
        "display_name": "Release Date",
        "value": moment(rd).format("MMMM Do YYYY")
    };

    var output_elements = [];
    $.each(display_elements, function(index, item) {
        var new_item = $("<li/>", {
            "class": "doi_" + index,
            "id": "doi_" + index
        })
            .append($("<span/>", {
                "style": "text-transform: capitalize;font-weight: bold;",
                "text": item.display_name.replace(" identifier", "") + ": "
            }))
            .append(item.value);
        output_elements.push(new_item);
    });
    return output_elements;
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
    doi_resource_info_dialog = $("#doi-resource-info-form").dialog({
        autoOpen: false,
        width: "640px",
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
            var cf = $(this).data("upload_item");
            var display_metadata = build_metadata_for_display(cf);
            var display_element = $(this).find(".readonly-display-grouping ul");
            $.each(display_metadata, function(index, item){
                display_element.append(item);
            });

        }
    });
});

var set_release_state_banners = function(release_states, selector){
    $(selector).each(function(index, el){
        el = $(el);
        var txn_id = el.find(".transaction_identifier").val();
        var ribbon_el = el.find(".ribbon");
        var release_info = release_states[txn_id];
        var transaction_id = release_info.transaction;

        if(release_info.release_state == "released"){
            //add doi staging button
            el.find(".upload_url").attr({"href": external_release_base_url + "released_data/" + txn_id});
            el.find(".release_date").val(release_info.release_date);
            var pub_status_block = el.next(".publication_status_block");
            if(release_info.transient_info.length > 0){
                var lb = pub_status_block.find(".publication_left_block");
                var rb = pub_status_block.find(".publication_right_block");
                lb.empty();
                rb.empty();
                lb.append($("<div>", {"class": "reference_header", "text": "Pending DOI Requests"}));
                rb.append($("<div>", {"class": "reference_header", "text": "Published DOI Entries"}));
                var pending_list = $("<ul/>").appendTo(lb);
                var completed_list = $("<ul/>").appendTo(rb);
                rb.hide();
                lb.hide();

                $.each(release_info.transient_info, function(index, item){
                    if (item.doi_reference === null) {
                        link_text = "pending";
                        link = doi_ui_base + "registrations/" + item.registration_id;
                        list_selection = pending_list;
                    } else {
                        link_text = item.doi_reference;
                        link = format_doi_ref(item.doi_reference);
                        list_selection = completed_list;
                    }
                    list_item = $("<li/>", {"title": item.description});
                    list_item.append($("<span/>", {"text": item.title + " / "}));
                    list_item.append($("<a/>", {"href": link, "text": link_text}));
                    list_item.appendTo(list_selection);
                });
                pub_status_block.show();
                if (lb.find("ul > li").length > 0) {
                    lb.show();
                }else{
                    lb.hide();
                }
                if (rb.find("ul > li").length > 0) {
                    rb.show();
                    if (lb.find("ul > li").length == 0){
                        rb.css("float", "left");
                    }
                }else{
                    rb.hide();
                }
            }

            if (typeof setup_doi_staging_button === "function") {
                setup_doi_staging_button(el, transaction_id);
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
        }
        el.find(".release_state").next("td.metadata_item").text(release_info.release_state);
        el.find(".release_state_display").next("td.metadata_item").text(release_info.display_state);
        ribbon_el.removeClass().addClass("ribbon").addClass(release_info.release_state);
        ribbon_el.find("span").text(release_info.display_state);

    });
};
