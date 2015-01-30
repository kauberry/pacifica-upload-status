<?php
require_once('baseline_controller.php');

class Status extends Baseline_controller {

  function __construct() {
    parent::__construct();
    if($this->admin_access_level < 400){
      $this->page_data['message'] = "You must have at least 'Power User' status to use these pages";
      $this->load->view('insufficient_privileges', $this->page_data);
    }
    $this->load->model('status_model','status');
    $this->load->helper(array('inflector','url','opwhse_search','form'));
    $this->load->library(array('table'));
  }
  
  public function index(){
    $this->page_data['page_header'] = "MyEMSL Status Reporting";
    $this->page_data['title'] = "Overview";
    
    $instrument_group_xref = $this->status->get_instrument_group_list();
    
    
    $this->load->view('emsl_mgmt_view',$this->page_data);
  }
  
}
?>