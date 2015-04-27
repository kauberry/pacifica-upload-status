var setup_file_download_links = function(parent_item){
  parent_item = $(parent_item);
  var file_object_collection = parent_item.find('.item_link');
  file_object_collection.click(function(e){
    var file_object_data = JSON.parse(parent_item.find('.item_link').siblings('.item_data_json').html());
    download_myemsl_item(file_object_data);
  });
};
