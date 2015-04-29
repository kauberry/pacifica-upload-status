$(function(){
  $('#proposal_selector').select2({
    placeholder: "Select an EUS Proposal..."
  });
  $('#proposal_selector').change(update_content);
  
  $('#instrument_selector').change(update_content);
  $("#instrument_selector").select2({
    data: [{id:0,text:""}],
    placeholder: "Select an Instrument..."
  });
  if(initial_proposal_id.length > 0){
    $('#proposal_selector').val(initial_proposal_id);
    get_instrument_list(initial_proposal_id);
    if(initial_instrument_id.length > 0){
      $('#instrument_selector').val(initial_instrument_id);
    }
  }
  
  
  
  
  $('#timeframe_selector').change(update_content);
  
  $("#timeframe_selector").select2({
    placeholder: "Select a Time Frame..."
  });
  
      
  setup_tree_data();
  setup_metadata_disclosure();
    
  window.setInterval(update_breadcrumbs,5000);
  window.setInterval(get_latest_transactions,5000);
  
});

