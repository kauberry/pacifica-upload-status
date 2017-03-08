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
 * Test is a CI controller class that extends Baseline_controller
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Test_api extends Baseline_api_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Status_api_model', 'status');
        $this->load->model('Cart_api_model', 'cart');
    }

    /**
     * Test Get User Info json.
     *
     * @return void
     */
    public function get_userinfo()
    {
        $user_info = $this->myemsl->get_user_info();
        var_dump($user_info);
    }

    /**
     * Test Get Transaction Info json.
     *
     * @return void
     */
    public function get_transactions()
    {
        $transactions = $this->status->get_transactions(-1, '45796', '2016-12-03', '2016-12-10', 50724);

        echo "<pre>";
        var_dump($transactions);
        echo "</pre>";
    }

    /**
     * Test Get Proposal Info json.
     *
     * @param string $search_terms search terms to use in searching for proposals
     *
     * @return void
     */
    public function get_proposals($search_terms)
    {
        echo "<pre>";
        $proposals = $this->status->get_proposals_by_name($search_terms, $this->user_id, FALSE);
        send_json_array($proposals);
        echo "</pre>";
    }

    /**
     * Test retrieval of cart information
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_active_carts()
    {
        echo "<pre>";
        $this->cart->get_active_carts();
        echo "</pre>";
    }

    /**
     * Test retrieval of files for a given transaction
     *
     * @param int $transaction item to search for files
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_files_for_transaction($transaction)
    {
        echo "<pre>";
        $proposals = $this->status->get_files_for_transaction($transaction);
        send_json_array($proposals);
        echo "</pre>";
    }

}
