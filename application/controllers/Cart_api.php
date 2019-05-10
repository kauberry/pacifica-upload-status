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
 * Cart is a CI Controller class that extends Baseline_controller_api
 *
 * The *Cart* class interacts with the MyEMSL Cart web API to
 * allow download of archived data, as well as generating proper
 * cart_token entities to allow for multi-file download specifications.
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Cart_api extends Baseline_api_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Cart_api_model', 'cart');
        $this->load->helper(array('url', 'network', 'item', 'myemsl_api'));
        $this->eus_cookie_name = $this->config->item('cookie_name');
        $this->eus_login_redirect_url = $this->config->item('cookie_redirect_url');
        $this->eus_cookie_encryption_key = $this->config->item('cookie_encryption_key');
        $this->enable_cookie_redirect = $this->config->item('enable_cookie_redirect');
    }

    /**
     * Retrieve the list of active carts owned by this user
     *
     * @return void sends out JSON text to browser
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function listing($cart_owner_identifier)
    {
        $accept = $this->input->get_request_header('Accept');
        $cart_list = $this->cart->cart_status($cart_owner_identifier);
        if (stristr(strtolower($accept), 'json')) {
            //looks like a json request
            transmit_array_with_json_header($cart_list);
        } else {
            if (empty($cart_list)) {
                print('');
            } else {
                //let's assume that they want html
                $this->load->view('cart_status_insert_view.html', $cart_list);
            }
        }
    }

    /**
     *  Create a new download cart
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function create($cart_owner_identifier)
    {
        $req_method = array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : "GET";
        if ($req_method != "POST") {
            //return info on how to use this function
            echo "That's not how you use this function!!!";
            exit();
        }
        // Check to make sure the auth cookie is set, and make sure that the encoded value is opcache_invalidate
        // How are we making use of this information? Does it go somewhere in the database?
        if($this->config->item('enable_require_credentials_for_cart_download')) {
            $user_block = $this->check_download_authorization(false);
            $user_id = $user_block['eus_id'];
            if ($user_id) {
                $user_info = get_user_details_simple($user_id);
            } else {
                $this->output->set_status_header(302, "Unknown EUS User");
                print("");
                return;
            }
        }

        $submit_block = json_decode($this->input->raw_input_stream, true);
        if (empty($submit_block)) {
            //bad json-block or empty post body
            echo "Hey! There's no real data here!";
        }
        // var_dump($this->input->request_headers());
        $cart_uuid_info = $this->cart->cart_create($cart_owner_identifier, $this->input->raw_input_stream);
        transmit_array_with_json_header($cart_uuid_info);
    }

    /**
     * Checks for pre-existing identity cookie token from a recent EUS login
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function check_download_authorization($show_output = true)
    {
        $retval = [
            "redirect_url" => $this->eus_login_redirect_url,
            "eus_id" => null
        ];
        // $this->user_id = false;
        if (!$this->config->item('enable_require_credentials_for_cart_download')) {
            $retval['eus_id'] = 0;
        } else if (!$this->config->item('enable_cookie_redirect')) {
            $retval['eus_id'] = $this->user_id;
            $retval = array_merge($retval, $this->user_info);
        } else if (!$this->config->item('enable_require_credentials_for_cart_download')) {
            $retval['eus_id'] = 0;
        } else {
            $eus_user_info = get_user_from_cookie();
            if($eus_user_info) {
                $this->user_info = $eus_user_info;
                $retval = array_merge($retval, $eus_user_info);
            }
        }
        if ($show_output) {
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode($retval));
        }
        return $retval;
    }

    /**
     * Deletes existing cart instances
     *
     * @param string $cart_uuid SHA256 hash identifier for the cart
     *
     * @return void
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function delete($cart_owner_identifier, $cart_uuid)
    {
        $req_method = array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : "GET";
        if ($req_method != "DELETE") {
            echo "That's not how you use this function!!!";
            exit();
        }
        $status_message = $this->cart->cart_delete($cart_owner_identifier, $cart_uuid);
        $success = false;
        if ($status_message / 100 == 2) {
            //looks like it went through ok
            $success = true;
        }
        $success_message = $success ? "" : " not";
        $ret_message = array(
            'message' => "The cart was{$success_message} successfully deleted "
        );
        transmit_array_with_json_header($ret_message, "", $success);
    }
}
