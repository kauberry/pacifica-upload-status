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
 * Baseline controller class
 *
 * @category Class
 * @package  Baseline
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */

ini_set("default_socket_timeout", 30);

class Baseline_api_controller extends CI_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        //get user info
        date_default_timezone_set($this->config->item('local_timezone'));
        $this->load->model('System_setup_model', 'setup');
        $this->load->helper(
            [
            'url', 'html', 'myemsl_api', 'file_info',
            'user', 'cookie', 'network', 'doi'
            ]
        );
        $this->output->enable_profiler(false);
        $this->nexus_backend_url = $this->config->item('nexus_backend_url');
        $this->metadata_url_base = str_replace('tcp:', 'http:', getenv('METADATA_PORT'));
        $this->policy_url_base = str_replace('tcp:', 'http:', getenv('POLICY_PORT'));
        $this->ingester_url_base = str_replace('tcp:', 'http:', getenv('INGESTER_PORT') ?: 'http://127.0.0.1:8066');
        $this->file_url_base = $this->config->item('external_file_url');
        $this->cart_url_base = $this->config->item('external_cart_url');
        $this->drhub_url_base = $this->config->item('drhub_url_base');
        $this->user_id = get_user();
        $this->ingester_messages = $this->config->item('ingest_status_messages');
        $this->git_hash = get_current_git_hash();
        $this->application_version = $this->config->item('application_version');
        $this->page_address = implode('/', $this->uri->rsegments);

        $this->benchmark->mark('get_user_details_start');
        $user_info = get_user_details($this->user_id);
        $this->username = $user_info['first_name'] ?: 'Anonymous Stranger';
        $this->is_emsl_staff = $user_info['emsl_employee'] == 'Y' ? true : false;
        $this->email = $user_info['email_address'];
        $this->fullname = "{$this->username} {$user_info['last_name']}";
        $user_info['full_name'] = $this->fullname;
        $user_info['network_id'] = !empty($user_info['network_id']) ? $user_info['network_id'] : '';
        $this->user_info = $user_info;

        if (isset($_SERVER['PATH_INFO'])) {
            $current_path_info = ltrim($_SERVER['PATH_INFO'], '/');
        } else {
            $current_path_info = './';
        }

        $this->nav_info['current_page_info']['logged_in_user'] = "{$this->fullname}";
        $this->nav_info['current_page_info']['logged_in_user_id'] = $user_info['network_id'] ?: "";
        $this->benchmark->mark('get_user_details_end');

        $this->page_data = array();
        $this->page_data['navData'] = $this->nav_info;
        $this->page_data['infoData'] = array(
            'current_credentials' => $this->user_id,
            'full_name' => $this->fullname
        );
        $this->page_data['username'] = $this->username;
        $this->page_data['fullname'] = $this->fullname;
        $this->page_data['load_prototype'] = false;
        $this->page_data['load_jquery'] = true;
        $this->controller_name = $this->uri->rsegment(1);
    }
}
