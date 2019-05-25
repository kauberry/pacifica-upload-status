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

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 *  Directly retrieves simplified user info from the MyEMSL EUS
 *  database clone
 *
 * @param integer $eus_id user id of the person in question
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details_simple($eus_id)
{
    return get_details('user', $eus_id, 'simple');
}

/**
 *  Directly retrieves user info from the MyEMSL EUS
 *  database clone
 *
 * @param integer $eus_id user id of the person in question
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details($eus_id)
{
    $results = [];
    $results = get_details('user', $eus_id);
    if (empty($results)) {
        if (get_user_from_cookie()) {
            $results = get_user_from_cookie();
            $results['emsl_employee'] = false;
            $results['projects'] = [];
            $results['email_address'] = $results['email'];
            $results['person_id'] = $results['eus_id'];
            $results['network_id'] = $results['eus_id'];
        } else {
            $results = [
                'first_name' => 'Anonymous Stranger',
                'last_name' => '',
                'emsl_employee' => false,
                'projects' => [],
                'email_address' => '',
                'person_id' => false
            ];
        }
    }
    return $results;
}

/**
 *  Directly retrieves instrument info from md server
 *
 * @param integer $instrument_id id of the instrument in question
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_instrument_details($instrument_id)
{
    return get_details('instrument', $instrument_id);
}

/**
 *  Directly retrieves project info from md server
 *
 * @param integer $project_id project id of the item in question
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_project_details($project_id)
{
    return get_details('project', $project_id);
}

/**
 *  Worker function for talking to md server
 *
 * @param string $object_type type of object to query
 * @param string $object_id   id of object to query
 * @param string $option      switch to pass in to md request
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_details($object_type, $object_id, $option = false)
{
    $object_map = array(
        'instrument' => array('url' => 'instrumentinfo/by_instrument_id'),
        'project' => array('url' => 'projectinfo/by_project_id'),
        'user' => array('url' => 'userinfo/by_id')
    );
    $results_body = "{}";
    if (empty($object_id)) {
        return json_decode($results_body, true);
    }
    $url = $object_map[$object_type]['url'];
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    $md_url = $CI->metadata_url_base;
    $url_object = array(
        $md_url, $url, $object_id
    );
    if ($option) {
        $url_object[] = $option;
    }
    $query_url = implode('/', $url_object);
    $query = Requests::get($query_url, array('Accept' => 'application/json'));
    if ($query->status_code == 200) {
        $results_body = $query->body;
    }
    return json_decode($results_body, true);
}

/**
 * [get_project_abstract description]
 *
 * @param string $project_id [description]
 *
 * @return string [description]
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_project_abstract($project_id)
{
    $url = "projects?_id={$project_id}";
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    $md_url = $CI->metadata_url_base;
    $query_url = "{$md_url}/{$url}";
    $query = Requests::get($query_url, array('Accept' => 'application/json'));
    $results_body = $query->body;
    $results = json_decode($results_body, true);
    $result = array_pop($results);
    $ret_array = array(
        'title' => $result['title'],
        'abstract' => $result['abstract']
    );

    return $ret_array;
}

/**
 *  Read and parse the '*general.ini*' file to retrieve things
 *  like the database connection strings, etc.
 *
 * @param string $file_specifier the name of the file to be read
 *                               from the default folder location
 *
 * @return array an array of ini file items
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function read_myemsl_config_file($file_specifier = 'general')
{
    $CI =& get_instance();
    $ini_path = $CI->config->item('application_config_file_path');
    $ini_items = parse_ini_file("{$ini_path}{$file_specifier}.ini", true);
    return $ini_items;
}

/**
 *  Construct an appropriate token for retrieving the items
 *  from a given cart object. This was needed to overcome the
 *  'single call for each cart item' limitation
 *
 * @param array   $item_list     the item identifiers for the cart items to be processed cart items to be processed
 *                               cart items to be processed
 * @param integer $eus_person_id cart owner user id
 *
 * @return string Base64 encoded token to use for the submission
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function generate_cart_token($item_list, $eus_person_id)
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
 * @param [type] $data     [description]
 * @param [type] $xml_data [description]
 *
 * @return [type]   [description]
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function array_to_xml($data, &$xml_data)
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                $key = 'item'.$key; //dealing with <0/>..<n/> issues
            }
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

function get_selection_defaults($incoming)
{
    $project_id = $incoming['project_id'] ?: get_cookie('last_project_selector');
    $instrument_id = $incoming['instrument_id'] ?: get_cookie('last_instrument_selector');
    $starting_date = $incoming['starting_date'] ?: get_cookie('last_starting_date_selector');
    $ending_date = $incoming['ending_date'] ?: get_cookie('last_ending_date_selector');
    $project_id = $project_id != 'null' ? $project_id : 0;
    $instrument_id = $instrument_id != 'null' ? $instrument_id : 0;

    if (!$starting_date || !$ending_date) {
        $today = new DateTime();
        if (!$ending_date) {
            $ending_date = $today->format('Y-m-d');
        }
        if (!$starting_date) {
            $today->modify('-30 days');
            $starting_date = $today->format('Y-m-d');
        }
    }

    $outgoing = [
        'project_id' => $project_id,
        'instrument_id' => $instrument_id,
        'starting_date' => $starting_date,
        'ending_date' => $ending_date
    ];

    return $outgoing;
}
