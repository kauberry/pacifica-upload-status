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
 * Test is a CI controller class that extends Baseline_controller
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Test extends Baseline_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Status_model', 'status');
        /* already loaded in status model
        $this->load->model('Myemsl_model', 'myemsl');
        $this->load->model('Cart_model', 'cart');
        $this->load->helper(array(
            'inflector', 'item', 'url', 'opwhse_search', 'form', 'network'
        ));
        $this->load->library(array('table'));
        */
        // $this->status_list = array(
        //   0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
        //   3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived',
        // );
    }

    /**
     * Test redirect method to the real class.
     *
     * @return void
     */
    public function index()
    {
        redirect('status/overview');
    }

    /**
     * Test Get Job Status
     *
     * @param int $job_id job ID
     *
     * @return void
     */
    public function test_get_status($job_id)
    {
        var_dump($this->status->get_status_for_transaction('j', $job_id));
    }

    /**
     * Get Instrument List
     *
     * @param string $instrument_id instrument ID
     *
     * @return void
     */
    public function test_get_instrument_list($instrument_id = '')
    {
        var_dump($this->status->get_instrument_group_list($instrument_id));
    }

    /**
     * Test Get Groups by Proposal ID
     *
     * @param string $proposal_id proposal ID
     *
     * @return void
     */
    public function test_get_groups_for_proposal($proposal_id)
    {
        $results = $this->status->get_proposal_group_list($proposal_id);
        var_dump($results);
    }

    /**
     * Test Get Groups for Transaction ID
     *
     * @param int $transaction_id transaction ID
     *
     * @return void
     */
    public function test_get_groups_for_transaction($transaction_id)
    {
        $results = $this->status->get_groups_for_transaction($transaction_id);
        echo '<pre>';
        var_dump($results);
        echo '</pre>';
    }

    /**
     * Test Get Transactions for Proposal ID
     *
     * @param string $proposal_id proposal ID
     *
     * @return void
     */
    public function test_get_transactions_for_proposal($proposal_id)
    {
        $results = $this->status->get_transactions_for_group(-1, 30, $proposal_id);
        var_dump($results);
    }

    /**
     * Test Get User Info json.
     *
     * @return void
     */
    public function test_get_userinfo()
    {
        $user_info = $this->myemsl->get_user_info();
        var_dump($user_info);
    }

    /**
     * Test Get Proposals from Instrument ID
     *
     * @param string $instrument_id instrument ID
     *
     * @return void
     */
    public function test_get_proposals_for_instrument($instrument_id)
    {
        $inst_list = $this->eus->get_proposals_for_instrument($instrument_id);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';
    }

    /**
     * Get Instruments for Proposal ID
     *
     * @param string $proposal_id proposal ID
     *
     * @return void
     */
    public function get_instruments_by_proposal($proposal_id)
    {
        $inst_list = $this->myemsl->get_instruments_by_proposal($proposal_id);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';

    }

    /**
     * Get Proposals by Name with String Filter
     *
     * @param string $filter space separated string of terms to filter proposals
     *                       metadata on.
     *
     * @return void
     */
    public function get_proposals_by_name_eus($filter = 'false')
    {
        $inst_list = $this->eus->get_proposals_by_name($filter, $this->user_id);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';

    }

    /**
     * Get Instruments for Proposal ID with string filter
     *
     * @param string $proposal_id proposal ID to get instruments from
     * @param string $filter      space separated string of terms to filter
     *                            instruments on by their metadata
     *
     * @return void
     */
    public function get_instruments_by_proposal_eus($proposal_id,$filter = FALSE)
    {
        $inst_list= $this->eus->get_instruments_for_proposal($proposal_id, $filter);
        echo '<pre>';
        var_dump($inst_list);
        echo '</pre>';
    }

    /**
     *  Test get_formatted_object_for_transactions
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_formatted_object_for_transactions()
    {
        $trans_list = array(56,57,58);
        $obj = $this->status->get_formatted_object_for_transactions($trans_list);
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }


    /**
     *  Test get_formatted_object_for_job
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_formatted_object_for_job()
    {
        $job_list = array(2000056,2000057,2000058);
        $obj = $this->status->get_formatted_object_for_job(2000056);
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }

    /**
     *  Test get_total_size_for_transactions
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_total_size_for_transactions()
    {
        $trans_list = array(56,57,58);
        $obj = $this->status->get_total_size_for_transactions($trans_list);
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }

    /**
     *  Test get_transaction_info
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_transaction_info()
    {
        $trans_list = array(56,57,58);
        $job_list = array(2000056,2000057,2000058);
        $job_id = array_pop($job_list);
        $obj = $this->status->get_transaction_info($job_id);
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }


    /**
     *  Test get_files_for_transaction
     *
     *  @param integer $trans_id transaction id to check
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_files_for_transaction($trans_id = FALSE)
    {
        $trans_list = array(56,57,58);
        $trans_id = $trans_id ? $trans_id : array_pop($trans_list);
        $obj = $this->status->get_files_for_transaction($trans_id);
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }

    /**
     *  [get_instrument_group_list description]
     *
     *  @param integer $inst_id instrument to get group list for
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instrument_group_list($inst_id)
    {
        $obj = $this->status->get_instrument_group_list($inst_id);
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }

}
