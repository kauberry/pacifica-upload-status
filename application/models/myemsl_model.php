<?php
require_once APPPATH.'libraries/Requests.php';
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Myemsl_model                                                            */
/*                                                                             */
/*             functionality dealing with MyEMSL API Access calls, etc.        */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Myemsl_model extends CI_Model {
  
  function __construct(){
    parent::__construct();
    $this->load->helper('myemsl');
    Requests::register_autoloader();
    $this->myemsl_ini = read_myemsl_config_file('general');
  }
  
  
  function get_user_info_myemsl(){
    $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? "https" : "http";
    $basedir = 'myemsl';
    $url_base = "{$protocol}://localhost";
    $options = array(
      'verify' => false
    );
    $headers = array();
    
    foreach($_COOKIE as $cookie_name => $cookie_value){
      $headers[] = "{$cookie_name}={$cookie_value}";
    }

    $headers = array('Cookie' => implode(';',$headers));
    $session = new Requests_Session($url_base, $headers, array(), $options);
    
    $response = $session->get('/myemsl/userinfo');

    $user_info = json_decode($response->body,true);
    return $user_info;
  }
 
  
  
}
?>