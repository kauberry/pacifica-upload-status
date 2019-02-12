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

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Entry function to encrypt string text
 *
 * @param string $src source text to encipher
 *
 * @return string encrypted text
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function eus_encrypt($src)
{
    $key = _getkey();
    return base64_encode(openssl_encrypt($src, "aes-128-ecb", OPENSSL_RAW_DATA));
}

/**
 * Entry function to decrypt string text
 *
 * @param string $src encrypted text to decipher
 *
 * @return string decrypted text
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function eus_decrypt($src)
{
    $key = _getkey();
    return openssl_decrypt(base64_decode($src), "aes-128-ecb", $key, OPENSSL_RAW_DATA);
}

/**
 * Get the shared key string from configuration
 *
 * @return string key value retrieved from configuration
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function _getkey()
{
    $CI =& get_instance();
    return $CI->config->item('cookie_encryption_key');
}
