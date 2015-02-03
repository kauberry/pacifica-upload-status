<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function format_files_object($files_object){
  $tree = array();
  
  $list = "<ul>";
  
  foreach($files_object as $item_id => $item){
    $size = format_bytes(intval($item['size']));
    if(empty($item['subdir'])){
      $list .= "<li id=\"item_{$item['item_id']}\">{$item['name']} <em>[{$size}]</em></li>";
    }else{
      $parts = explode('/',$item['subdir']);
      while($parts){
        $dir_fragment = array_shift($parts);
      }
    }
  }
  
  // foreach($files_object as $item_id => $item){
    // $size = format_bytes(intval($item['size']));
    // if(empty($item['subdir'])){
      // //at top level, just add file info
      // $tree[] = "<div id=\"item_{$item['item_id']}\">{$item['name']} <em>[{$size}]</em></div>";
    // }else{
      // $parts = explode('/',$item['subdir']);
      // $value = "<div id=\"item_{$item['item_id']}\">{$item['name']} <em>[{$size}]</em></div>";
      // while($parts) {
         // $value = array(array_pop($parts) => $value);
      // }
      // $tree[] = $value;
    // }
    // var_dump($tree);
  // }
}
  
function format_bytes($bytes) {
   if ($bytes < 1024) return $bytes.' B';
   elseif ($bytes < 1048576) return round($bytes / 1024, 2).' KB';
   elseif ($bytes < 1073741824) return round($bytes / 1048576, 2).' MB';
   elseif ($bytes < 1099511627776) return round($bytes / 1073741824, 2).' GB';
   else return round($bytes / 1099511627776, 2).' TB';
}

?>