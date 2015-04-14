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
    // var_dump($path_array);
    // echo "\n\n\n\n\n\n\n";
    if (count($path_array) > 1) {
        if (!isset($dirs['folders'][$path_array[0]])) {
            $dirs['folders'][$path_array[0]] = array();
        }

        build_folder_structure($dirs['folders'][$path_array[0]], array_splice($path_array, 1));
    } else {
        $dirs['files'][] = $path_array[0];
    }
}

function format_folder_object_json($folder_obj, &$output_structure){
  // if(!is_array($output_structure) || empty($output_structure)){
    // $output_structure = array();
  // }
  $child_output = array();
  foreach(array_keys($folder_obj) as $folder_entry){
    // $child_output = array();
    $output = array('title' => $folder_entry, 'folder' => true);
    if(array_key_exists('folders', $folder_obj[$folder_entry])){
      $child_output = array();
      $f_obj = $folder_obj[$folder_entry]['folders'];
      format_folder_object_json($f_obj, $child_output);
    }
    // if(array_key_exists('files',$folder_obj[$folder_entry])){
      // // $child_output = array();
      // $file_obj = $folder_obj[$folder_entry]['files'];
      // format_file_object_json($file_obj, $child_output);
    // }
  }
  // if(array_key_exists('files',$folder_obj)){
    // // $child_output = array();
    // $file_obj = $folder_obj['files'];
    // format_file_object_json($file_obj, $child_output);
  // }
  
  if(!empty($child_output)){
    $output['children'] = $child_output;
  }
  $output_structure[] = $output;
}

function format_file_object_json($file_obj, &$file_structure){
  foreach($file_obj as $file_entry){
    $file_structure[] = array('title' => $file_entry);
  }
}

function format_folder_object_html($folder_obj, &$output_structure){
  foreach(array_keys($folder_obj) as $folder_entry){
    $output_structure .= "<li class='folder'>{$folder_entry}<ul>";
    if(array_key_exists('folders', $folder_obj[$folder_entry])){
      $f_obj = $folder_obj[$folder_entry]['folders'];
      format_folder_object_html($f_obj, $output_structure);
    }
    if(array_key_exists('files',$folder_obj[$folder_entry])){
      $file_obj = $folder_obj[$folder_entry]['files'];
      format_file_object_html($file_obj, $output_structure);
    }
    $output_structure .= "</ul></li>";
  }
}

function format_file_object_html($file_obj, &$output_structure){
  foreach($file_obj as $file_entry){
    $output_structure .= "<li>{$file_entry}</li>";
  }
}
  
function format_bytes($bytes) {
   if ($bytes < 1024) return $bytes.' B';
   elseif ($bytes < 1048576) return round($bytes / 1024, 2).' KB';
   elseif ($bytes < 1073741824) return round($bytes / 1048576, 2).' MB';
   elseif ($bytes < 1099511627776) return round($bytes / 1073741824, 2).' GB';
   else return round($bytes / 1099511627776, 2).' TB';
}

?>