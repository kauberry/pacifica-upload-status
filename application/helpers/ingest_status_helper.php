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
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Provides a more user friendly version of the status messages from ingest
 *
 * @param string $ingest_status The task name from the ingest system
 *
 * @return array object with new status messages for that task type
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function translate_ingest_status_message($ingest_status)
{
    $status_messages = $this->config->item('ingest_status_messages');
    $clean_status = strtolower(str_replace(' ', '_', $ingest_status));
    return $status_messages[$clean_status];
}
