<?php
require_once('baseline_controller.php');

class Status extends Baseline_controller {

  function __construct() {
    parent::__construct();
    // if($this->admin_access_level < 400){
      // $this->page_data['message'] = "You must have at least 'Power User' status to use these pages";
      // $this->load->view('insufficient_privileges', $this->page_data);
    // }
    $this->load->model('status_model','status');
    $this->load->model('Myemsl_model','myemsl');
    $this->load->helper(array('inflector','item','url','opwhse_search','form','network'));
    $this->load->library(array('table'));
    $this->status_list = array(
      0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
      3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived'
    );
    
  }
  
  public function view($lookup_type,$id){
    $this->page_data['page_header'] = "Upload Report";
    $this->page_data['title'] = "Upload Report";
    $this->page_data['css_uris'] = array(
      base_url()."resources/scripts/fancytree/skin-lion/ui.fancytree.css",
      base_url()."resources/stylesheets/status.css",
      base_url()."resources/stylesheets/status_style.css",
      base_url()."resources/stylesheets/file_directory_styling.css"
    );
    $this->page_data['script_uris'] = array(
      base_url()."resources/scripts/fancytree/jquery.fancytree-all.js",
      base_url()."resources/scripts/status_common.js",
      base_url()."resources/scripts/single_item_view.js"
    );
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;  
    
    if($lookup_type == 'j' || $lookup_type == 'job'){
      //lookup transaction_id from job
      $lookup_type = 't';
      $id = $this->status->get_transaction_id($id);
    }
    $inst_id = $this->status->get_instrument_for_id('t',$id);
    $lookup_type_description = $lookup_type = 't' ? 'transaction' : 'job';
    $transaction_list = array();
    $transaction_list[] = $id;
    
    $transaction_info = $this->status->get_formatted_object_for_transactions($transaction_list);
    $this->page_data['status_list'] = $this->status_list;
    if(empty($transaction_info)){
      $this->page_data['message'] = "No {$lookup_type_description} with an identifier of {$id} was found";
      $this->page_data['script_uris'] = array();
    }
    $this->page_data['transaction_data'] = $transaction_info;
    $this->page_data['js'] = "var initial_inst_id = {$inst_id};";
        // var_dump($transaction_info);
    $this->page_data['show_instrument_data'] = true;
    $this->load->view('single_item_view',$this->page_data);
    
    
    
    
}

  
  public function overview($instrument_id = 8029, $time_period = 1){
    $instrument_group_xref = $this->status->get_instrument_group_list();
    $time_period = $this->input->cookie('myemsl_status_last_timeframe_selector') ? $this->input->cookie('myemsl_status_last_timeframe_selector') : $time_period;
    $instrument_id = $this->input->cookie('myemsl_status_last_instrument_selector') ? $this->input->cookie('myemsl_status_last_instrument_selector') : $instrument_id;
    if(!$this->input->is_ajax_request()){
      $view_name = 'emsl_mgmt_view';
      $this->page_data['page_header'] = "MyEMSL Status Reporting";
      $this->page_data['title'] = "Overview";
      $this->page_data['informational_message'] = "";
      $this->page_data['css_uris'] = array(
        base_url()."resources/scripts/fancytree/skin-lion/ui.fancytree.css",
        base_url()."resources/stylesheets/status.css",
        base_url()."resources/stylesheets/status_style.css",
        base_url()."resources/scripts/select2/select2.css",
        base_url()."resources/stylesheets/file_directory_styling.css"
      );
      $this->page_data['script_uris'] = array(
        base_url()."resources/scripts/fancytree/jquery.fancytree-all.js",
        base_url()."resources/scripts/status_common.js",
        base_url()."resources/scripts/emsl_mgmt_view.js",
        base_url()."resources/scripts/select2/select2.js"
      );
      $this->page_data['load_prototype'] = false;
      $this->page_data['load_jquery'] = true;
      $this->page_data['time_period'] = $time_period;
      $this->page_data['instrument_id'] = $instrument_id;
      $this->page_data['instrument_list'] = $instrument_group_xref;
    }else{
      $view_name = 'upload_item_view.html';
    }
    $this->page_data['informational_message'] = "";
    $results = $this->status->get_transactions_for_group($instrument_id,$time_period);
    $this->page_data['status_list'] = $this->status_list;
    // $this->page_data['transaction_data'] = $transaction_list;
    $this->page_data['transaction_data'] = $results['transaction_list'];
    if($results['time_period_empty']){
      $list_size = sizeof($results['transaction_list']['times']);
      $this->page_data['informational_message'] = "No uploads were found during this time period.<br />The {$list_size} most recent entries for this instrument are below.";
    }    
    $this->load->view($view_name,$this->page_data);
  }
  
  
  public function get_files_by_transaction($transaction_id){
    $treelist = $this->status->get_files_for_transaction($transaction_id);
    // var_dump($treelist);
    $output_array = format_folder_object_json($treelist['treelist']);
    // var_dump($output_array);
    transmit_array_with_json_header($output_array);
  }
  
