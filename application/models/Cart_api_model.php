<?php
/**
 * Pacifica.
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view,
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
 * Cart API Model.
 *
 * The **Cart_api_model** talks to the cart daemon on the backend to make
 * and retrieve carts and files.
 *
 * Cart submission object needs to contain...
 *  - name (string): A descriptive name for the cart
 *  - description (optional, string): optional extended description
 *  - files (array): list of file IDs and corresponding paths to pull
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Cart_api_model extends CI_Model
{
    /**
     *  Class constructor.
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->cart_url_base = $this->config->item('internal_cart_url');
        $this->cart_dl_base = $this->config->item('external_cart_url');
        $this->load->database('default');
        // $this->load->library('PHPRequests');
        $this->load->helper('item');
    }

    /**
     *  Generates the an ID for the cart, then makes the appropriate entries
     *  in the cart status database.
     *
     *  @param array $cart_submission_json Cart request JSON, converted to array
     *
     *  @return string  cart_uuid
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function cart_create($cart_submission_json)
    {
        $return_array = array(
            'cart_uuid' => NULL,
            'message' => '',
            'success' => FALSE,
            'retrieval_url' => NULL
        );
        $local_cart_success = FALSE;
        $new_submission_info = $this->_clean_cart_submission($cart_submission_json);
        if(!$new_submission_info) {
            $return_array['message'] = 'No files were located for this submission';
            $this->output->set_status_header(410);
            return $return_array;
        }
        $cart_submission_object = $new_submission_info['cleaned_submisson_object'];
        $cart_uuid = $this->_generate_cart_uuid($cart_submission_object);

        $cart_submit_response = $this->_submit_to_cartd($cart_uuid, $cart_submission_object);

        if (intval($cart_submit_response->status_code / 100) == 2) {
            $local_cart_success = $this->_create_cart_entry(
                $cart_uuid,
                $cart_submission_object,
                $new_submission_info['file_details']
            );
            if(!$local_cart_success) {
                $return_array['message'] = "An error occurred while saving changes to the local database";
                $this->output->set_status_header(500);
                return $return_array;
            }
        } else {
            //return error about not being able to create the cart entry properly
            $this->output->set_status_header($cart_submit_response->status_code, 'An error occurred while talking to the cart server');
            $return_array['message'] = 'cart creation was unsuccessful';
            return $return_array;
        }
        $return_array['success'] = TRUE;
        $return_array['cart_uuid'] = $cart_uuid;
        $return_array['message'] = "A cart named '{$cart_submission_object['name']}' was successfully created";
        $return_array['retrieval_url'] = "{$this->cart_dl_base}/{$cart_uuid}";

        return $return_array;
    }

    /**
     * [get_active_carts description].
     *
     * @param array $cart_uuid_list list of cart entities to interrogate
     *
     * @return array
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _get_cart_info($cart_uuid_list)
    {
        //get the list of any carts from the database
        $select_array = array(
            'c.cart_uuid as cart_uuid',
            'MIN(c.name) as name',
            'MIN(c.description) as description',
            'MIN(c.created) as created',
            'MIN(c.updated) as updated',
            'SUM(ci.file_size_bytes) as total_file_size_bytes',
            'COUNT(ci.file_id) as total_file_count',
        );
        $this->db->select($select_array);
        $this->db->from('cart c');
        $this->db->join('cart_items ci', 'c.cart_uuid = ci.cart_uuid', 'INNER');
        $this->db->group_by('c.cart_uuid');
        $query = $this->db->where_in('c.cart_uuid', $cart_uuid_list)->get();
        $return_array = array();
        foreach ($query->result_array() as $row) {
            $cart_uuid = $row['cart_uuid'];
            $return_array[$cart_uuid] = $row;
        }

        return $return_array;
    }

    /**
     * Retrieve the list of active carts for this user.
     *
     * @param string $filter keyword filter for cart search
     *
     * @return array list of available cart_uuid's
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _get_user_cart_list($filter = '')
    {
        $this->db->where('deleted is null')->where('owner', $this->user_id);
        if (!empty($filter)) {
            $this->db->like('CONCAT(name, description)', $filter);
        }
        $this->db->order_by('created');
        $query = $this->db->select('cart_uuid')->get('cart');
        $cart_uuid_list = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $cart_uuid_list[] = $row->cart_uuid;
            }
        }

        return $cart_uuid_list;
    }

    /**
     * Retrieve the status for a specified cart entry.
     *
     * @param array $cart_uuid_list simple array list of SHA256 cart uuid's
     *
     * @return array summarized status report from cartd
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function cart_status($cart_uuid_list = FALSE)
    {
        if (!$cart_uuid_list) {
            $cart_uuid_list = $this->_get_user_cart_list();
        }

        if(empty($cart_uuid_list)) {
            return array();
        }

        $cart_info = $this->_get_cart_info($cart_uuid_list);
        $status_lookup = array(
            'waiting' => 'In Preparation',
            'staging' => 'File Retrieval',
            'bundling' => 'File Packaging',
            'ready' => 'Ready for Download',
            'error' => 'Error Condition',
            'deleted' => 'Deleted Cart Entry'
        );
        $status_return = array();
        foreach ($cart_uuid_list as $cart_uuid) {
            $cart_url = "{$this->cart_url_base}/{$cart_uuid}";
            $response = Requests::head($cart_url);
            $status = $response->headers['X-Pacifica-Status'];
            $message = $response->headers['X-Pacifica-Message'];
            $response_overview = intval($response->status_code / 100);
            if ($response_overview == 2 && $status != 'error') {
                //looks like it went through ok
                $success = TRUE;
            }elseif($response->status_code == 404) {
                $success = FALSE;
                continue;
            } else {
                $success = FALSE;
            }
            if($status == 'deleted') {
                continue;
            }
            $this->update_cart_info($cart_uuid, array('last_known_state' => $status));
            $status_return['lookup'] = $status_lookup;
            $status_return['categories'][$status][] = $cart_uuid;
            $status_return['cart_list'][$cart_uuid] = array(
                'status' => $status,
                'friendly_status' => $status_lookup[$status],
                'message' => $message,
                'success' => $success,
                'response_code' => $response->status_code,
                'name' => $cart_info[$cart_uuid]['name'],
                'description' => $cart_info[$cart_uuid]['description'],
                'total_file_size_bytes' => $cart_info[$cart_uuid]['total_file_size_bytes'],
                'friendly_file_size' => format_bytes($cart_info[$cart_uuid]['total_file_size_bytes']),
                'total_file_count' => $cart_info[$cart_uuid]['total_file_count'],
                'created' => $cart_info[$cart_uuid]['created'],
                'updated' => $cart_info[$cart_uuid]['updated'],
                'user_download_url' => "{$this->cart_dl_base}/{$cart_uuid}",
            );
        }

        return $status_return;
    }

    /**
     * Check for the existence and readiness of a cart instance,
     * and pass along the redirected download url when ready.
     *
     * @param string $cart_uuid SHA256 hash from _generate_cart_uuid
     *
     * @return string cart download url
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function cart_retrieve($cart_uuid)
    {
        $cart_url = "{$this->cart_url_base}/{$cart_uuid}";
        //check for ready status
        $status_info = $this->cart_status(array($cart_uuid));
        if ($status_info['success'] == TRUE && $status_info['status'] == 'ready') {
            //looks like the cart is ready to download. Let's go.
            $download_url = "{$this->cart_dl_base}/{$cart_uuid}";
        } else {
            $download_url = FALSE;
        }
        $this->output->set_header('X-Pacifica-Status: {$status_info["status"]}');
        $this->output->set_header('X-Pacifica-Message: {$status_info["message"]}');

        return $download_url;
    }

    /**
     * Removes a cart instance from active service.
     *
     * @param string $cart_uuid SHA256 hash from _generate_cart_uuid
     *
     * @return bool true/false for success/failure
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function cart_delete($cart_uuid)
    {
        $cart_url = "{$this->cart_url_base}/{$cart_uuid}";
        $query = Requests::delete($cart_url);
        if ($query->status_code / 100 == 2) {
            //looks like it went through ok
            $success = TRUE;
        } else {
            $success = FALSE;
        }
        if($success) {
            //gone in the cartd, now mark it in ours
            $nowtime = new DateTime('', new DateTimeZone('UTC'));
            $this->db->set('deleted', 'now()')->where('cart_uuid', $cart_uuid);
            $this->db->update('cart');
        }
        return $query->status_code;
    }

    /**
     * Change metadata about the cart, including the name and description.
     *
     * @param string $cart_uuid     SHA256 hash from _generate_cart_uuid
     * @param array  $update_object collection of attributes to change
     *
     * @return array updated cart instance entry from database
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function update_cart_info($cart_uuid, $update_object)
    {
        $acceptable_names = array(
            'name' => 'name',
            'description' => 'description',
            'last_known_state' => 'last_known_state'
        );
        $clean_update = array();
        foreach ($update_object as $name => $new_value) {
            if(array_key_exists($name, $acceptable_names)) {
                $clean_update[$name] = $new_value;
            }
        }
        if(!empty($clean_update)) {
            $this->db->where('cart_uuid', $cart_uuid);
            $this->db->update('cart', $clean_update);
        }
    }

    /**
     *  Takes the submitted JSON string from the request, cleans it up, and
     *  verifies that all the entries that it needs are present. Returns
     *  the object as an array, or FALSE if invalid.
     *
     *  @param string $cart_submission_json Originally submitted cart request JSON
     *
     *  @return array   cleaned up cart submission object
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _clean_cart_submission($cart_submission_json)
    {
        $submission_timestamp = new DateTime();
        $default_cart_name = "Cart for {$this->fullname}";
        $raw_object = json_decode($cart_submission_json, TRUE);
        $description = array_key_exists('description', $raw_object) ? $raw_object['description'] : '';
        $name = array_key_exists('name', $raw_object) ? $raw_object['name'] : $default_cart_name;
        $file_list = array_key_exists('files', $raw_object) ? $raw_object['files'] : FALSE;
        if (!$file_list) {
            //throw an error, as this is an incomplete cart object
        }
        $file_info = $this->_check_and_clean_file_list($file_list);
        if(empty($file_info)) {
            return FALSE;
        }

        $cleaned_object = array(
            'name' => "{$name} ({$submission_timestamp->format('d M Y g:ia')})",
            'files' => $file_info['postable'],
            'submitter' => $this->user_id,
            'submission_timestamp' => $submission_timestamp->getTimestamp(),
        );

        if (!empty($description)) {
            $cleaned_object['description'] = $description;
        }

        $return_object = array(
            'cleaned_submisson_object' => $cleaned_object,
            'file_details' => $file_info['details'],
        );

        return $return_object;
    }

    /**
     * Check the incoming file list and cleanly format it for submission to the cartd.
     *
     * @param array $file_id_list List of file_id's and paths to request
     *
     * @return array Array containing postable results and full file details
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _check_and_clean_file_list($file_id_list)
    {
        $files_url = "{$this->metadata_url_base}/fileinfo/file_details";
        $header_list = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        );
        $query = Requests::post($files_url, $header_list, json_encode($file_id_list));
        if ($query->status_code / 100 != 2) {
            //some kind of error
            return array();
        }
        $results = json_decode($query->body, TRUE);

        $postable_results = array('fileids' => array());

        foreach ($results as $file_entry) {
            $id = $file_entry['file_id'];
            $path = $file_entry['relative_local_path'];

            $postable_results['fileids'][] = array(
                'id' => $id, 'path' => $path,
            );
        }

        $clean_results = array(
            'details' => $results,
            'postable' => $postable_results,
        );

        return $clean_results;
    }

    /**
     * Perform a SHA256 hash on the stringified cart submission object to
     * generate a unique identifier.
     *
     * @param array $cart_submission_object Cleaned and formatted cart submit object
     *
     * @return string SHA256 hash for submit object, formatted as lowercase hex digits
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _generate_cart_uuid($cart_submission_object)
    {
        $clean_cart_string = json_encode($cart_submission_object);

        return hash('sha256', $clean_cart_string);
    }

    /**
     * Add the cart entry and file details to the tracking database.
     *
     * @param string $cart_uuid              SHA256 hash from generate_cart_uuid
     * @param array  $cart_submission_object Cleaned and formatted cart request object
     * @param array  $file_details           Name, path, and size info for the requested files
     *
     * @return bool success value
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _create_cart_entry($cart_uuid, $cart_submission_object, $file_details)
    {
        $this->db->trans_start();
        $submit_time = new DateTime("@{$cart_submission_object['submission_timestamp']}");
        $insert_data = array(
            'cart_uuid' => strtolower($cart_uuid),
            'name' => $cart_submission_object['name'],
            'owner' => $cart_submission_object['submitter'],
            'json_submission' => json_encode($cart_submission_object),
            'created' => $submit_time->format('Y-m-d H:i:s'),
            'updated' => $submit_time->format('Y-m-d H:i:s'),
        );
        if (array_key_exists('description', $cart_submission_object) && !empty($cart_submission_object['description'])) {
            $insert_data['description'] = $cart_submission_object['description'];
        }
        $this->db->insert('cart', $insert_data);
        $file_insert_data = array();
        foreach ($file_details as $file_entry) {
            $file_entry['cart_uuid'] = $cart_uuid;
            $file_insert_data[] = $file_entry;
        }

        $this->db->insert_batch('cart_items', $file_insert_data);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            return FALSE;
            //error thrown during db insert
        } else {
            $this->db->trans_commit();
        }

        return TRUE;
    }

    /**
     * Submit the cleaned cart object to the cart daemon server for processing.
     *
     * @param string $cart_uuid              SHA256 hash from generate_cart_uuid
     * @param array  $cart_submission_object The cleaned and formatted cart request object
     *
     * @return bool TRUE on successful request
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _submit_to_cartd($cart_uuid, $cart_submission_object)
    {
        // $cart_uuid = $cart_submission_object['cart_uuid'];
        $cart_url = "{$this->cart_url_base}/{$cart_uuid}";
        $headers_list = array('Content-Type' => 'application/json');
        $query = Requests::post($cart_url, $headers_list, json_encode($cart_submission_object['files']));

        return $query;
    }
}
