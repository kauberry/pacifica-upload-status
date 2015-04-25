$(function(){
  $('#proposal_selector').select2({
    placeholder: "Select an EUS Proposal..."
  });
  $('#proposal_selector').change(update_content);
  
  $("#instrument_selector").select2({
    data: [{id:0,text:""}],
    placeholder: "Select an Instrument..."
  });
  
  $('#instrument_selector').change(update_content);
  
  
  $('#timeframe_selector').change(update_content);
  
  $("#timeframe_selector").select2({
    placeholder: "Select a Time Frame..."
  });
  
      
  setup_tree_data();
  setup_metadata_disclosure();
    
  window.setInterval(update_breadcrumbs,5000);
  window.setInterval(get_latest_transactions,5000);
  
  
});

