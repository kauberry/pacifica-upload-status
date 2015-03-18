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
      base_url()."resources/scripts/status_common.js"
    );
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;  
    
    if($lookup_type == 'j' || $lookup_type == 'job'){
      //lookup transaction_id from job
      $lookup_type = 't';
      $id = $this->status->get_transaction_id($id);
    }
    
    $transaction_list = array();
    $transaction_list[] = $id;
    
    
    $transaction_info = $this->status->get_formatted_object_for_transactions($transaction_list);
    $this->page_data['status_list'] = $this->status_list;
    $this->page_data['transaction_data'] = $transaction_info;
        // var_dump($transaction_info);
    $this->page_data['show_instrument_data'] = true;
    $this->load->view('single_item_view',$this->page_data);
    
    
    
    
}

  
  public function overview($instrument_id = 7777, $time_period = 30){
    $instrument_group_xref = $this->status->get_instrument_group_list();
    if(!$this->input->is_ajax_request()){
      $view_name = 'emsl_mgmt_view';
      $this->page_data['page_header'] = "MyEMSL Status Reporting";
      $this->page_data['title'] = "Overview";
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
    
    $transaction_list = $this->status->get_transactions_for_group($instrument_id,$time_period);
    
    $this->page_data['status_list'] = $this->status_list;
    $this->page_data['transaction_data'] = $transaction_list;
    $this->load->view($view_name,$this->page_data);
  }
  
  public function get_uploads_by_group_id_ajax(){
    //receives a json block containing a list of group id's and a start_date,
    // returns a set of transactions and summaries sorted by time
    
  }
  
  public function get_files_by_transaction($transaction_id){
    $treelist = $this->status->get_files_for_transaction($transaction_id);
    transmit_array_with_json_header($treelist);
  }
  
  public function get_status($lookup_type, $id){
    //lookup by (j)ob or (t)ransaction
    $status_obj = $this->status->get_status_for_transaction($lookup_type,$id);
    if(!empty($status_obj)){
      $sortable = $status_obj;
      krsort($sortable);
      $latest_step_obj = array_shift($sortable);
      $latest_step = intval($latest_step_obj['step']);
      $status_info = array(
        'latest_step' => $latest_step,
        'status_list' => $this->status_list
      );
      $this->load->view('status_breadcrumb_insert_view.html',$status_info);
    }
  }
  
  public function test_get_status($lookup_type,$transaction_id){
    var_dump($this->status->get_status_for_transaction($lookup_type,$transaction_id));
  }
  
}

?>