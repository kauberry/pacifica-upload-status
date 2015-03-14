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
  
  // function get_instrument_group_list_static($filter = ""){
    // $json_string = file_get_contents(FCPATH."resources/json_files/instrument_group_list.json");
    // $inst_list = array();
    // $inst_list_raw = json_decode($json_string,TRUE);
    // foreach($inst_list_raw['RECORDS'] as $item){
      // $inst_list[$item['group_id']] = $item['name'];
    // }
    // return $inst_list;
  // }
  
  
  function get_instrument_group_list($filter = ""){
    $DB_myemsl = $this->load->database('default',TRUE);
    
    $DB_myemsl->select(array('group_id','name'));
    $DB_myemsl->where("(type = 'omics.dms.instrument' or type ilike 'instrument.%') and name not in ('0','foo')");
    $query = $DB_myemsl->order_by('name')->get('groups');
    
    $results = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        $results[$row->group_id] = $row->name;
      }
    }
    return $results;
  }
  
  
  function get_files_for_transaction($transaction_id){
    $DB_myemsl = $this->load->database('default',TRUE);
    
    $file_select_array = array(
      'f.item_id','f.name','f.subdir','t.stime','f.transaction','f.size'
    );
    
    $DB_myemsl->select($file_select_array)->from('transactions t')->join('files f', 't.transaction = f.transaction');
    $DB_myemsl->where('f.transaction',$transaction_id);
    $DB_myemsl->order_by('t.stime desc');
    $files_query = $DB_myemsl->get();
    
    $files_list = array();
    
    if($files_query && $files_query->num_rows()>0){
      foreach($files_query->result_array() as $row){
        $files_list[$row['item_id']] = $row;
      }
      $file_tree = array();
      
      
      $dirs = array();
      
      foreach($files_list as $item_id => $item_info){
        $subdir = $item_info['subdir'];
        $filename = $item_info['name'];
        $path = !empty($subdir) ? "{$subdir}/{$filename}" : $filename;
        $path_array = explode('/',$path);
        build_folder_structure($dirs, $path_array);
      }
      return array('treelist' => $dirs, 'files' => $files_list);
    }
  }
  
  function get_transactions_for_group($group_id, $num_days_back){
    $raw_transaction_list = array();
    $transaction_list = array();
    $DB_myemsl = $this->load->database('default',TRUE);
    
    $select_array = array(
      'max(f.transaction) as transaction_id',
      'max(gi.group_id) as group_id'
    );
    
    $DB_myemsl->select($select_array)->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
    $DB_myemsl->group_by('f.transaction')->order_by('f.transaction desc');
    $query = $DB_myemsl->where('gi.group_id',$group_id)->get();
    
    
    //filter the transactions for date
    $results = array();
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $raw_transaction_list[] = $row->transaction_id;
      }
      
      
      $today = new DateTime();
      $earliest_date = clone $today;
      $earliest_date->modify("-{$num_days_back} days");
      $DB_myemsl->select('transaction')->where_in('transaction',$raw_transaction_list)->where('stime >=',$earliest_date->format('Y-m-d'));
      $trans_query = $DB_myemsl->get('transactions');
      if($trans_query && $trans_query->num_rows()>0){
        foreach($trans_query->result() as $row){
          $transaction_list[] = $row->transaction;
        }
      }
      
      if(!empty($transaction_list)){
        foreach($transaction_list as $transaction_id){
          $files_obj = $this->get_files_for_transaction($transaction_id);
          $file_tree = $files_obj['treelist'];
          $flat_list = $files_obj['files'];
          foreach($flat_list as $item){
            $sub_time = new DateTime($item['stime']);
            break;
          }
          $time_string = $sub_time->format('Y-m-d H:i:s');
  
          $results['times'][$time_string] = $transaction_id;
          
          $results['transactions'][$transaction_id]['files'] = $file_tree;
          $results['transactions'][$transaction_id]['flat_files'] = $flat_list;
          if(sizeof($files_obj)>0){
            $status_list = $this->get_status_for_transaction('transaction',$transaction_id);
            if(sizeof($status_list) > 0){
              $results['transactions'][$transaction_id]['status'] = $status_list;
            }else{
              $results['transactions'][$transaction_id]['status'] = "Unknown";
            }
          }
        }
        arsort($results['times']);
      }
      
    }
    return $results;
  }

  function get_status_for_transaction($lookup_type, $id){
    $lookup_types = array(
      't' => 'trans_id', 'trans_id' => 'trans_id',
      'j' => 'jobid', 'job' => 'jobid'
    );
    if(!array_key_exists($lookup_type,$lookup_types)){
      $lookup_field = 'trans_id';
    }else{
      $lookup_field = $lookup_types[$lookup_type];
    }
    $DB_myemsl = $this->load->database('default',TRUE);
    $status_list = array();
    $select_array = array(
      'jobid','trans_id','person_id','step','message','status'
    );
    $DB_myemsl->select($select_array)->where($lookup_field,$id);
    $ingest_query = $DB_myemsl->get('ingest_state');
    if($ingest_query && $ingest_query->num_rows()>0){
      foreach($ingest_query->result_array() as $row){
        $status_list[$row['step']] = $row;
      }
    }
    return $status_list;
  }
  
  
  
  
  // function get_transactions_for_group_static($group_id){
    // $json_string = file_get_contents(FCPATH."resources/json_files/transactions_{$group_id}.json");
    // $transaction_list = array();
    // $transaction_list_raw = json_decode($json_string, TRUE);
    // foreach($transaction_list_raw['RECORDS'] as $trans){
      // $transaction_list[] = $trans['transaction_id'];
    // }
    // $files_json_string = file_get_contents(FCPATH."resources/json_files/files_{$group_id}.json");
    // $files_list = array();
    // $files_list_raw = json_decode($files_json_string,TRUE);
    // foreach($files_list_raw['RECORDS'] as $file){
      // $files_list[$file['transaction']][$file['item_id']] = $file;
    // }
    // $status_json_string = file_get_contents(FCPATH."resources/json_files/ingest_states_group_{$group_id}.json");
    // $status_list = array();
    // $status_list_raw = json_decode($status_json_string, TRUE);
    // foreach($status_list_raw['RECORDS'] as $status){
      // $status_list[$status['trans_id']][$status['step']] = $status;
    // }
//     
//     
    // $results = array();
//     
    // foreach($transaction_list as $transaction){
      // if(array_key_exists($transaction,$files_list)){
        // $results['transactions'][$transaction]['files'] = $files_list[$transaction];
        // if(array_key_exists($transaction, $status_list)){
          // $results['transactions'][$transaction]['status'] = $status_list[$transaction];
        // }else{
          // $results['transaction'][$transaction]['status'] = "Unknown";
        // }
        // foreach($files_list[$transaction] as $item){
          // $sub_time = new DateTime($item['stime']);
          // break;
        // }
        // $time_string = $sub_time->format('Y-m-d H:i:s');
// 
        // $results['times'][$time_string] = $transaction;
      // }
    // }
//     
    // arsort($results['times']);
//     
    // return $results;
  // }
  
}
?>