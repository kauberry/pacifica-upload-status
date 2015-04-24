<?php
if(!defined('BASEPATH'))
  exit('No direct script access allowed');

function get_user_details_myemsl($eus_id){
  $CI =& get_instance();
  
  $users_table = "UP_USERS";
  $DB_eus = $CI->load->database('eus_for_myemsl',TRUE);
  
  $select_array = array('person_id', 'first_name','last_name', 'email_address', 'network_id');
  
  $query = $DB_eus->select($select_array)->get_where($users_table, array('person_id' => $eus_id),1);
  
  if($query && $query->num_rows() > 0){
    $results = $query->row_array();
  }
  
  return $results;
  
}


function read_myemsl_config_file($file_specifier = 'general'){
  $CI =& get_instance();
  $ini_path = $CI->config->item('myemsl_config_file_path');
  $ini_items = parse_ini_file("{$ini_path}{$file_specifier}.ini", TRUE);
  return $ini_items;
}



?>