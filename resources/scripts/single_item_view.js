$(function(){
      
  $('.tree_holder').each(function(index, el){
    $(el).fancytree();
  });
  
  window.setInterval(update_breadcrumbs,5000);
  
  
  
});

