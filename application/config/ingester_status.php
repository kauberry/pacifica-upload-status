<?php
/**
 * CI Ingester Status Message Translations
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Default_Pacifica
 * @author   Ken Auberry <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$config['ingest_status_messages'] = array(
    'open_tar' => array(
        'percent_complete' => 20,
        'success_message' => 'Checking uploaded file bundle consistency',
        'failure_message' => 'Unable to read uploaded file bundle'
    ),
    'bad_tarfile' => array(
        'percent_complete' => 20,
        'success_message' => 'Checking uploaded file bundle consistency',
        'failure_message' => 'Unable to read uploaded file bundle'
    ),
    'load_metadata' => array(
        'percent_complete' => 40,
        'success_message' => 'Loading and parsing file metadata',
        'failure_message' => 'Unable to extract file metadata'
    ),
    'policy_validation' => array(
        'percent_complete' => 60,
        'success_message' => 'Checking file metadata for consistency and validity',
        'failure_message' => 'Uploaded file metadata is invalid. This may be due to an invalid combination of user/proposal/instrument'
    ),
    'ingest_files' => array(
        'percent_complete' => 80,
        'success_message' => 'Extracting and verifying uploaded files',
        'failure_message' => 'Unable to extract files from uploaded bundle'
    ),
    'ingest_metadata' => array(
        'percent_complete' => 100,
        'success_message' => 'Ingest Complete',
        'failure_message' => 'Unable to store file metadata'
    )
);
