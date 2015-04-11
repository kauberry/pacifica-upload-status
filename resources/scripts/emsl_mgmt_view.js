$(function(){
  $("#instrument_selector").select2({
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
  window.setInterval(get_latest_transactions,15000);
  
  
});

