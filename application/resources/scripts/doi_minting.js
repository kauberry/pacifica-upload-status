/* doi staging setup code */
var setup_doi_linking_button = function(el) {
    var transaction_id = el.find(".transaction_identifier").val();
    var data_release_link = el.find(".upload_url");
    var doi_linking_button = el.find(".doi_linking_button");
    if(!doi_linking_button.length){
        var button_options = {
            "class": "doi_linking_button alt_linking_button fa fa-clipboard",
            "style": "z-index: 4; margin-right: 6px;padding: 4px 4px 1px 7px;",
            "id": "doi_linking_button_" + transaction_id,
            "alt": "Copy data release link to clipboard",
            "title": "Copy data release link to clipboard",
            "name": "doi_linking_button_" + transaction_id,
            "data-clipboard-text": data_release_link.attr("href"),
            "data-clipboard-action": "copy"
        };
        doi_linking_button = $("<button>", button_options);
    }
    return doi_linking_button;
};

var setup_doi_staging_button = function(el) {
    var transaction_id = el.find(".transaction_identifier").val();
    var doi_staging_button = el.find(".doi_staging_button");
    // var data_release_link = el.find(".upload_url");
    if(!doi_staging_button.length){
        var doi_staging_button_container = el.find(".staging_buttons");
        if(!doi_staging_button_container.length){
            doi_staging_button_container = $("<div/>", {
                "class": "staging_buttons buttons"
            });
            el.find("legend").after(doi_staging_button_container);
        }
        doi_staging_button = $("<input>", {
            "value": "Submit DOI",
            "class": "doi_staging_button",
            "style": "z-index: 4;",
            "id": "doi_staging_button_" + transaction_id,
            "name": "doi_staging_button_" + transaction_id,
        }).attr({
            "type": "button"
        });

        doi_staging_button_container.empty().append(doi_staging_button);
        doi_staging_button_container = add_link_copy_info(
            el,
            doi_staging_button_container,
            setup_doi_linking_button,
            el.find(".site_url_identifier")
        );
        doi_staging_button.on("click", function(event){
            create_doi_data_resource($(event.target));
        });
    }
    if(!doi_staging_button.is(":visible")){
        doi_staging_button.fadeIn("slow");
    }
};

var create_doi_data_resource = function(el) {
    doi_resource_info_dialog
        .data("entry_button", $(el))
        .data("upload_item", $(el).parents("fieldset"))
        .dialog("open");
};

var publish_released_data = function(el, form_data) {
    var container = el.parents(".transaction_container");

    var new_info = {
        "title": form_data.doi_dataset_title,
        "description": form_data.doi_dataset_description,
        "dataset_type": "SM",
        "language": "EN",
        "country": "US",
        "originating_research_org": originating_research_organization,
        "doi_infix": container.find(".doi_infix").val(),
        "product_nos": [
            "Project ID: " + container.find(".project_identifier").val(),
            "Instrument ID: " + container.find(".instrument_identifier").val(),
            "Upload ID: " + container.find(".transaction_identifier").val()
        ],
        "authors": [
            {
                "first_name": container.find(".author_first_name").val(),
                "last_name": container.find(".author_last_name").val(),
                "private_email": container.find(".author_email").val()
            }
        ],
        "contact_org": originating_research_organization[0],
        "contact_name": container.find(".contact_first_name").val() + " " + container.find(".contact_last_name").val(),
        "contact_email": container.find(".contact_email").val(),
        "contract_nos": [container.find(".contract_numbers").val()],
        "set_reserved": false,
        "publication_date": moment().format("YYYY-MM-DD"),
        "site_url": external_release_base_url + "released_data/" + container.find(".transaction_identifier").val()
    };

    var file_size = myemsl_size_format(container.find(".total_file_size_bytes").val());
    var file_count = container.find(".total_file_count").val();
    var count_pluralizer = file_count == 1 ? "" : "s";
    new_info["dataset_size"] = file_count + " file" + count_pluralizer + " (" + file_size + ")";

    var doi_api_url_base = doi_url_base + "api/1.0/";
    var prefill_url = doi_api_url_base + "registrations";
    var pg_hider = $("#page_hider_working");
    var lb = $("#doi_loading_status_text");
    pg_hider.fadeIn();
    lb.text("Preparing DOI Submission");
    new_info["prefill"] = true;
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
                                function(){
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
    var item_list = el.find("input[title]");
    item_list.each(function(index, item){
        item = $(item);
        display_elements[item.prop("class")] = {
            "display_name": item.prop("class").replace(/_/g, " "),
            "value": item.prop("title") + " (ID #" + item.val() + ")"
        };
    });
    item_list = el.find("input[class*='author']");
    item_list.each(function(index, item){
        item = $(item);
        display_elements[item.prop("class")]["value"] = item.prop("title");
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
                    publish_released_data(entry_button, f.serializeFormJSON());
                    doi_resource_info_dialog.dialog("close");
                }

            },
            Cancel: function() {
                doi_resource_info_dialog.dialog("close");
            }
        },
        open: function() {
            var cf = $(this).data("upload_item");
            var display_metadata = build_metadata_for_display(cf);
            var display_element = $(this).find(".readonly-display-grouping ul");
            display_element.empty();
            $.each(display_metadata, function(index, item){
                display_element.append(item);
            });

        }
    });
});
