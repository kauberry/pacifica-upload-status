$(function(){
  $("#instrument_selector").select2({
    placeholder: "Select an Instrument..."
  });
  
  $('#instrument_selector').change(update_content);
  $('#timeframe_selector').change(update_content);
  
  $("#timeframe_selector").select2({
    placeholder: "Select a Time Frame..."
  });
      
  $('.tree_holder').each(function(index, el){
    $(el).fancytree();
  });
  
  window.setInterval(update_breadcrumbs,20000);
  // $("#instrument_selector").select2("val","156858");
  // $("#timeframe_selector").select2("val","-1 month");
  
  
  
});

