var trans_id_list = {};
var latest_tx_id = 0;
var inst_id = 0;

$(function(){
  inst_id = $('#instrument_selector').length > 0 ? $('#instrument_selector').val() : initial_inst_id;
});

var update_breadcrumbs = function(){
  $('.bar_holder').each(function(index,el){
    var pattern = /\w+_\w+_(\d+)/i;
    var m = $(el).prop('id').match(pattern);
    var this_tx_id = parseInt(m[1],10);
    latest_tx_id = this_tx_id > latest_tx_id ? this_tx_id : latest_tx_id;
    if(!(this_tx_id in trans_id_list)){
      var hash = $(el).crypt({method:"sha1"});
      trans_id_list[this_tx_id] = hash;
    }
  });
  var data_obj = {
    'transaction_list' : trans_id_list,
    'instrument_id' : inst_id
  };
  if(inst_id && trans_id_list.length > 0){
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
  }
  
};

var get_latest_transactions = function(){
  if(inst_id && latest_tx_id){
    var new_tx_url = base_url + 'index.php/status/get_latest_transactions/' + inst_id + '/'  + latest_tx_id;
    $.get(new_tx_url, function(data){
      if(data.length > 0){
        $('#item_info_container').prepend(data);
        setup_tree_data();
        setup_metadata_disclosure();
      }
    });  
  }
};

var update_content = function(event){
  var el = $(event.target);
  if(['proposal_selector','instrument_selector','timeframe_selector'].indexOf(el.prop('id')) >= 0){
    $.cookie('myemsl_status_last_' + el.prop('id'), el.val(),7);
  }
  var proposal_id = $('#proposal_selector').select2('val').length > 0 ? $('#proposal_selector').select2('val') : 0;
  var instrument_id = $('#instrument_selector').select2('val').length > 0 ? $('#instrument_selector').select2('val') : 0;
  var time_frame = $('#timeframe_selector').select2('val').length > 0 ? $('#timeframe_selector').select2('val') : 0;
  var url = base_url + 'index.php/status/overview/' + instrument_id + '/' + time_frame;
  if(proposal_id && instrument_id && time_frame){
    $('#item_info_container').hide();
    $('#loading_status').fadeIn("slow", function(){
      var getting = $.get(url);
      getting.done(function(data){
        if(data){
          $('#loading_status').fadeOut(200,function(){
            $('#item_info_container').html(data);
            $('#item_info_container').fadeIn('slow',function(){
              setup_tree_data();
              setup_metadata_disclosure();
            });
          });
        }
      });
    });
  }
  if(el.prop('id') == 'proposal_selector'){ 
    //check to see if instrument list is current
    if(el.val() != initial_proposal_id){
      get_instrument_list(el.val());
      initial_proposal_id = el.val();
    }
  }
};

var get_instrument_list = function(proposal_id){
  var inst_url = base_url + 'index.php/status/get_instrument_list/' + proposal_id;
  $.getJSON(inst_url,function(data){
    $('#instrument_selector').select2({
      data: data.items,
      placeholder: "Select an Instrument..."
    });
    $('#instrument_selector').enable();
    initial_instrument_list = [];
    
    $.each(data.items, function(index,item){
      initial_instrument_list.push(item.id);
    });
    if(initial_instrument_list.indexOf(parseInt(initial_instrument_id,10)) < 0){
      $('#instrument_selector').val('').trigger('change');
    }
  });
};

var setup_metadata_disclosure = function(){
  $('ul.metadata_container').hide();
  $('.disclosure_button').click(function(){
    var el = $(this);
    var container = el.parentsUntil('div').siblings('ul.metadata_container');
    if(el.hasClass('dc_up')){
      //view is rolled up and hidden
      el.removeClass('dc_up').addClass('dc_down');
      container.slideDown("slow");
    }else if(el.hasClass('dc_down')){
      //view is open and visible
      el.removeClass('dc_down').addClass('dc_up');
      container.slideUp("slow");

    }else{
      
    }
  });
  
};

var setup_tree_data = function(){
  $('.tree_holder').each(function(index, el){
    if($(el).find('ul.ui-fancytree').length == 0){
      $(el).fancytree(
        {
          lazyLoad: function(event, data){
            var node = data.node;
            data.result = {
              url: base_url + 'index.php/status/get_lazy_load_folder',
              data: {mode: "children", parent: node.key},
              method:"POST",
              cache: false
            };
          }
        }
      );
    }
  });
};

var get_tree_data = function(event, data){
  var id_matcher = /.+_(\d+)/i;
  var m = data.node.key.match(id_matcher);
  var trans_id = parseInt(m[1],10);
  // var url = 
};
