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
  
  function get_instrument_group_list_static($filter = ""){
    $json_string = file_get_contents(FCPATH."resources/json_files/instrument_group_list.json");
    $inst_list = array();
    $inst_list_raw = json_decode($json_string,TRUE);
    foreach($inst_list_raw['RECORDS'] as $item){
      $inst_list[$item['group_id']] = $item['name'];
    }
    return $inst_list;
  }
  
  
  function get_instrument_group_list($filter = ""){
    
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
    $files_list_raw = json_decode($files_json_string,TRUE);
    foreach($files_list_raw['RECORDS'] as $file){
      $files_list[$file['transaction']][$file['item_id']] = $file;
    }
    $status_json_string = file_get_contents(FCPATH."resources/json_files/ingest_states_group_{$group_id}.json");
    $status_list = array();
    $status_list_raw = json_decode($status_json_string, TRUE);
    foreach($status_list_raw['RECORDS'] as $status){
      $status_list[$status['trans_id']][$status['step']] = $status;
    }
    
    
    $results = array();
    
    foreach($transaction_list as $transaction){
      if(array_key_exists($transaction,$files_list)){
        $results['transactions'][$transaction]['files'] = $files_list[$transaction];
        if(array_key_exists($transaction, $status_list)){
          $results['transactions'][$transaction]['status'] = $status_list[$transaction];
        }else{
          $results['transaction'][$transaction]['status'] = "Unknown";
        }
        foreach($files_list[$transaction] as $item){
          $sub_time = new DateTime($item['stime']);
          break;
        }
        $time_string = $sub_time->format('Y-m-d H:i:s');

        $results['times'][$time_string] = $transaction;
      }
    }
    
    arsort($results['times']);
    
    return $results;
  }
  
}
?>