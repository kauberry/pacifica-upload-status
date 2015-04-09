<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Status Model                                                            */
/*                                                                             */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Status_model extends CI_Model {
  function __construct(){
    parent::__construct();
    $this->local_timezone = "US/Pacific";
  }
  
  
  /*
   * gathers wide overview data about many types of instruments for upper-level mgmt
   * 
   */
  function get_status_overview($class_filter = "", $instrument_filter = ""){
    
  }
  
  
  

  
  function get_status_for_instrument_over_range($instrument_list, $time_period = "1 day"){
    $current_time_obj = new DateTime();
    $current_time_obj->setTime($current_time_obj->getHours(),0,0);
    $start_time = date_modify($current_time_obj, "-{$time_period}");
    return $this->get_status_for_instrument_before_date($instrument_list, $start_time);
    
  }
  


  
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
      'f.item_id','f.name','f.subdir',"t.stime AT TIME ZONE 'US/Pacific' as stime",'f.transaction','f.size'
    );
    
    $DB_myemsl->trans_start();
    $DB_myemsl->query("set local timezone to '{$this->local_timezone}';");
    // echo $DB_myemsl->last_query();
    $DB_myemsl->select($file_select_array)->from('transactions t')->join('files f', 't.transaction = f.transaction');
    $DB_myemsl->where('f.transaction',$transaction_id);
    $DB_myemsl->order_by('t.stime desc');
    $files_query = $DB_myemsl->get();
    $DB_myemsl->trans_complete();
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


  function get_latest_transactions($group_id, $last_id){
    //if last_id is -1, grab the last transaction so we can display its date as a pointer 
    $transaction_list = array();
    $DB_myemsl = $this->load->database('default',TRUE);
    $select_array = array(
      'max(f.transaction) as transaction_id',
      'max(gi.group_id) as group_id'
    );
    
    $raw_transaction_list = array();
    if($last_id > 0){
      $DB_myemsl->trans_start();
      $DB_myemsl->query("set local timezone to '{$this->local_timezone}';");    
      $DB_myemsl->select($select_array)->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
      $DB_myemsl->group_by('f.transaction')->order_by('f.transaction desc');
      $query = $DB_myemsl->where('gi.group_id',$group_id)->where('f.transaction >',$last_id)->get();
      $DB_myemsl->trans_complete();
    }else{
      $DB_myemsl->trans_start();
      $DB_myemsl->query("set local timezone to '{$this->local_timezone}';");    
      $DB_myemsl->select($select_array)->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
      $DB_myemsl->group_by('f.transaction')->order_by('f.transaction desc');
      $query = $DB_myemsl->where('gi.group_id',$group_id)->order_by('f.transaction desc')->limit(1)->get();
      $DB_myemsl->trans_complete();
    }

    if($query && $query->num_rows() > 0){
      //must have some new transactions
      foreach($query->result() as $row){
        $raw_transaction_list[] = intval($row->transaction_id);
      }
    }

    sort($raw_transaction_list);
    return $raw_transaction_list;
  }

  
  function get_transactions_for_group($group_id, $num_days_back, $eus_proposal_id = ""){
    $transaction_list = array();
    $is_empty = false;
    $DB_myemsl = $this->load->database('default',TRUE);
    
    $select_array = array(
      'max(f.transaction) as transaction_id',
      'max(gi.group_id) as group_id'
    );
    $DB_myemsl->trans_start();
    $DB_myemsl->query("set local timezone to '{$this->local_timezone}';");    
    $DB_myemsl->select($select_array)->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
    $DB_myemsl->group_by('f.transaction')->order_by('f.transaction desc');
    $query = $DB_myemsl->where('gi.group_id',$group_id)->get();
    // echo $DB_myemsl->last_query();
    $DB_myemsl->trans_complete();    
    $results = array();
    //filter the transactions for date
    $results = array();
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $raw_transaction_list[] = $row->transaction_id;
      }
      $today = new DateTime();
      $earliest_date = clone $today;
      $earliest_date->modify("-{$num_days_back} days");
      $DB_myemsl->trans_start();
      $DB_myemsl->query("set local timezone to '{$this->local_timezone}';");
      $DB_myemsl->select('transaction')->where_in('transaction',$raw_transaction_list)->where("stime AT TIME ZONE 'US/Pacific' >=",$earliest_date->format('Y-m-d'));
      $trans_query = $DB_myemsl->get('transactions');
      $DB_myemsl->trans_complete();
      if($trans_query && $trans_query->num_rows()>0){
        foreach($trans_query->result() as $row){
          $transaction_list[] = $row->transaction;
        }
      }
      if(!empty($transaction_list)){
        $results = $this->get_formatted_object_for_transactions($transaction_list);
      }else{
        $DB_myemsl->select('transaction')->where_in('transaction',$raw_transaction_list)->order_by('transaction desc')->limit(3);
        $trans_query = $DB_myemsl->get('transactions');
        if($trans_query && $trans_query->num_rows()>0){
          foreach($trans_query->result() as $row){
            $transaction_list[] = $row->transaction;
          }
          $results = $this->get_formatted_object_for_transactions($transaction_list);
        }
        $is_empty = true;        
      }
    }
    

    return array('transaction_list' => $results, 'time_period_empty' => $is_empty);
  }

  function get_groups_for_transaction($transaction_id){
    $DB_myemsl = $this->load->database('default',TRUE);
    
    $select_array = array(
      'f.transaction as transaction_id',
      'g.group_id as group_id', 'g.group_name as group_name',
      'g.type as group_type'
    );
    
    $DB_myemsl->select($select_array)->distinct();
    $DB_myemsl->from('files f')->join('group_items gi', 'gi.item_id = f.item_id');
    $DB_myemsl->join('groups g', 'g.group_id = gi.group_id');
    $query = $DB_myemsl->where('f.transaction', $transaction_id)->get();
    
    
    // SELECT distinct f."transaction", "g"."group_id", "g"."name", "g"."type"
      // FROM myemsl.files f INNER JOIN myemsl.group_items gi ON gi.item_id = f.item_id
        // INNER JOIN myemsl.groups "g" ON "g".group_id = gi.group_id where f.transaction = 1056;
    
    echo $DB_myemsl->last_query();
  }





  function get_formatted_object_for_transactions($transaction_list){
    $results = array('transactions' => array(),'times' => array());
    foreach($transaction_list as $transaction_id){
      $files_obj = $this->get_files_for_transaction($transaction_id);
      if(!empty($files_obj)){
        $file_tree = $files_obj['treelist'];
        $flat_list = $files_obj['files'];
        foreach($flat_list as $item){
          $sub_time = new DateTime($item['stime']);
          break;
        }
        $time_string = $sub_time->format('Y-m-d H:i:s');
    
        $results['times'][$time_string] = $transaction_id;
        
        $results['transactions'][$transaction_id]['files'] = $file_tree;
        // $results['transactions'][$transaction_id]['flat_files'] = $flat_list;
        if(sizeof($files_obj)>0){
          $status_list = $this->get_status_for_transaction('transaction',$transaction_id);
          if(sizeof($status_list) > 0){
            $results['transactions'][$transaction_id]['status'] = $status_list;
          }else{
            $results['transactions'][$transaction_id]['status'] = "Unknown";
          }
        }
      }
    }
    if(!empty($results['times'])){
      arsort($results['times']);
    }
    return $results;
  }
  
  
  
  

  function get_status_for_transaction($lookup_type, $id_list){
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
    $DB_myemsl->trans_start();
    $DB_myemsl->query("set local timezone to '{$this->local_timezone}';");    
    $DB_myemsl->select($select_array)->where_in($lookup_field,$id_list);
    $ingest_query = $DB_myemsl->get('ingest_state');
    $DB_myemsl->trans_complete();
    if($ingest_query && $ingest_query->num_rows()>0){
      foreach($ingest_query->result_array() as $row){
        $status_list[$row[$lookup_field]][$row['step']] = $row;
      }
    }
    return $status_list;
  }
  
  function get_instrument_for_id($lookup_type,$id){
    $lookup_types = array(
      't' => 'trans_id', 'trans_id' => 'trans_id',
      'j' => 'jobid', 'job' => 'jobid'
    );
    if(!array_key_exists($lookup_type,$lookup_types)){
      $lookup_field = 'trans_id';
    }else{
      $lookup_field = $lookup_types[$lookup_type];
    }
    if($lookup_field == 'jobid'){
      $id = $this->get_transaction_id($id);
      $lookup_field = 'trans_id';
    }
    
    $DB_myemsl = $this->load->database('default',TRUE);
    $inst_id = false;
    $query = $DB_myemsl->select('group_id as instrument_id')->get_where('v_transactions_by_group_id', array('transaction_id' => $id),1);
    if($query && $query->num_rows() > 0){
      $inst_id = $query->row()->instrument_id;
    }
    return $inst_id;
  }
  
  
  
  
  
  function get_transaction_id($job_id){
    $DB_myemsl = $this->load->database('default',TRUE);
    $DB_myemsl->trans_start();
    $DB_myemsl->query("set local timezone to '{$this->local_timezone}';");    
    $query = $DB_myemsl->select('trans_id as transaction_id')->get_where('ingest_state',array('jobid' =>$job_id),1);
    $DB_myemsl->trans_complete();
    $transaction_id = -1;
    if($query && $query->num_rows()>0){
      $transaction_id = $query->row()->transaction_id;
    }
    return $transaction_id;
  }
  

}
?>