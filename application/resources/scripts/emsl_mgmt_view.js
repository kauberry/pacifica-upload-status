$(function(){
  var initials = [];
  var selected = 49418;
  // initials.push({id:49418, title: 'Development of NMR Sparse Sampling Methods for Quantitative Metabolic Flux Analysis'})
  // initials.push({id:49223, title: 'Extinction, ecosystem structure and carbon cycling in coastal sediments: Quantifying the impacts of megafaunal species loss upon coastal sediment organic carbon pools using nuclear magnetic resonance (NMR) and high resolution mass spectroscopy'})


  $('#proposal_selector').select2({
    // data: initials,
    ajax: {
        dataType: 'json',
        delay: 250,
        url: function(params){
            var myURL = base_url + "ajax/get_proposals_by_name/";
            if(params.term != undefined){
                myURL += params.term;
            }
            return myURL;

        },
        data: function(params) {
            return "";
        },
        processResults: function(data,params){
            params.page = params.page || 1;
            return {
                results: data.items,
                pagination: {
                    more: (params.page * 30) < data.total_count
                }
            }
        }
    },
    escapeMarkup: function (markup) { return markup; },
    templateResult: formatProposal,
    templateSelection: formatProposalSelection
  });
  // $('#proposal_selector').select2("search","nmr");

  $("#instrument_selector").select2({
    data: [{id:0,text:""}],
    placeholder: "Select an Instrument..."
  });
  if(initial_proposal_id.length > 0){
    $('#proposal_selector').val(initial_proposal_id);
    get_instrument_list(initial_proposal_id);
  }

  $("#timeframe_selector").select2({
    placeholder: "Select a Time Frame..."
  });

  $('.criterion_selector').change(update_content);

  setup_tree_data();
  setup_metadata_disclosure();

  window.setInterval(check_cart_status, 30000);
  //window.setInterval(update_breadcrumbs,30000);
  window.setInterval(get_latest_transactions,60000);

  function formatProposal(item){
      var markup = false;
      if(item.loading) return item.text;
      if(item.id.length > 0){
          markup = "<div><strong>Proposal " + item.id + "</strong></div><div style='margin-left:10px;font-size:0.85em;'>" + item.title + "</div>";
      }
      return markup;
  }

  function formatProposalSelection (item) {
      var markup = 'Please Select a Proposal';
      if(item.id.length > 0){
          markup = "Proposal " + item.id + ": " + item.title;
      }
      return markup;
  }

});
