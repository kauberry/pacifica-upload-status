$(
    function() {
        $("#project_selector").select2(
            {
                // data: initials,
                ajax: {
                    dataType: "json",
                    delay: 250,
                    cache: true,
                    url: function(params) {
                        var myURL = base_url + "ajax_api/get_projects_by_name/";
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
                templateResult: formatProject,
                templateSelection: formatProjectSelection
            }
        );
        // $("#project_selector").select2("search","nmr");

        $("#instrument_selector").select2(
            {
                placeholder: "Select an Instrument..."
            }
        );
        if (initial_project_id.length > 0) {
            var current_prop_val = $("#project_selector").val();
            $("#project_selector").val(initial_project_id);
            if($("#project_selector").val() != null){
                get_instrument_list(initial_project_id);
            }else{
                $("#project_selector").val(current_prop_val);
            }
        }

        $("#timeframe_selector").select2(
            {
                placeholder: "Select a Time Frame..."
            }
        );
        $(".criterion_selector").change(update_content);
        setup_tree_data();
        setup_metadata_disclosure();

        //window.setInterval(check_cart_status, 30000);
        //window.setInterval(update_breadcrumbs,30000);
        // window.setInterval(get_latest_transactions, 60000);

        function formatProject(item) {
            var markup = false;
            var start_date = moment(item.start_date);
            var end_date = moment(item.end_date);
            var start_date_string = start_date.isValid() ? start_date.format("MM/DD/YYYY") : "&mdash;&mdash;";
            var end_date_string = end_date.isValid() ? end_date.format("MM/DD/YYYY") : "&mdash;&mdash;";

            if (item.loading) return item.text;
            if (item.id.length > 0) {
                markup = "<div id=\"prop_info_" + item.id + "\" class=\"prop_info\">";
                markup += "   <div class=\"";
                markup += item.currently_active == "yes" ? "active" : "inactive";
                markup += "_project\"><strong>Project " + item.id + "</strong>";
                markup += "   </div>";
                markup += "   <div style=\"float:right;\">";
                markup += "     <span class=\"active_dates\">";
                if (item.currently_active == "yes" && item.state == "active") {
                    markup += "Active Through " + end_date_string;
                }else if(item.currently_active == "no") {
                    if(item.state == "preactive") {
                        markup += "Inactive Until " + start_date_string;
                    }else{
                        if(!start_date.isValid() || !end_date.isValid()) {
                            markup += "Invalid Start/End Dates";
                        }else{
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
        }

        function formatProjectSelection(item) {
            var markup = "Please Select a Project...";
            if (item.id.length > 0) {
                markup = "<span title=\"" + item.title + "\">Project " + item.id + ": " + item.text + "</span>";
            }
            return markup;
        }

    }
);
