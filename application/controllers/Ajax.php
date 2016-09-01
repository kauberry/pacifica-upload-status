<?php

require_once 'Baseline_controller.php';

class Ajax extends Baseline_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('status_model', 'status');
        $this->load->helper(array('inflector', 'item', 'url', 'opwhse_search', 'form', 'network', 'myemsl'));
        $this->load->library(array('table'));
    }

    public function get_proposals_by_name($terms = false){
        $prop_list = $this->eus->get_proposals_by_name($terms, $this->user_id, false);
        $results = array(
            'total_count' => sizeof($prop_list),
            'incomplete_results' => false,
            'items' => array()
        );
        foreach($prop_list as $item){
            $item['text'] = "<div>Proposal {$item['id']}: {$item['title']}</div>";
            $results['items'][] = $item;
        }
        send_json_array($results);
    }

    public function get_instruments_for_proposal($proposal_id = false, $terms = false){
        if(!$proposal_id){
            $this->output->set_status_header(404, "Proposal ID {$proposal_id} was not found");
            return;
        }

        $inst_list = $this->eus->get_instruments_for_proposal($proposal_id, $terms);

        if(sizeof($inst_list == 0)){

        }



    }


}
