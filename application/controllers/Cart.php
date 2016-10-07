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
 * Cart is a CI Controller class that extends Baseline_controller
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
class Cart extends Baseline_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Cart_model', 'cart');
        $this->load->helper(array('url', 'network'));
    }

    /**
     * Generates an appropriately formatted token to convince
     * the cart engine to send a bunch of item ID's at once,
     * rather than specifying each item as a separate (slow)
     * web request roundtrip. Single item ID's can be specified
     * in the GET params, and multiple ID's can be POSTed as
     * a block of JSON.
     *
     * @param int $item_id single itemid to get a cart for.
     *
     * @return void
     */
    public function get_cart_token($item_id = FALSE)
    {
        $HTTP_RAW_POST_DATA = file_get_contents('php://input');
        $values = json_decode($HTTP_RAW_POST_DATA, TRUE);
        if (empty($values) && $item_id) {
            $item_list = array($item_id);
        } else {
            $item_list = $values['items'];
        }
        echo generate_cart_token($item_list, $this->user_id);
    }

    /**
     * Retrieve a listing of the user's currently active
     * cart entities, formatted as an HTML table view
     *
     * @param string $optional_message optional message to send to user.
     *
     * @return void
     */
    public function listing($optional_message = '')
    {
        $cart_list = $this->cart->get_active_carts($this->user_id);
        $cart_list['optional_message'] = $optional_message;
        $this->load->view('cart_list_insert.html', array('carts' => $cart_list));
    }

    /**
     * Delete a cart based on ID
     *
     * @param int $cart_id cart ID to delete
     *
     * @return void
     */
    public function delete($cart_id)
    {
        $success_info = $this->cart->delete_dead_cart($cart_id);
        $this->listing($success_info['message']);
    }


    /**
     * Test cart listing method
     *
     * @return void
     */
    public function test_get_cart_list()
    {
        echo '<pre>';
        var_dump($this->cart->get_active_carts($this->user_id, FALSE));
        echo '</pre>';
    }
}
