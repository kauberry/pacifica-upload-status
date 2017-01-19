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
 *
 * @link http://github.com/EMSL-MSC/pacifica-upload-status
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
 *
 * @package Pacifica-upload-status
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 *
 * @link http://github.com/EMSL-MSC/pacifica-upload-status
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
        $this->load->library('PHPRequests');
    }

    /**
     *  Generates the an ID for the cart, then makes the appropriate entries
     *  in the cart status database.
     *
     *  @param array $cart_submission_json Cart request JSON, converted to array
     *  @param array $request_info         Apache request object data
     *
     *  @return string  cart_uuid
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function cart_create($cart_submission_json, $request_info)
    {
        $new_submission_info = $this->_clean_cart_submission($cart_submission_json);
        $cart_submission_object = $new_submission_info['cleaned_submisson_object'];
        $cart_uuid = $this->_generate_cart_uuid($cart_submission_object);
        $cart_submit_response = $this->_submit_to_cartd($cart_submission_object);

        if ($cart_submit_response->status_code / 100 == 2) {
            $this->_create_cart_entry(
                $cart_uuid,
                $new_submission_info['cleaned_submission_object'],
                $new_submission_info['file_details']
            );
        } else {
            //return error about not being able to create the cart entry properly
        }
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
    public function cart_status($cart_uuid_list)
    {
        $status_lookup = array(
            'waiting' => 'Locating requested files...',
            'staging' => 'Retrieving files from archival storage...',
            'bundling' => 'Preparing files for transfer...',
            'ready' => 'Cart is ready to be downloaded.',
            'error' => 'An error occurred during the cart generation process.',
        );
        $status_return = array();
        foreach ($cart_uuid_list as $cart_uuid) {
            $cart_url = "{$this->cart_url_base}/{$cart_uuid}";
            $response = Requests::head($cart_url);
            if ($response->status_code / 100 == 2 && $status != 'error') {
                //looks like it went through ok
                $success = TRUE;
            } else {
                $success = FALSE;
            }
            $status = $response->headers['X-Pacifica-Status'];
            $message = $response->headers['X-Pacifica-Message'];
            $status_return[$cart_uuid] = array(
                'status' => $status,
                'message' => $message,
                'success' => $success,
                'response_code' => $response->status_code,
            );
        }
        $this->output->set_header('X-Pacifica-Status: {$status}');
        $this->output->set_header('X-Pacifica-Message: {$message}');

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

        return $success;
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
        );
        $update_calls = array();
        foreach ($update_object as $name => $new_value) {
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
        $default_cart_name = "Cart for {$this->fullname} / Created {$submission_timestamp->format('d M Y g:ia')}";
        $raw_object = json_decode($cart_submission_json, TRUE);
        $description = array_key_exists('description', $raw_object) ? $raw_object['description'] : '';
        $name = array_key_exists('name', $raw_object) ? $raw_object['name'] : $default_cart_name;
        $file_list = array_key_exists('files', $raw_object) ? $raw_object['files'] : FALSE;
        if (!$file_list) {
            //throw an error, as this is an incomplete cart object
        }
        $file_info = $this->_generate_cart_uuid($file_list);

        $cleaned_object = array(
            'name' => $name,
            'files' => $file_info['postable_results'],
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
        $data_list = array_keys($file_id_list);
        $query = Requests::post($files_url, $header_list, $data_list);
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

        $insert_data = array(
            'cart_uuid' => strtolower($cart_uuid),
            'name' => $cart_submission_object['name'],
            'owner' => $cart_submission_object['submitter'],
            'json_submission' => json_encode($cart_submission_object),
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

        $this->db->insert('cart_items', $file_insert_data);
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
     * @param array $cart_submission_object The cleaned and formatted cart request object
     *
     * @return bool TRUE on successful request
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _submit_to_cartd($cart_submission_object)
    {
        $cart_uuid = $cart_submission_object['cart_uuid'];
        $cart_url = "{$this->cart_url_base}/{$cart_uuid}";
        $headers_list = array('Content-Type' => 'application/json');
        $query = Requests::post($cart_url, $headers_list, $cart_submission_object['files']);
        if ($query->status_code / 100 == 2) {
            //looks like it went through ok
            $success = TRUE;
        } else {
            $success = FALSE;
        }

        return $success;
    }
}
