var setup_file_download_links = function(parent_item) {
  parent_item = $(parent_item);
  var file_object_collection = parent_item.find('.item_link');
  file_object_collection.click(function(e) {
    var file_object_data = JSON.parse(parent_item.find('.item_link').siblings('.item_data_json').html());
    download_myemsl_item(file_object_data);
  });
};

var download_myemsl_item = function(file_object_data) {
  // var x
};

var myemsl_tape_status = function(token, item_id, cb) {
  var ajx = $.ajax({
    //FIXME foo, bar
    url : "/myemsl/item/foo/bar/" + item_id + "/2.txt/?token=" + token + "&locked",
    type : 'HEAD',
    processData : false,
    success : function(token, status_target) {
      return function(ajaxdata, status, xhr) {
        var custom_header = xhr.getResponseHeader('X-MyEMSL-Locked');
        if (custom_header == "false") {
          cb('slow');
        } else {
          cb('fast');
        }
      };
    }(token, status),
    error : function(token, status_target) {
      return function(xhr, status, error) {
        if (xhr.status == 503) {
          cb('slow');
        } else {
          cb('error');
        }
      };
    }(token, status)
  });
  return ajx;
}; 