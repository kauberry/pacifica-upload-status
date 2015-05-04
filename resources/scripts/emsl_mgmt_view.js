$(function(){
  $('#proposal_selector').select2({
    placeholder: "Select an EUS Proposal..."
  });
  
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
// 
  // $('#proposal_selector').change(update_content);
  // $('#instrument_selector').change(update_content);
  // $('#timeframe_selector').change(update_content);
      
  setup_tree_data();
  setup_metadata_disclosure();
    
  window.setInterval(update_breadcrumbs,5000);
  window.setInterval(get_latest_transactions,5000);
  
});

