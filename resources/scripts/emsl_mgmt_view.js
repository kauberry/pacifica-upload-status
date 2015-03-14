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
  
  window.setInterval(update_breadcrumbs,5000);
  // $("#instrument_selector").select2("val","156858");
  // $("#timeframe_selector").select2("val","-1 month");
  
  
  
});

var update_breadcrumbs = function(){
  $('.bar_holder').each(function(index,el){
    var pattern = /\w+_\w+_(\d+)/i;
    var m = $(el).prop('id').match(pattern);
    var trans_id = m[1];
    var url = base_url + 'index.php/status/get_status/t/' + trans_id;
    $.get(url, function(data){
      $(el).html(data);
    });
  });
};

var update_content = function(){
  var instrument_id = $('#instrument_selector').select2('val');
  var time_frame = $('#timeframe_selector').select2('val');
  var url = base_url + 'index.php/status/view/' + instrument_id + '/' + time_frame;
  $.get(url, function(data){
    $('#item_info_container').html(data);
  });
};
