<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Navigation Info Model                                                   */
/*                                                                             */
/*             functionality for setting up left hand nav menu                 */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Status_model extends CI_Model {
  function __construct(){
    parent::__construct();

  }
  
  
  /*
   * gathers wide overview data about many types of instruments for upper-level mgmt
   * 
   */
  function get_status_overview($class_filter = "", $instrument_filter = ""){
    
  }
  
  function get_status_for_instrument_before_date($instrument_list, $start_time){
    if(is_string($instrument_list)){
      $instrument_list = explode(',',$instrument_list);
    }
    
    
    
    
    
  }
  
  function get_status_for_instrument_over_range($instrument_list, $time_period = "1 day"){
    $current_time_obj = new DateTime();
    $current_time_obj->setTime($current_time_obj->getHours(),0,0);
    $start_time = date_modify($current_time_obj, "-{$time_period}");
    return $this->get_status_for_instrument_before_date($instrument_list, $start_time);
    
  }
  
  function get_instrument_group_list($filter = ""){
    $json_string = file_get_contents(FCPATH."resources/json_files/instrument_group_list.json");
    $inst_list = array();
    $inst_list_raw = json_decode($json_string,TRUE);
    foreach($inst_list_raw['RECORDS'] as $item){
      $inst_list[$item['group_id']] = $item['name'];
    }
    return $inst_list;
  }
  
  function get_transactions_for_group($group_id){
    $json_string = file_get_contents(FCPATH."resources/json_files/transactions_{$group_id}.json");
    $transaction_list = array();
    $transaction_list_raw = json_decode($json_string, TRUE);
    foreach($transaction_list_raw['RECORDS'] as $trans){
      $transaction_list[] = $trans['transaction_id'];
    }
    $files_json_string = file_get_contents(FCPATH."resources/json_files/files_{$group_id}.json");
    $files_list = array();
    $files_list_raw = json_decode($json_string,TRUE);
    foreach($files_list_raw as $file){
      $files_list[$file['transaction']][$file['item_id']] = $file;
    }
    
    
  }
  
}
?>