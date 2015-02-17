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
  }
  
  // public function test(){
    // echo phpinfo();
  // }
  
  public function index($instrument_id = 7777, $time_period = 30){
    $this->page_data['page_header'] = "MyEMSL Status Reporting";
    $this->page_data['title'] = "Overview";
    $this->page_data['css_uris'] = array(
      base_url()."resources/stylesheets/status.css",
      base_url()."resources/stylesheets/status_style.css",
      base_url()."resources/scripts/select2/select2.css",
      base_url()."resources/scripts/fancytree/skin-lion/ui.fancytree.css"
    );
    $this->page_data['script_uris'] = array(
      base_url()."resources/scripts/fancytree/jquery.fancytree-all.js",
      base_url()."resources/scripts/emsl_mgmt_view.js",
      base_url()."resources/scripts/select2/select2.js"
    );
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;
    $instrument_group_xref = $this->status->get_instrument_group_list();
    
    $transaction_list = $this->status->get_transactions_for_group($instrument_id,$time_period);
        // $transaction_list = $this->status->get_transactions_for_group_static($instrument_id);
    
    // echo "transaction list";
    // echo "<pre>";
    // var_dump($transaction_list);
    // echo "</pre>";
    $this->page_data['status_list'] = array(
      0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
      3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived'
    );
    $this->page_data['time_period'] = $time_period;
    $this->page_data['instrument_id'] = $instrument_id;
    $this->page_data['instrument_list'] = $instrument_group_xref;
    $this->page_data['transaction_data'] = $transaction_list;
    $this->load->view('emsl_mgmt_view',$this->page_data);
  }
  
  public function get_uploads_by_group_id_ajax(){
    //receives a json block containing a list of group id's and a start_date,
    // returns a set of transactions and summaries sorted by time
    
  }
  
  public function get_files_by_transaction($transaction_id){
    $treelist = $this->status->get_files_for_transaction($transaction_id);
    transmit_array_with_json_header($treelist);
  }
  
}

?>