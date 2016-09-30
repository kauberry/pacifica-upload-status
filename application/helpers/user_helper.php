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
 *  Properly formats the user returned in the ['REMOTE_USER']
 *  variable from Apache
 *
 *  @return array
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user()
{
    $user = '(unknown)';
    if(isset($_SERVER["REMOTE_USER"])) {
        $user = str_replace('@PNL.GOV', '', $_SERVER["REMOTE_USER"]);
    }
    return strtolower($user);
}

/**
 *  Properly formats the user returned in the ['REMOTE_USER']
 *  variable from Apache
 *
 *  @param integer $user_id The user_id to format
 *
 *  @return array
 *
 *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details($user_id)
{
    $user_info = array(
    'user_id' => strtolower($_SERVER['REMOTE_USER']),
    'first_name' => $_SERVER['LDAP_GIVENNAME'],
    'middle_initial' => $_SERVER['LDAP_INITIALS'],
    'last_name' => $_SERVER['LDAP_SN'],
    'email' => strtolower($_SERVER['LDAP_MAIL'])
    );
    return $user_info;
}