  public function get_latest_transactions($instrument_id,$latest_id){
    $new_transactions = $this->status->get_latest_transactions($instrument_id,$latest_id);
    $results = $this->status->get_formatted_object_for_transactions($new_transactions);
    foreach($new_transactions as $tx_id){
      $group_list = $this->status->get_groups_for_transaction($tx_id);
      $results['transactions'][$tx_id]['groups'] = $group_list;
    }
    
    $this->page_data['status_list'] = $this->status_list;
    $this->page_data['transaction_data'] = $results;
    $view_name = 'upload_item_view.html';
    if(!empty($results['times'])){
      $this->load->view($view_name, $this->page_data);
    }else{
      print "";
    }
  }
  
  public function get_status($lookup_type, $id = 0){
    //lookup by (j)ob or (t)ransaction
    //check for list of transactions in post
    if($this->input->post('transaction_list')){
      $lookup_type = 't';
      $item_list = $this->input->post('transaction_list');
    }elseif($this->input->post('job_list')){
      $lookup_type = 'j';
      $item_list = $this->input->post('job_list');
    }elseif($id > 0){
      $item_list = array($id);
    }

    $item_keys = array_keys($item_list);
    sort($item_keys);
    $last_id = array_pop($item_keys);
        
    $status_info = array();
    
    $status_obj = $this->status->get_status_for_transaction($lookup_type,array_keys($item_list));
    if(!empty($status_obj)){
      foreach($status_obj as $item_id => $item_info){
        $sortable = $item_info;
        krsort($sortable);
        $latest_step_obj = array_shift($sortable);
        $latest_step = intval($latest_step_obj['step']);
        $status_info_temp = array(
          'latest_step' => $latest_step,
          'status_list' => $this->status_list,
          'transaction_id' => $item_id
        );
        $item_text = trim($this->load->view('status_breadcrumb_insert_view.html',$status_info_temp, true));
        if($item_list[$item_id] != sha1($item_text)){
          $status_info[$item_id] = $item_text;
        }
      }
      krsort($status_info);
      if($this->input->is_ajax_request()){
      // if(sizeof($status_info) > 1){
        transmit_array_with_json_header($status_info);
      }elseif(sizeof($status_info) == 1){
        $this->load->view('status_breadcrumb_insert_view.html',$status_info[$id]);
      }
      
    }
  }

  public function get_lazy_load_folder(){
    if(!$this->input->post('parent')){
      print("");
      return;
    }
    $node = intval(str_replace("treeData_","",$this->input->post('parent')));
    $treelist = $this->status->get_files_for_transaction($node);
    $output_array = format_folder_object_json($treelist['treelist'],"test");
    transmit_array_with_json_header($output_array);
  }

  public function job_status($job_id = -1){
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,true);
    if(!$values && $job_id > 0){
      //must not have a list of values, so just check the one
      $values = array($job_id);
    }
    $results = $this->status->get_job_status($values, $this->status_list);
    transmit_array_with_json_header($results);
  }
  
  
  public function test_get_instrument_list(){
    var_dump($this->status->get_instrument_group_list());
  }
  
  public function test_get_groups_for_transaction($transaction_id){
    $this->status->get_groups_for_transaction($transaction_id);
    
  }
  
  public function test_get_userinfo(){
    $user_info = $this->myemsl->get_user_info_myemsl();
    var_dump($user_info);
  }
  
}

?>