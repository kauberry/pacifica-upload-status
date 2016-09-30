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
require_once APPPATH.'libraries/Requests.php';

/**
 * API_model is a CI Model class
 *
 * The **API_model** class enables interaction with the Pacifica webservices
 * stack to retrieve information about file and group metadata for display
 * within the status tool main page
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Api_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('myemsl');
        Requests::register_autoloader();
        $this->myemsl_ini = read_myemsl_config_file('general');
        // $this->load->database('default');
    }

    /**
     *  Returns the list of valid internal Pacifica groups,
     *  filtered by the given search term
     *
     *  @param string $filter partial string on which to
     *                        filter the retrieved results
     *
     *  @return array   hierarchical object containing the
     *                  returned rows
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_available_group_types($filter = '')
    {
        $DB_metadata = $this->load->database('default', TRUE);

        if (!empty($filter)) {
            $DB_metadata->like('type', $filter);
        }
        $results = array();
        $query = $DB_metadata->select('type')->distinct()->order_by('type')->get('groups');
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $results[] = $row->type;
            }
        }

        return $results;
    }

    /**
     *  Retrieve the set of transactions/items that match a certain
     *  set of metadata pairs in a parseable array format
     *
     *  @param array  $metadata_pairs  name/value array of search terms
     *  @param string $search_operator which operator to apply between terms terms
     *                                       terms
     *
     *  @return array   set of results matching the search pairs
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function search_by_metadata($metadata_pairs, $search_operator = 'AND')
    {
        $DB_metadata = $this->load->database('default', TRUE);
        // check for valid search operator
        $search_operator = strtoupper($search_operator);
        $valid_operators = array('AND', 'OR');
        if (!in_array($search_operator, $valid_operators)) {
            $search_operator = 'AND';
        }

        //check for valid types
        $clean_pairs = $this->_clean_up_metadata_pairs($metadata_pairs);
        $compiled_info = array('results_count' => 0);
        if (empty($clean_pairs)) {
            return $compiled_info;
        }

        //build the search query for group_id list
        $found_group_ids = array();
        $group_id_relationships = array();
        foreach ($clean_pairs as $field => $value) {
            if (!array_key_exists($field, $group_id_relationships)) {
                $group_id_relationships[$field] = array();
            }
            $where_array = array(
                'lower(type)' => strtolower($field), 'lower(name)' => !is_numeric($value) ? strtolower($value) : $value,
            );
            $DB_metadata->select('group_id')->where($where_array);
            $group_query = $DB_metadata->get('groups', 1);
            if ($group_query && $group_query->num_rows() > 0) {
                foreach ($group_query->result() as $group_row) {
                    $group_id_relationships[$field][] = $group_row->group_id;
                    $found_group_ids[] = $group_row->group_id;
                }
            }
        }

        //now use the group_ids to find the items related to each one
        if (!empty($found_group_ids)) {
            $item_results = array();
            $item_query = $DB_metadata->where_in('group_id', $found_group_ids)->get('group_items');
            if ($item_query && $item_query->num_rows() > 0) {
                foreach ($item_query->result() as $item_row) {
                    if (!array_key_exists($item_row->group_id, $item_results)) {
                        $item_results[$item_row->group_id] = array('items' => array());
                    }

                    $item_results[$item_row->group_id]['items'][] = $item_row->item_id;
                }

                $item_list = array_shift($item_results);
                $item_list = $item_list['items'];
                if ($search_operator == 'AND') {
                    foreach ($item_results as $filter) {
                        $item_list = array_merge($item_list, $filter['items']);
                    }
                } else {
                    foreach ($item_results as $filter) {
                        $item_list = array_intersect($item_list, $filter['items']);
                    }
                }
                $file_info = $this->_get_transaction_info($item_list);
                $compiled_info = $this->_get_metadata_entries($file_info);
            }
        }

        return $compiled_info;
    }

    /**
     *  Retrieve basic metadata for a given file item, as specified
     *  by its item_id in the database
     *
     *  @param integer $item_id The item id to be searched
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_item_info($item_id)
    {
        $DB_metadata = $this->load->database('default', TRUE);

        $select_array = array(
            'f.item_id as itemid', "CONCAT(f.subdir,'/',f.name) as full_path",
            'f.name as filename', 'f.size', "t.stime AT TIME ZONE 'US/Pacific' as stime",
            'h.hashsum', 'f.verified', 'f.aged',
        );
        $fi_row = array('error_message' => 'Could not find item.');
        $DB_metadata->select($select_array)->where('f.item_id', $item_id);
        $DB_metadata->from('files f')->join('hashsums h', 'f.item_id = h.item_id');
        $DB_metadata->join('transactions t', 't.transaction = f.transaction');
        $file_info_query = $DB_metadata->limit(1)->get();
        if ($file_info_query && $file_info_query->num_rows() > 0) {
            $fi_row = $file_info_query->row_array();
            $fi_row['type'] = 'file';
            $fi_row['checksum'] = array('sha1' => $fi_row['hashsum']);
            unset($fi_row['hashsum']);
        }

        return $fi_row;
    }

    /**
     *  Retrieve the combined transaction metadata for a set of
     *  items, returning a list of metadata indexed by transaction
     *
     *  @param array $item_list a list of item_id's to scan
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _get_transaction_info($item_list)
    {
        $DB_metadata = $this->load->database('default', TRUE);

        //get a list of transactions for this list of item_id's
        $trans_query = $DB_metadata->select('transaction')->distinct()->where_in('item_id', $item_list)->get('files');
        $transaction_list = array();
        if ($trans_query && $trans_query->num_rows() > 0) {
            foreach ($trans_query->result() as $row) {
                $transaction_list[$row->transaction] = '0000-00-00 00:00:00';
            }
        }
        $file_info = array();
        if (!empty($transaction_list)) {
            //first, get the submission times for each transaction
            $DB_metadata->select(array("stime AT TIME ZONE 'US/Pacific' as stime", 'transaction'));
            $stime_query = $DB_metadata->where_in('transaction', array_keys($transaction_list))->get('transactions');
            if ($stime_query && $stime_query->num_rows() > 0) {
                foreach ($stime_query->result() as $stime_row) {
                    $stime = new DateTime($stime_row->stime);
                    $transaction_list[$stime_row->transaction] = $stime->format('Y-m-d H:i:s');
                }
            }

            $select_array = array(
                'f.item_id', "CONCAT(f.subdir,'/',f.name) as full_path",
                'f.name as filename', 'f.size as size_in_bytes',
                'f.transaction', 'h.hashsum', 'f.verified', 'f.aged',
            );
            $file_info = array();
            $DB_metadata->select($select_array)->where_in('transaction', array_keys($transaction_list));
            $DB_metadata->from('files f')->join('hashsums h', 'f.item_id = h.item_id');
            $file_info_query = $DB_metadata->get();
            if ($file_info_query && $file_info_query->num_rows() > 0) {
                foreach ($file_info_query->result_array() as $fi_row) {
                    $fi_row['submit_time'] = $transaction_list[$fi_row['transaction']];
                    $file_info['transactions'][$fi_row['transaction']]['file_info'][$fi_row['item_id']] = $fi_row;
                }
            }
        }

        return $file_info;
    }

    /**
     *  Given a set of file_info, retrieve the metadata tags from
     *  the groups/group_items tables
     *
     *  @param array $file_info a list of file-level metadata to search
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _get_metadata_entries($file_info)
    {
        $DB_metadata = $this->load->database('default', TRUE);

        //get a representative item_id from each transaction
        $DB_metadata->select(array('MIN(item_id) as rep_item_id', 'transaction'))->group_by('transaction');
        $item_query = $DB_metadata->where_in('transaction', array_keys($file_info['transactions']))->get('files');
        if ($item_query && $item_query->num_rows() > 0) {
            foreach ($item_query->result() as $item_row) {
                $DB_metadata->where('item_id', $item_row->rep_item_id);
                $file_info['transactions'][$item_row->transaction]['result_count'] = count($file_info['transactions'][$item_row->transaction]['file_info']);
                $group_query = $DB_metadata->from('groups g')->join('group_items gi', 'gi.group_id = g.group_id')->get();
                if ($group_query && $group_query->num_rows() > 0) {
                    foreach ($group_query->result() as $md_row) {
                        $file_info['transactions'][$item_row->transaction]['metadata'][$md_row->type] = $md_row->name;
                    }
                }
            }
        }

        return $file_info;
    }

    /**
     *  Checks over a group of name/value metadata pairs to assure that
     *  the names are valid group identifiers
     *
     *  @param array $pairs a set of name/value pairs to clean
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _clean_up_metadata_pairs($pairs)
    {
        //check for valid types
        $cleaned_types = array();
        $clean_pairs = array();
        $valid_types = $this->get_available_group_types();
        foreach ($pairs as $field => $value) {
            if (!in_array($field, $valid_types)) {
                //hmmm... try stripping off group.* if it exists
                if (preg_match('/group\.(.+)/i', $field, $m)) {
                    $new_field = $m[1];
                    if (in_array($new_field, $valid_types)) {
                        $cleaned_types[$field] = $new_field;
                        continue;
                    }
                }
            } else {
                $cleaned_types[$field] = $field;
            }
        }
        foreach ($pairs as $field => $value) {
            if (array_key_exists($field, $cleaned_types)) {
                $clean_pairs[$cleaned_types[$field]] = $value;
            }
        }

        return $clean_pairs;
    }
}
