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
class Baseline_controller extends CI_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        date_default_timezone_set('America/Los_Angeles');
        parent::__construct();
        $this->load->helper(array('user', 'url', 'html', 'myemsl', 'file_info'));
        $this->output->enable_profiler(FALSE);
        $this->benchmark->mark('get_user_start');
        $this->user_id = get_user();
        $this->benchmark->mark('get_user_end');
        $this->application_version = $this->config->item('application_version');

        $this->page_address = implode('/', $this->uri->rsegments);

        $this->benchmark->mark('get_user_details_start');
        $user_info = get_user_details_myemsl($this->user_id);
        if($user_info['first_name'] != NULL) {
            $this->username = $user_info['first_name'];
        } else {
            'Anonymous Stranger';
        }
        $this->fullname = "{$this->username} {$user_info['last_name']}";
        $this->is_emsl_staff = $user_info['emsl_employee'] == 'Y' ? TRUE : FALSE;
        $this->site_color = $this->config->item('site_color');

        $this->email = $user_info['email_address'];
        $user_info['full_name'] = $this->fullname;
        if(!empty($user_info['network_id'])) {
            $user_info['network_id'] = $user_info['network_id'];
        } else {
            $user_info['network_id'] = 'unknown';
        }
        if(isset($_SERVER['PATH_INFO'])) {
            $current_path_info = ltrim($_SERVER['PATH_INFO'], '/');
        }else {
            $current_path_info = './';
        }
        $this->nav_info['current_page_info']['logged_in_user'] = "{$this->fullname}";
        $this->benchmark->mark('get_user_details_end');

        $this->page_data = array();
        $this->page_data['navData'] = $this->nav_info;
        $this->page_data['infoData'] = array(
            'current_credentials' => $this->user_id,
            'full_name' => $this->fullname
        );
        $this->page_data['username'] = $this->username;
        $this->page_data['fullname'] = $this->fullname;
        $this->page_data['load_prototype'] = FALSE;
        $this->page_data['load_jquery'] = TRUE;
        $this->controller_name = $this->uri->rsegment(1);
    }
}
