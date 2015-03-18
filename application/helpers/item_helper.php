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

function build_folder_structure(&$dirs, $path_array) {
    if (count($path_array) > 1) {
        if (!isset($dirs[$path_array[0]])) {
            $dirs[$path_array[0]] = array();
        }

        build_folder_structure($dirs[$path_array[0]], array_splice($path_array, 1));
    } else {
        $dirs[] = $path_array[0];
    }
}

function format_folder_structure($ul_struct,&$directory, $dir_name = ""){
  var_dump($ul_struct);
  $directory = "<li id='' class='lazy folder'>{$dir_name}</li>";
}

function format_folder_structure_old($ul_struct, &$directory, $dir_name = "Root"){
  echo $directory;
  echo "\n\n";
  $directory .= "<li class='folder'>{$dir_name}<ul>";
  foreach($ul_struct as $name => $contents){
    if(is_array($contents)){
      format_folder_structure($contents, $directory, $name);
    }else{
      $directory .= "<li>{$contents}</li>";
    }
  }
  $directory .= "</ul></li>";
}

  
function format_bytes($bytes) {
   if ($bytes < 1024) return $bytes.' B';
   elseif ($bytes < 1048576) return round($bytes / 1024, 2).' KB';
   elseif ($bytes < 1073741824) return round($bytes / 1048576, 2).' MB';
   elseif ($bytes < 1099511627776) return round($bytes / 1073741824, 2).' GB';
   else return round($bytes / 1099511627776, 2).' TB';
}

?>