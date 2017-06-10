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
 *  This file contains a number of common functions for retrieving
 *
 * PHP version 5.5
 *
 * @package Pacifica-upload-status
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */

if(!defined('BASEPATH')) { exit('No direct script access allowed');
}

/**
 *  Directly retrieves user info from the MyEMSL EUS
 *  database clone
 *
 *  @param integer $eus_id user id of the person in question
 *
 *  @return array
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details($eus_id)
{
    return get_user_details_base($eus_id, '');
}

/**
 *  Directly retrieves simplified user info from the MyEMSL EUS
 *  database clone
 *
 *  @param integer $eus_id user id of the person in question
 *
 *  @return array
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details_simple($eus_id)
{
    return get_user_details_base($eus_id, 'simple');
}

/**
 *  Directly retrieves simplified user info from the MyEMSL EUS
 *  database clone
 *
 *  @param integer $eus_id user id of the person in question
 *  @param string  $option 'simple' if short version, anything else if not
 *  
 *  @return array
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details_base($eus_id, $option)
{
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    // $md_url = $CI->config->item('metadata_url');
    $md_url = $CI->metadata_url_base;
    $query_url = "{$md_url}/userinfo/by_id/{$eus_id}/{$option}";
    $query = Requests::get($query_url, array('Accept' => 'application/json'));
    if($query->status_code == 200) {
        $results_body = $query->body;
    }else{
        $results_body = array();
    }


    return json_decode($results_body, TRUE);
}


/**
 *  Read and parse the '*general.ini*' file to retrieve things
 *  like the database connection strings, etc.
 *
 *  @param string $file_specifier the name of the file to be read
 *                                from the default folder location
 *
 *  @return array an array of ini file items
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function read_myemsl_config_file($file_specifier = 'general')
{
    $CI =& get_instance();
    $ini_path = $CI->config->item('application_config_file_path');
    $ini_items = parse_ini_file("{$ini_path}{$file_specifier}.ini", TRUE);
    return $ini_items;
}

/**
 *  Construct an appropriate token for retrieving the items
 *  from a given cart object. This was needed to overcome the
 *  'single call for each cart item' limitation
 *
 *  @param array   $item_list     the item identifiers for the cart items to be processed cart items to be processed
 *                                cart items to be processed
 *  @param integer $eus_person_id cart owner user id
 *
 *  @return string Base64 encoded token to use for the submission
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function generate_cart_token($item_list,$eus_person_id)
{
    $uuid = "huYNwptYEeGzDAAmucepzw";
    $duration = 3600;

    //grab private key file
    $fp = fopen('/etc/myemsl/keys/item/local.key', 'r');
    $priv_key = fread($fp, 8192);
    fclose($fp);
    $pkey_id = openssl_get_privatekey($priv_key);

    $s_time = new DateTime();
    $time = $s_time->format(DATE_ATOM);
    // $time = '2015-05-08T16:07:06-07:00';

    $token_object = array(
        'd' => $duration, 'i' => $item_list, 'p' => intval($eus_person_id),
        's' => $time, 'u' => $uuid
    );

    $token_json = json_encode($token_object);

    $trimmed_token = trim($token_json, '{}');

    openssl_sign($trimmed_token, $signature, $pkey_id, 'sha256');
    openssl_free_key($pkey_id);

    $cart_token = strlen($trimmed_token).$trimmed_token.$signature;

    $cart_token_b64 = base64_encode($cart_token);

    return $cart_token_b64;

}

/**
 *  Generates an XML block that conforms to the
 *  same format as the status XML returned by the
 *  previous MyEMSL backend
 *
 *  @param [type] $data     [description]
 *  @param [type] $xml_data [description]
 *
 *  @return [type]   [description]
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function array_to_xml($data, &$xml_data)
{
    foreach( $data as $key => $value ) {
        if(is_array($value)) {
            if(is_numeric($key)) {
                $key = 'item'.$key; //dealing with <0/>..<n/> issues
            }
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        }else{
            $xml_data->addChild("$key", htmlspecialchars("$value"));
        }
    }
}


?>
