var trans_id_list = {};
var latest_tx_id = 0;
var update_breadcrumbs = function(){
  $('.bar_holder').each(function(index,el){
    var pattern = /\w+_\w+_(\d+)/i;
    var m = $(el).prop('id').match(pattern);
    latest_tx_id = m[1] > latest_tx_id ? m[1] : latest_tx_id;
    if(!(m[1] in trans_id_list)){
      var hash = $(el).crypt({method:"sha1"});
      trans_id_list[m[1]] = hash;
    }
  });
  var inst_id = $('#instrument_selector').val();
  var data_obj = {
    'transaction_list' : trans_id_list,
    'instrument_id' : inst_id
  };
  var url = base_url + 'index.php/status/get_status/t';
  $.ajax({
    type: "POST",
    url: url,
    data: data_obj,
    success: function(data){
      if(data != 0){
        $.each(data, function(index,trans_entry){
          var new_item = $('#bar_holder_' + index);
          new_item.html(trans_entry);
          var hash = new_item.crypt({method:"sha1"});
          trans_id_list[index] = hash;
        });
      }
    },
    dataType: 'json'
  });
  
  var new_tx_url = base_url + 'index.php/status/get_latest_transactions/' + inst_id + '/'  + latest_tx_id;
  $.get(new_tx_url, function(data){
    $('#item_info_container').prepend(data);
    setup_tree_data();
  });
  
};

var update_content = function(event){
  var el = $(event.target);
  $.cookie('myemsl_status_last_' + el.prop('id'), el.val(),7);
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

var setup_tree_data = function(){
  $('.tree_holder').each(function(index, el){
    $(el).fancytree();
  });
};

var get_tree_data = function(event, data){
  var id_matcher = /.+_(\d+)/i;
  var m = data.node.key.match(id_matcher);
  var trans_id = parseInt(m[1],10);
  // var url = 
};
