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
    
  //window.setInterval(update_breadcrumbs,20000);
  
  
  
});

