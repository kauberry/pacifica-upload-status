var bearer_token = "";
var token_url_base = '/myemsl/itemauth/';
var cart_url_base = '/myemsl/api/2/cart/';

var setup_file_download_links = function(parent_item) {
  parent_item = $(parent_item);
  var tx_id = parent_item.prop('id').replace('tree_','');
  var file_object_collection = parent_item.find('.item_link');
  file_object_collection.click(function(e) {
    var file_object_data = JSON.parse($(e.target).siblings('.item_data_json').html());
    download_myemsl_item(file_object_data);
  });
  var dl_button = $('#dl_button_' + tx_id);
  dl_button.unbind('click').click(function(e){
    var el = $(e.target);
    cart_download(parent_item, null);
  });
};

var cart_download = function(transaction_container, cart_id){
  var selected_files = get_selected_files(transaction_container);
  //check for token
  get_tokens(selected_files);
};

var get_tokens = function(item_id_list, token_list){
  if(!token_list) var token_list = [];
  var item = item_id_list.shift();
  console.log(item + ':getting token');
  var token_url = token_url_base + item;
  var token_getter = $.get(token_url, function(data){
    console.log(item + ':got token');
    token_list.push({'item': item, 'token' : data});
    if(item_id_list.length > 0){
      get_tokens(item_id_list,token_list);
    }else{
      add_cart_items(token_list);
    }
  });
};

var add_cart_items = function(token_list, cart_id){
  if(token_list.length > 0){
    if(!cart_id) cart_id = '';
    var item_info = token_list.shift();
    var item = item_info.item;
    var bearer_token = item_info.token;
    var token_url = token_url_base + item;
    var cart_url = cart_url_base + cart_id;
    var cart_list = [item];
    var cart_submitter = $.ajax({
      url : cart_url,
      type : 'POST',
      data : JSON.stringify({
        'items' : cart_list,
        'auth_token' : bearer_token
      }),
      dataType: 'json'
    });
    cart_submitter.done(function(data,textStatus,jq_obj){
      cart_id = !cart_id ? data.cart_id : cart_id;
      if(token_list.length > 0){
        console.log(item + ':submitted');
        add_cart_items(token_list, cart_id);
      }else{
        submit_cart_for_download(cart_id);
      }
    });
  }
};

var submit_cart_for_download = function(cart_id){
  debugger;
};

var get_selected_files = function(tree_container){
  if(typeof(tree_container) == "string"){
    tree_container = $('#' + tree_container);
  }
  var tree = tree_container.fancytree('getTree');
  var selCount = tree.countSelected();
  var selFiles = [];
  if(selCount > 0){
    var topNode = tree.getRootNode();
    var dataNode = topNode.children[0];
    //check lazyload status and load if necessary
    if(selCount == 1 && dataNode.lazy && dataNode.countChildren == 0){
      dataNode.load();
      selCount = tree.countSelected();
    }
    selFiles = $.map(tree.getSelectedNodes(), function(node){
      if(!node.folder){
        return parseInt(node.key.replace('ft_item_',''),10);
      }
    });
  }
  return selFiles;
};

var download_myemsl_item = function(file_object_data) {
  //get a download token
  var item_id = file_object_data.item_id;

  var token_url = token_url_base + item_id;
  var token_getter = $.ajax({
    url: token_url,
    method:'get'
  });
  token_getter.done(function(data){
    bearer_token = data;
    var token = data;
    myemsl_tape_status(token, file_object_data);
  });
};

var myemsl_tape_status = function(token, file_object_data, cb) {
  var item_id = file_object_data.item_id;
  var file_name = file_object_data.name;

  var ajx = $.ajax({
    //FIXME foo, bar
    url : "/myemsl/item/foo/bar/" + item_id + "/2.txt/?token=" + token + "&locked",
    type : 'HEAD',
    processData : false,
    success : function(token, status_target) {
      return function(ajaxdata, status, xhr) {
        var custom_header = xhr.getResponseHeader('X-MyEMSL-Locked');
        if (custom_header == "false") {
          //pop up a dialog box regarding the item being on tape
        } else {
          window.location.href = '/myemsl/item/foo/bar/' + item_id + '/' + file_name + "?token=" + token;
        }
      };
    }(token, status),
    error : function(token, status_target) {
      // return function(xhr, status, error) {
        // if (xhr.status == 503) {
          // cb('slow');
        // } else {
          // cb('error');
        // }
      // };
    }//(token, status)
  });
  return ajx;
}; 