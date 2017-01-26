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
 *
 *  This file contains a number of common functions related to
 *  file info and handling.
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
if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
 *  Takes a given array object and formats it as
 *  standard JSON with appropriate status headers
 *  and X-JSON messages
 *
 *  @param array   $response            the array to be transmitted
 *  @param string  $statusMessage       optional status message
 *  @param boolean $operationSuccessful Was the calling
 *                                      operation successful?
 *
 *  @return void (sends directly to browser)
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function transmit_array_with_json_header($response, $statusMessage = '', $operationSuccessful = TRUE)
{
    header("Content-type: application/json");
    $headerArray = array();
    $headerArray['status'] = $operationSuccessful ? "ok" : "fail";
    $headerArray['message'] = !empty($statusMessage) ? $statusMessage : "";
    header("X-JSON: (".json_encode($headerArray).")");
    if(!$operationSuccessful) {
        $this->output->set_status_header(404);
    }
    $response = !is_array($response) ? array('results' => $response) : $response;

    if(is_array($response) && sizeof($response) > 0) {
        print(json_encode($response));
    }else{
        print("0");
    }
}

/**
 *  Similar to transmit_array_with_json_header above,
 *  but with different headers returned
 *
 *  @param array $response_array array to be processed
 *
 *  @return void (sends directly to browser)
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function send_json_array($response_array)
{
    $CI =& get_instance();

    $CI->output->set_content_type("text/json");
    $array_size = sizeof($response_array);
    $status_header = $array_size > 0 ? 200 : 404;
    $CI->output->set_status_header($status_header);
    $CI->output->set_header("Operation-message:{$array_size} record".$array_size != 1 ? 's' : ''." returned");

    $CI->output->set_output(json_encode($response_array));
    $CI->output->set_header("Operation-status:ok");

}


/**
 *  Formats an array object into the proper format
 *  to be parsed by the Select2 Jquery library for
 *  generating dropdown menu objects
 *
 *  @param array $response array to be formatted
 *
 *  @return void (sends directly to browser)
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_array_for_select2($response)
{
    header("Content-type: text/json");

    $results = array();

    foreach($response['items'] as $id => $text){
        $results[] = array('id' => $id, 'text' => $text);
    }

    $ret_object = array(
    'total_count' => sizeof($results),
    'incomplete_results' => FALSE,
    'items' => $results
    );

    print(json_encode($ret_object));

}

/**
 *  Takes a string that is too long and chops
 *  some content out of the middle to provide
 *  better display formatting
 *
 *  @param string  $string string to be shortened
 *  @param integer $limit  maximum string length allowed
 *  @param string  $break  preferred character at which to split the original string
 *                             to split the original string
 *  @param string  $pad    string to use for replacing the deleted text
 *                             deleted text
 *
 *  @return string shortened string
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function truncate_text($string, $limit, $break=" ", $pad="...")
{
    $textLength = strlen($string);
    $result = $string;
    if($textLength > $limit) {
        $result = substr_replace(
            $string,
            '...',
            $limit/2,
            $textLength-$limit
        );
    }
    return $result;
}

?>
