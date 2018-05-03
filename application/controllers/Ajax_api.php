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
require_once 'Baseline_api_controller.php';

/**
 * Ajax API Class
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Ajax_api extends Baseline_api_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {

        parent::__construct();
        $this->load->model('status_api_model', 'status');
        $this->load->model('Myemsl_api_model', 'myemsl');
        $this->load->helper('network');
        $this->load->library('PHPRequests');
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
    public function get_proposals_by_name($terms = false)
    {
        $prop_list = $this->status->get_proposals_by_name(
            $terms,
            $this->user_id,
            false
        );
        $results = array(
            'total_count' => sizeof($prop_list),
            'incomplete_results' => false,
            'more' => false,
            'items' => array()
        );
        $max_text_len = 200;
        foreach ($prop_list as $item) {
            $textLength = strlen($item['title']);
            $result = substr_replace(
                $item['title'],
                '...',
                $max_text_len/2,
                $textLength-$max_text_len
            );

            // $item['text'] = "<span title='{$item['title']}'>{$result}</span>";
            $item['text'] = $item['display_name'];
            $results['items'][] = $item;
        }
        transmit_array_with_json_header($results);
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
        $proposal_id = false,
        $terms = false
    ) {

        if (!$proposal_id || empty($proposal_id)) {
            //some kind of error callback
            return array();
        }
        $policy_url = "{$this->policy_url_base}/status/instrument/by_proposal_id/{$proposal_id}";
        $query = Requests::get($policy_url, array('Accept' => 'application/json'));
        // $results_body = $query->body;

        print($query->body);
    }

    /**
     * [get_release_states description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_release_states()
    {
        $md_url = "{$this->metadata_url_base}/transactioninfo/release_state";
        $transaction_list = [];
        if ($this->input->is_ajax_request() || file_get_contents('php://input')) {
            $http_raw_post_data = file_get_contents('php://input');
            $transaction_list = json_decode($http_raw_post_data, true);
        }
        $query = Requests::post($md_url, array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ), json_encode($transaction_list));
        print $query->body;
    }

    /**
     * [set_release_state description]
     *
     * @param  [type] $transaction_id [description]
     * @param  [type] $release_state [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function set_release_state($transaction_id, $release_state)
    {
        //This really needs to check permissions
        if (!in_array($release_state, array('released', 'not_released'))) {
            $release_state = 'not_released';
        }
        $content = [
            'authorized_person' => $this->user_id,
            'transaction' => $transaction_id,
        ];
        $md_url = "{$this->metadata_url_base}/transaction_release";
        if ($release_state == 'released') {
            $query = Requests::put($md_url, array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ), json_encode($content));
        }
        $check_url = "{$this->metadata_url_base}/transactioninfo/release_state/{$transaction_id}";
        $check_query = Requests::get($check_url);
        print $check_query->body;
    }

    /**
     * Grabs the last known transaction ID
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_latest_transaction_id()
    {
        $last_id = $this->status->get_last_known_transaction();
        transmit_array_with_json_header(array('last_transaction_id' => $last_id));
    }

    /**
     * Grabs ingest status information from the ingest subsystem
     *
     * @param int $transaction_id transaction_id to investigate
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_ingest_status($transaction_id)
    {
        $results_obj = $this->status->get_ingest_status($transaction_id);
        transmit_array_with_json_header($results_obj);
    }
}
