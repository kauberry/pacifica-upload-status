<?php
require_once('baseline_controller.php');

class Status extends Baseline_controller {

  function __construct() {
    parent::__construct();
    if($this->admin_access_level < 400){
      $this->page_data['message'] = "You must have at least 'Power User' status to use these pages";
      $this->load->view('insufficient_privileges', $this->page_data);
    }
    $this->load->helper(array('inflector','url','opwhse_search','form','network','edit_equipment'));
    $this->load->library(array('table'));
    $this->load->model('Inventory_model','inv_model');
  }
  
  public function index(){
    $this->load->view('emsl_mgmt_view',$this->page_data);
  }
  
}
?>