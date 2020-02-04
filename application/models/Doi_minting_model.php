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
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
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
        try {
            $query = Requests::post(
                $md_url,
                array(
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ),
                json_encode($transaction_list)
            );
            $results = $query->status_code / 100 == 2 ? json_decode($query->body, true) : [];
        } catch (Exception $e) {
            $results = [];
        }
        return $results;
    }

    public function store_transient_details($doi_entry_info)
    {
        $this->db->insert($this->transient_table, $doi_entry_info);
        $success = $this->db->affected_rows() == 1;
        return $success;
    }
}
