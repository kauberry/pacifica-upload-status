<?php

class Baseline_controller extends CI_Controller
{
    public function __construct()
    {
        date_default_timezone_set('America/Los_Angeles');
        parent::__construct();
        $this->load->helper(array('user', 'url', 'html', 'myemsl', 'file_info'));
        $this->output->enable_profiler(false);
        $this->benchmark->mark('get_user_start');
        $this->user_id = get_user();
        $this->benchmark->mark('get_user_end');
        $this->application_version = $this->config->item('application_version');

        $this->page_address = implode('/', $this->uri->rsegments);

        $this->benchmark->mark('get_user_details_start');
        $user_info = get_user_details_myemsl($this->user_id);
        $this->username = $user_info['first_name'] != null ? $user_info['first_name'] : 'Anonymous Stranger';
        $this->fullname = "{$this->username} {$user_info['last_name']}";
        $this->is_emsl_staff = $user_info['emsl_employee'] == 'Y' ? true : false;
        $this->site_color = $this->config->item('site_color');

        $this->email = $user_info['email_address'];
        $user_info['full_name'] = $this->fullname;
        $user_info['network_id'] = !empty($user_info['network_id']) ? $user_info['network_id'] : 'unknown';
        $current_path_info = isset($_SERVER['PATH_INFO']) ? ltrim($_SERVER['PATH_INFO'], '/') : './';
        $this->nav_info['current_page_info']['logged_in_user'] = "{$this->fullname}";
        $this->benchmark->mark('get_user_details_end');

        $this->page_data = array();
        $this->page_data['navData'] = $this->nav_info;
        $this->page_data['infoData'] = array('current_credentials' => $this->user_id, 'full_name' => $this->fullname);
        $this->page_data['username'] = $this->username;
        $this->page_data['fullname'] = $this->fullname;
        $this->page_data['load_prototype'] = false;
        $this->page_data['load_jquery'] = true;
        $this->controller_name = $this->uri->rsegment(1);
    }
}
