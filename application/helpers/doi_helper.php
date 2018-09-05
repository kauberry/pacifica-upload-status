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
 * Generate the DOI infix string for our different types of products
 * NB: local_identifier is the primary key ID of the item in question.
 * Think transaction ID for data, instrument ID for instrument, etc.
 *
 * @param string   $local_identifier    transaction_id for this infix
 * @param datetime $collection_date_obj when was the data collected?
 *
 * @return string returns the formatted doi infix string
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function format_doi_infix($local_identifier, $collection_date_obj = false, $infix_type = 'data', $infix_version_ref = 1)
{
    $valid_infix_types = [
        'project' => 'proj',
        'instrument' => 'inst',
        'data' => 'data',
        'software' => 'sftwr',
        'sample' => 'smpl'
    ];

    if (is_string($collection_date_obj) || !$collection_date_obj) {
        $collection_date_obj = strtotime($collection_date_obj) ? new DateTime($collection_date_obj) : new DateTime();
    }
    // make sure our infix type is valid and parseable
    if (!array_key_exists(strtolower($infix_type), $valid_infix_types)
        && !in_array(strtolower($infix_type), $valid_infix_types)
    ) {
        return "";
    }

    // if they've requested an infix type by full name, convert
    // it to the abbreviated style
    if (array_key_exists($infix_type, $valid_infix_types)) {
        $infix_type = $valid_infix_types[$infix_type];
    }

    $doi_components = [];
    // $doi_components[] = 'v' + str_pad($infix_version_ref, 2, '0');

    $doi_components[] = $infix_type;

    switch ($infix_type) {
        case 'proj':
            // looks like /<doi_prefix>/vXX.proj.2018.12345/<osti_id>
            $doi_componenents[] = $collection_date->format('Y');
            break;
        case 'inst':
            // looks like /<doi_prefix>/vXX.inst.12345/<osti_id>
            // don't really need to add anything, just leaving it for completeness
            break;
        case 'data':
            // looks like /<doi_prefix>/vXX.data.2018-05.12345/<osti_id>
            //falls through
        case 'sftwr':
            // looks like /<doi_prefix>/vXX.sftwr.2018-05.a26de41/<osti_id>
            // falls through
        case 'smpl':
            // looks like /<doi_prefix>/vXX.smpl.2018-05.12345/<osti_id>
            $doi_components[] = $collection_date_obj->format('Y-m');
            break;
        default:
    }
    $doi_components[] = $local_identifier;
    $doi_string = implode($doi_components, ".");
    return $doi_string;
}
