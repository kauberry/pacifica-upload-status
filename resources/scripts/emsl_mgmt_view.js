$(function(){
  $("#instrument_selector").select2({
    placeholder: "Select an Instrument..."
  });
  $("#timeframe_selector").select2({
    placeholder: "Select a Time Frame..."
  });
  
  $('#instrument_selector').select2('enable',false);
  $('#timeframe_selector').select2('enable',false);
    
  $('.tree_holder').each(function(index, el){
    $(el).fancytree();
  });
  
  // $("#instrument_selector").select2("val","156858");
  // $("#timeframe_selector").select2("val","-1 month");
});