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

function format_folder_object_json_worse($folder_obj, $folder_name){
  $result = array();
  foreach(array_keys($folder_obj) as $folder_entry){
    var_dump($folder_obj[$folder_entry]);
    echo "\n\n\n\n";
    $output = array('title' => $folder_entry, 'folder' => true);
    if(array_key_exists('folders', $folder_obj[$folder_entry])){
      $output_children = format_folder_object_json($folder_obj[$folder_entry]);
      if(!empty($output_children)){
        foreach($output_children as $child_entry){
          $output['children'][] = $child_entry;
        }  
      }
    }
    if(array_key_exists('files',$folder_obj[$folder_entry])){
      $file_obj = $folder_obj[$folder_entry]['files'];
      $child_output = format_file_object_json($file_obj);
      if(!empty($child_output)){
        foreach($child_output as $child_entry){
          $output['children'][] = $child_emtry;
        }
      }
    }
    $result[] = $output;
  }
  return $result;
}
  
function format_folder_object_json($folder_obj,$folder_name){
  $output = array();
  
  // $output[] = array('title' => $folder_name, 'folder' => TRUE);
  if(array_key_exists('folders', $folder_obj)){
    foreach($folder_obj['folders'] as $folder_entry => $folder_tree){
      $folder_output = array('title' => $folder_entry, 'folder' => true);
      $children = format_folder_object_json($folder_tree, $folder_entry);
      if(!empty($children)){
        foreach($children as $child){
          $folder_output['children'][] = $child; 
        }
      }
      $output[] = $folder_output;
    }
  }
  if(array_key_exists('files', $folder_obj)){
    foreach($folder_obj['files'] as $file_entry){
      $output[] = array('title' => $file_entry);
    }
  }
  // $results[] = $output;
  // return $results;
  return $output;
}


function format_file_object_json($file_obj){
  $children = array();
  foreach($file_obj as $file_entry){
    $children[] = array('title' => $file_entry);
  }
}



function format_file_object_json_old($file_obj, &$file_structure){
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