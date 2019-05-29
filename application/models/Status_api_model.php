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

/**
 * Status API Model
 *
 * The **Status_api_model** performs most of the heavy lifting for the status site.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Status_api_model extends CI_Model
{
    /**
     *  Class constructor
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->local_timezone = $this->config->item('local_timezone');
        $this->load->model('Myemsl_api_model', 'myemsl');
        $this->load->helper(array('item', 'network', 'time'));

        $this->status_list = array(
            0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
            3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived',
        );
        $this->load->library('PHPRequests');
    }

    /**
     *  Retrieves a set of transaction entries that correspond to the combination
     *  of instrument, project, and timeframe specified in the call
     *
     * @param int     $instrument_id [description]
     * @param string  $project_id    [description]
     * @param string  $start_time    [description]
     * @param string  $end_time      [description]
     * @param integer $submitter     [description]
     *
     * @return array   transaction results from search
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_transactions($instrument_id, $project_id, $start_time, $end_time, $submitter = -1)
    {
        // echo "current page offset => ".$this->current_page_offset;
        $transactions_url = "{$this->policy_url_base}/status/transactions/search/details?";
        $url_args_array = array(
            'instrument' => isset($instrument_id) ? $instrument_id : -1,
            'project' => isset($project_id) ? $project_id : -1,
            'start' => local_time_to_utc($start_time, 'Y-m-d H:i:s'),
            'end' => local_time_to_utc($end_time, 'Y-m-d H:i:s'),
            'submitter' => isset($submitter) ? $submitter : -1,
            'requesting_user' => $this->user_id,
            'page' => $this->current_page_number,
            'item_count' => $this->current_items_per_page
        );
        $transactions_url .= http_build_query($url_args_array, '', '&');
        $query = Requests::get($transactions_url, array('Accept' => 'application/json'));
        $results = json_decode($query->body, true);
        $transactions = $results['transactions'];
        return $results;
    }

    /**
     *  Retrieves detailed info for a specified transaction id
     *
     * @param int $transaction_id The transaction id to grab
     *
     * @return array transaction details
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_formatted_transaction($transaction_id)
    {
        $transactions_url = "{$this->metadata_url_base}/transactioninfo/search/details?";
        $url_args_array = array(
            // 'user' => $this->user_id,
            'transaction_id' => $transaction_id
        );
        $transactions_url .= http_build_query($url_args_array, '', '&');

        $query = Requests::get($transactions_url, array('Accept' => 'application/json'));
        $results = json_decode($query->body, true);
        return $results;
    }

    /**
     *  Retrieves a set of project entries for a given set of search terms and
     *  a corresponding requester_id
     *
     * @param string $terms        search terms from the user
     * @param int    $requester_id the user requesting projects
     * @param string $is_active    do we retrieve inactive projects
     *
     * @return array   project details listing
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_projects_by_name($terms, $requester_id, $is_active = 'active')
    {
        $projects_url = "{$this->policy_url_base}/status/projects/search/{$terms}?";
        $url_args_array = array(
            'user' => $this->user_id
        );
        $projects_url .= http_build_query($url_args_array, '', '&');
        $results = [];
        try {
            $query = Requests::get($projects_url, array('Accept' => 'application/json'));
            $results = $query->status_code / 100 == 2 ? json_decode($query->body, true) : [];
        } catch (Exception $e) {
            $results = [];
        }
        return $results;
    }

    /**
     *  More highly detailed transaction info with file lists, etc.
     *
     * @param int $transaction_id The transaction id to grab
     *
     * @return array detailed transaction info
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_transaction_details($transaction_id)
    {
        $transaction_url = "{$this->metadata_url_base}/transactioninfo/by_id/{$transaction_id}?";
        $results = array();

        try {
            $query = Requests::get($transaction_url, array('Accept' => 'application/json'));
            $sc = $query->status_code;
            if ($sc / 100 == 2) {
                //good data, move along
                $results = json_decode($query->body, true);
                if (isset($results['status']) && intval($results['status'] / 100) == 4) {
                    $results = array();
                }
            } elseif ($sc / 100 == 4) {
                if ($sc == 404) {
                    //transaction not found
                    $results = array();
                } else {
                    //some other input error
                }
            } else {
                $results = array();
            }
        } catch (Exception $e) {
            //some other error
        }

        return $results;
    }

    /**
     *  Add up the total size for files specified in a transaction
     *
     * @param int $transaction_id The transaction id to grab
     *
     * @return int  total size of transaction files
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_total_size_for_transaction($transaction_id)
    {
        $transaction = $this->get_transaction_details($transaction_id);
        $total_file_size_bytes = 0;
        foreach ($transaction['files'] as $file_id => $file_info) {
            $total_file_size_bytes += $file_info['size'];
        }
        return $total_file_size_bytes;
    }


    /**
     *  Return the list of files and their associated metadata
     *  for a given transaction id
     *
     * @param integer $transaction_id The transaction to pull
     *
     * @return [type]   [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_files_for_transaction($transaction_id)
    {
        $files_url = "{$this->policy_url_base}/status/transactions/files/{$transaction_id}?";
        $url_args_array = array(
            'user' => $this->user_id
        );
        $results = array();
        $query = Requests::get($files_url, array('Accept' => 'application/json'));
        if (intval($query->status_code / 100) == 2) {
            $results = json_decode($query->body, true);
        }
        $dirs = array();
        $file_list = array();
        $common_path_prefix_array = array();
        if ($results && !empty($results) > 0) {
            foreach ($results as $item_id => $item_info) {
                $subdir = trim($item_info['subdir'], '/');
                $filename = $item_info['name'];
                $path = !empty($subdir) ? "{$subdir}/{$filename}" : $filename;
                $path_array = explode('/', $path);
                $file_list[$path] = $item_id;
            }
            ksort($file_list);
            $temp_list = array_keys($file_list);
            $first_path = array_shift($temp_list);
            $temp_list = array_keys($file_list);
            $last_path = array_pop($temp_list);
            $common_path_prefix_array = $this->get_common_path_prefix($first_path, $last_path);
            $common_path_prefix = implode('/', $common_path_prefix_array);
            foreach ($file_list as $path => $item_id) {
                $item_info = $results[$item_id];
                $path = ltrim(preg_replace('/^' . preg_quote($common_path_prefix, '/') . '/', '', $path), '/');
                $item_info['subdir'] = $path;
                $path_array = explode('/', $path);
                build_folder_structure($dirs, $path_array, $item_info);
            }
        }
        return array(
            'treelist' => $dirs,
            'files' => $results,
            'common_path_prefix_array' => $common_path_prefix_array
        );
    }

    /**
     * Get the common directory prefix for a set of paths so that we can remove it.
     *
     * @param string $first_path first path to compare
     * @param string $last_path  second path to compare
     * @param string $delimiter  path delimiter (defaults to '/')
     *
     * @return array array of common path elements
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_common_path_prefix($first_path, $last_path, $delimiter = '/')
    {
        // $shortest_path = strlen($first_path) < strlen($last_path) ? $first_path : $last_path;
        // $longest_path = $shortest_path == $first_path ? $last_path : $first_path;
        $first_path_array = explode($delimiter, dirname($first_path));
        $last_path_array = explode($delimiter, dirname($last_path));
        $short_path_array = count($first_path_array) < count($last_path_array) ? $first_path_array : $last_path_array;
        $longest_path_array = $short_path_array == $first_path_array ? $last_path_array : $first_path_array;
        $common_path_array = array();
        for ($i=0; $i<count($short_path_array); $i++) {
            if ($short_path_array[$i] == $longest_path_array[$i]) {
                $common_path_array[] = $short_path_array[$i];
            } else {
                break;
            }
        }
        return $common_path_array;
    }

    /**
     * Get the last known transaction number from the md server
     *
     * @return int last known transaction number from the metadata db
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_last_known_transaction()
    {
        $txn_url = "{$this->metadata_url_base}/transactioninfo/last/";
        $last_txn = -1;
        $query = Requests::get($txn_url);
        if (intval($query->status_code / 100) == 2) {
            $results = json_decode($query->body, true);
            $last_txn = $results['latest_transaction_id'];
        }
        return $last_txn;
    }

    /**
     * Retrieve real transaction in progress status from the ingester
     *
     * @param int $transaction_id transaction id to check
     *
     * @return array doctored results object
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_ingest_status($transaction_id)
    {
        $now = new DateTime();
        $default_results_obj = array(
            'task_percent' => "0.000",
            'updated' => local_time_to_utc($now)->format('Y-m-d H:i:s'),
            'task' => '',
            'job_id' => $transaction_id,
            'created' => local_time_to_utc($now)->format('Y-m-d H:i:s'),
            'exception' => '',
            'state' => 'fail'
        );
        $transaction_details = $this->get_transaction_details($transaction_id);
        $upload_present_on_mds = !empty($transaction_details) ? true : false;
        $ingester_url = "{$this->ingester_url_base}/get_state/{$transaction_id}";
        try {
            $query = Requests::get($ingester_url, array('Accept' => 'application/json'));
            $results_obj = json_decode(stripslashes($query->body), true);
            if (intval($query->status_code / 100) == 2 && $results_obj) {
                $task_topic = strtolower(str_replace(' ', '_', $results_obj['task']));
            } else {
                if (intval($query->status_code / 100) == 4) {
                    $task_topic = "no_transaction";
                    $message = $results_obj['message'];
                } else {
                    $task_topic = "server_error";
                    $message = "a server error has occurred";
                }
            }
        } catch (\Exception $e) {
            $results_obj = $default_results_obj;
            $results_obj['task'] = "server_error";
            $results_obj['exception'] = "Could not contact ingester service for status";
            $results_obj['upload_present_on_mds'] = $upload_present_on_mds;
            return $results_obj;
        }
        $results_obj = $default_results_obj;
        $results_obj['task'] = $task_topic;
        $results_obj['exception'] = $message;
        $results_obj['upload_present_on_mds'] = $upload_present_on_mds;
        if ($task_topic == "ingest_metadata" && !empty($transaction_details)) {
            $task_topic = "ingest_complete";
        }
        $translated_message_obj = $this->ingester_messages[$task_topic];
        $results_obj['message'] = strtolower($results_obj['state']) == "ok" ?
            $translated_message_obj['success_message'] : $translated_message_obj['failure_message'];
        $results_obj['state'] = strtolower($results_obj['state']);
        $results_obj['overall_percentage'] = $translated_message_obj['percent_complete'];
        return $results_obj;
    }
}
