$(
    function() {
        var initials = [];
        var selected = 49418;
        // initials.push({id:49418, title: 'Development of NMR Sparse Sampling Methods for Quantitative Metabolic Flux Analysis'})
        // initials.push({id:49223, title: 'Extinction, ecosystem structure and carbon cycling in coastal sediments: Quantifying the impacts of megafaunal species loss upon coastal sediment organic carbon pools using nuclear magnetic resonance (NMR) and high resolution mass spectroscopy'})


        $('#proposal_selector').select2(
            {
                // data: initials,
                ajax: {
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    url: function(params) {
                        var myURL = base_url + "ajax/get_proposals_by_name/";
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
                        }
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateResult: formatProposal,
                templateSelection: formatProposalSelection
            }
        );
        // $('#proposal_selector').select2("search","nmr");

        $("#instrument_selector").select2(
            {
                placeholder: "Select an Instrument..."
            }
        );
        if (initial_proposal_id.length > 0) {
            var current_prop_val = $('#proposal_selector').val();
            $('#proposal_selector').val(initial_proposal_id);
            if($('#proposal_selector').val() != null){
                get_instrument_list(initial_proposal_id);
            }else{
                $('#proposal_selector').val(current_prop_val);
            }
        }

        $("#timeframe_selector").select2(
            {
                placeholder: "Select a Time Frame..."
            }
        );

        $('.criterion_selector').change(update_content);

        setup_tree_data();
        setup_metadata_disclosure();

        window.setInterval(check_cart_status, 30000);
        //window.setInterval(update_breadcrumbs,30000);
        window.setInterval(get_latest_transactions, 60000);

        function formatProposal(item) {
            var markup = false;
            var start_date = moment(item.start_date);
            var end_date = moment(item.end_date);
            var start_date_string = start_date.isValid() ? start_date.format('MM/DD/YYYY') : '&mdash;&mdash;';
            var end_date_string = end_date.isValid() ? end_date.format('MM/DD/YYYY') : '&mdash;&mdash;';

            if (item.loading) return item.text;
            if (item.id.length > 0) {
                markup = "<div id='prop_info_" + item.id + "' class='prop_info'>";
                markup += "   <div class='";
                markup += item.currently_active == 'yes' ? 'active' : 'inactive';
                markup += "_proposal'><strong>Proposal " + item.id + "</strong>";
                markup += "   </div>";
                markup += "   <div style='float:right;'>"
                markup += "     <span class='active_dates'>"
                if (item.currently_active == 'yes' && item.state == 'active') {
                    markup += "Active Through " + end_date_string;
                }else if(item.currently_active == 'no') {
                    if(item.state == 'preactive') {
                        markup += "Inactive Until " + start_date_string;
                    }else{
                        if(!start_date.isValid() || !end_date.isValid()) {
                            markup += "Invalid Start/End Dates";
                        }else{
                            markup += "Inactive Since " + end_date_string;
                        }
                    }
                }
                markup += "     </span>"
                markup += "   </div>"
                markup += "</div>";
                markup += "<div class='prop_description'>" + item.title + "</div>";
            }
            return markup;
        }

        function formatProposalSelection(item) {
            var markup = 'Please Select an EUS Proposal...';
            if (item.id.length > 0) {
                markup = "<span title='" + item.title + "'>" + item.text + "</span>";
            }
            return markup;
        }

    }
);
