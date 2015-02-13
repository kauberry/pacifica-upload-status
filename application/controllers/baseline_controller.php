<?php
class Baseline_controller extends CI_Controller {
  
  function __construct() {
    date_default_timezone_set('America/Los_Angeles');
    parent::__construct();
    $this->load->helper(array('user','url','html','myemsl'));
    $this->user_id = strtolower(get_user());
    $this->site_id = $this->config->item('site_id');
    
    // echo $this->user_id;
    // $this->user_id = "d3j427";
    $this->page_address = implode('/',$this->uri->rsegments);
    $this->load->model('User_operations_model', 'user_model');
    $this->load->model('Navigation_info_model', 'nav_info_model');
    //$this->site_info = $this->nav_info_model->get_site_identifier($this->site_id);
    // $this->user_model->refresh_user_info($this->user_id,$this->site_id);
    
    
        
    $user_info = get_user_details_myemsl($this->user_id);
    // $user_group_list = $user_info['group_list'];
    // $this->admin_access_level = $this->user_model->get_user_permissions_level($user_group_list,$this->site_id);
    // echo "admin access level {$this->admin_access_level}";
    if($this->user_id == 'd3k857'){
      // $this->admin_access_level = 100;
    }
//    $this->admin_access_level = 400;
//    echo $this->admin_access_level;
    $this->username = $user_info['first_name'] != null ? $user_info['first_name'] : "Anonymous Stranger";
    $this->fullname = "{$this->username} {$user_info['last_name']}";
    $this->site_color = $this->config->item('site_color');
    
    
    $user_info['full_name'] = $this->fullname;
    $user_info['network_id'] = !empty($user_info['network_id']) ? $user_info['network_id'] : "unknown";
    // $this->lookupname = isset($user_info['middle_initial']) ? 
      // $user_info['last_name'].", ".$user_info['first_name']." ".$user_info['middle_initial']."." : 
      // $user_info['last_name'].", ".$user_info['first_name'];
    $current_path_info = isset($_SERVER['PATH_INFO']) ? ltrim($_SERVER['PATH_INFO'],'/') : "./";
    //$this->nav_info = $this->nav_info_model->generate_navigation_entries($current_path_info);
    // $perm_description = $this->user_model->get_permission_level_info($this->admin_access_level);
    $this->nav_info['current_page_info']['logged_in_user'] = "{$this->fullname}";
    
    
    $this->page_data = array();
    $this->page_data['navData'] = $this->nav_info;
    $this->page_data['infoData'] = array('current_credentials' => $this->user_id,'full_name' => $this->fullname);
    $this->page_data['username'] = $this->username;
    $this->page_data['fullname'] = $this->fullname;
    // $this->page_data['title'] = $this->nav_info['current_page_info']['name'];
    // $this->page_data['page_header'] = $this->page_data['title'];
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;
    $this->controller_name = $this->uri->rsegment(1);
    // $data_array = array(
      // 'user_id' => $this->user_id
    // );
//    var_dump($user_info);
    //$this->session->set_userdata($data_array);    
  }
  
  
  
}
?>