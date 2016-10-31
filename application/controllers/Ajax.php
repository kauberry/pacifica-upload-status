<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view
 *  the current state of any uploads they may have performed, as
 *  well as enabling the download and retrieval of that data.
 *
 * PHP Version 5
 *
 * @package Pacifica-upload-status
 * @author  Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
require_once 'Baseline_controller.php';

/**
 * Ajax Class
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 *
 * @uses Status_model
 * @uses Myemsl_model
 */
class Ajax extends Baseline_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('status_model', 'status');
        $this->load->model('myemsl_model', 'myemsl');
        $this->load->helper(
            array(
                'inflector', 'item', 'url',
                'form', 'network', 'myemsl'
            )
        );
        $this->load->library(array('table'));
    }

    /**
     * Given a list of search terms, generates a list
     * of HTML-formatted <span> entities, each containing a
     * shortened version of the proposal title in the
     * body and the full length version in the *title*
     * element. Sends to browser as a JSON block.
     *
     * @param string $terms space separated search terms.
     *
     * @return void
     */
    public function get_proposals_by_name($terms = FALSE)
    {
        $prop_list = $this->eus->get_proposals_by_name(
            $terms, $this->user_id, FALSE
        );
        $results = array(
            'total_count' => sizeof($prop_list),
            'incomplete_results' => FALSE,
            'more' => FALSE,
            'items' => array()
        );
        $max_text_len = 200;
        foreach($prop_list as $item){
            $textLength = strlen($item['title']);
            $result = substr_replace(
                $item['title'],
                '...',
                $max_text_len/2,
                $textLength-$max_text_len
            );

            $item['text'] = "<span title='{$item['title']}'>{$result}</span>";
            $results['items'][] = $item;
        }
        send_json_array($results);
    }

    /**
     * Retrieves the full set of instruments that are
     * associated with a given proposal, and formats
     * it to be compatible with the Select2 JSON array
     * loading interface.
     *
     * @param string $proposal_id proposal ID string
     * @param string $terms       space separated list of search terms against
     *                            instruments metadata
     *
     * @return void
     */
    public function get_instruments_for_proposal(
        $proposal_id = FALSE, $terms = FALSE
    )
    {
        if(!$proposal_id) {
            $this->output->set_status_header(
                404, "Proposal ID {$proposal_id} was not found"
            );
            return;
        }
        $full_user_info = $this->myemsl->get_user_info();
        $instruments = array();
        $inst_list = $full_user_info['instruments'];
        if(array_key_exists($proposal_id, $full_user_info['proposals'])) {
            $instruments_available
                = $full_user_info['proposals'][$proposal_id]['instruments'];
        } else {
            $instruments_available = array();
        }
        $total_count = sizeof($instruments_available) + 1;
        asort($instruments_available);
        $instruments[] = array(
            'id' => 0,
            'text' => NULL
        );
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
            'incomplete_results' => FALSE,
            'items' => $instruments
        );

        send_json_array($results);
    }

    /**
     * Retrives the list of available instruments for
     * a given proposal ID, formatting the results
     * as a SELECT object for Select2
     *
     * @param string $proposal_id unique proposal ID
     *
     * @return void
     */
    public function get_instrument_list($proposal_id)
    {
        // $instruments = $this->eus->get_instruments_for_proposal($proposal_id);
        $full_user_info = $this->myemsl->get_user_info();
        $instruments = array();
        if($this->is_emsl_staff) {
            $instruments = $this->eus->get_instruments_for_proposal($proposal_id);
        }else{
            $instruments_available
                = $full_user_info['proposals'][$proposal_id]['instruments'];
            foreach ($instruments_available as $inst_id) {
                $instruments[$inst_id] = "Instrument {$inst_id}: ".
                    $full_user_info['instruments'][$inst_id]['eus_display_name'];
            }
        }
        $instruments[-1] = "All Available Instruments for Proposal {$proposal_id}";

        asort($instruments);

        format_array_for_select2(array('items' => $instruments));
    }
}
