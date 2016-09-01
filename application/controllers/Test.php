<?php

require_once 'Baseline_controller.php';

class Test extends Baseline_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('status_model', 'status');
        /* already loaded in status model
        $this->load->model('Myemsl_model', 'myemsl');
        $this->load->model('Cart_model', 'cart');
        $this->load->helper(array('inflector', 'item', 'url', 'opwhse_search', 'form', 'network'));
        $this->load->library(array('table'));
        */
        // $this->status_list = array(
        //   0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
        //   3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived',
        // );
    }

    public function index()
    {
        redirect('status/overview');
    }

    public function test_get_status($job_id)
    {
        var_dump($this->status->get_status_for_transaction('j', $job_id));
    }

    public function test_get_instrument_list($instrument_id = '')
    {
        var_dump($this->status->get_instrument_group_list($instrument_id));
    }

    public function test_get_groups_for_proposal($proposal_id)
    {
        $results = $this->status->get_proposal_group_list($proposal_id);
        var_dump($results);
    }

    public function test_get_groups_for_transaction($transaction_id)
    {
        $results = $this->status->get_groups_for_transaction($transaction_id);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    public function test_get_transactions_for_proposal($proposal_id)
    {
        $results = $this->status->get_transactions_for_group(-1, 30, $proposal_id);
        var_dump($results);
    }

    public function test_get_userinfo()
    {
        $user_info = $this->myemsl->get_user_info();
        // var_dump($user_info);
    }

    public function test_get_proposals_for_instrument($instrument_id)
    {
        $inst_list = $this->eus->get_proposals_for_instrument($instrument_id);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';
    }

    public function get_instruments_by_proposal($proposal_id){
        $inst_list = $this->myemsl->get_instruments_by_proposal($proposal_id);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';

    }

    public function get_proposals_by_name_eus($filter = 'false'){
        $inst_list = $this->eus->get_proposals_by_name($filter,$this->user_id);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';

    }


    public function get_instruments_by_proposal_eus($proposal_id,$filter = false){
        $inst_list= $this->eus->get_instruments_for_proposal($proposal_id, $filter);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';
    }
}
