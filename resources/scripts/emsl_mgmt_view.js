$(function(){
  $("#instrument_selector").select2({
    placeholder: "Select an Instrument..."
  });
  $("#timeframe_selector").select2({
    placeholder: "Select a Time Frame..."
  });
  // $("#instrument_selector").select2("val","156858");
  // $("#timeframe_selector").select2("val","-1 month");
});