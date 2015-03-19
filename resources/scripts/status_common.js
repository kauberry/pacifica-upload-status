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
  var url = base_url + 'index.php/status/overview/' + instrument_id + '/' + time_frame;
  $('#item_info_container').hide();
  $('#loading_status').fadeIn("slow", function(){
    var getting = $.get(url);
    getting.done(function(data){
      $('#loading_status').fadeOut(200,function(){
        $('#item_info_container').html(data);
        $('#item_info_container').fadeIn('slow',function(){
          $('.tree_holder').each(function(index, el){
            $(el).fancytree();
          });
        });
      });
    });
  });
};

var get_tree_data = function(event, data){
  var id_matcher = /.+_(\d+)/i;
  var m = data.node.key.match(id_matcher);
  var trans_id = parseInt(m[1],10);
  var url = 
};
