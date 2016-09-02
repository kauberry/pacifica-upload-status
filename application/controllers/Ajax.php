<?php

require_once 'Baseline_controller.php';

class Ajax extends Baseline_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('status_model', 'status');
        $this->load->model('myemsl_model', 'myemsl');
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
        $max_text_len = 110;
        foreach($prop_list as $item){
            $textLength = strlen($item['title']);
            $result = substr_replace($item['title'], '...', $max_text_len/2, $textLength-$max_text_len);

            $item['text'] = "<span title='{$item['title']}'>{$result}</span>";
            $results['items'][] = $item;
        }
        send_json_array($results);
    }

    public function get_instruments_for_proposal($proposal_id = false, $terms = false){
        if(!$proposal_id){
            $this->output->set_status_header(404, "Proposal ID {$proposal_id} was not found");
            return;
        }
        $full_user_info = $this->myemsl->get_user_info();
        $instruments = array();
        $inst_list = $full_user_info['instruments'];
        $instruments_available = array_key_exists($proposal_id,$full_user_info['proposals']) ? $full_user_info['proposals'][$proposal_id]['instruments'] : array();
        $total_count = sizeof($instruments_available) + 1;
        asort($instruments_available);
        $instruments[] = array(
            'id' => -1,
            'text' => "All Available Instruments for Proposal {$proposal_id}",
            'name' => "All Instruments",
            'active' => 'Y'
        );
        foreach ($instruments_available as $inst_id) {
            $instruments[] = array(
                'id' => $inst_id,
                'text' => "Instrument {$inst_id}: {$full_user_info['instruments'][$inst_id]['eus_display_name']}",
                'name' => $full_user_info['instruments'][$inst_id]['eus_display_name'],
                'active' => $inst_list[$inst_id]['active_sw']
            );
        }
        // $instruments[-1] = "All Available Instruments for Proposal {$proposal_id}";
        $results = array(
            'total_count' => $total_count,
            'incomplete_results' => false,
            'items' => $instruments
        );

        send_json_array($results);
    }

    public function get_instrument_list($proposal_id)
    {
        // $instruments = $this->eus->get_instruments_for_proposal($proposal_id);
        $full_user_info = $this->myemsl->get_user_info();
        $instruments = array();
        if($this->is_emsl_staff){
            $instruments = $this->eus->get_instruments_for_proposal($proposal_id);
        }else{
            $instruments_available = $full_user_info['proposals'][$proposal_id]['instruments'];
            foreach ($instruments_available as $inst_id) {
                $instruments[$inst_id] = "Instrument {$inst_id}: {$full_user_info['instruments'][$inst_id]['eus_display_name']}";
            }
        }
        $instruments[-1] = "All Available Instruments for Proposal {$proposal_id}";

        asort($instruments);

        format_array_for_select2(array('items' => $instruments));
    }



}
