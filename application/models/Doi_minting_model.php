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
 * @package Pacifica-upload-status
 * @author  Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */

/**
 * DOI Minting Model
 *
 * The **DOI Minting Model** handles backend storage and tracking of minted
 * DOI entries.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Doi_minting_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        $this->load->database('default');
        $this->transient_table = "doi_records";
    }

    /**
     * [get_release_states description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_release_states($transaction_list)
    {
        $md_url = "{$this->metadata_url_base}/transactioninfo/release_state";
        $query = Requests::post($md_url, array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ), json_encode($transaction_list));
        $results = json_decode($query->body, true);
        $transient_info = [];
        foreach ($results as $result_item) {
            $transient_info = $this->get_transient_records_for_transaction($result_item['transaction']);
            $results[$result_item['transaction']]['transient_info'] = $transient_info;
        }
        return $results;
    }

    public function store_transient_details($doi_entry_info)
    {
        $this->db->insert($this->transient_table, $doi_entry_info);
        $success = $this->db->affected_rows() == 1;
        return $success;
    }

    public function get_transient_records_for_transaction($transaction_id, $dataset_id = "")
    {
        $resource_query = $this->db->get_where($this->transient_table, ['transaction_id' => $transaction_id]);
        $resource_results = $resource_query->num_rows() > 0 ? $resource_query->result_array() : [];
        return $resource_results;
    }
}
