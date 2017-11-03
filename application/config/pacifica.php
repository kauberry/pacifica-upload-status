<?php
/**
 * CI Default Pacifica Config
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

$config['local_timezone'] = 'America/Los_Angeles';

$cart_port = getenv('CART_PORT');
$cart_dl_port = getenv('CART_DOWNLOAD_PORT');

$files_dl_port = getenv('FILE_DOWNLOAD_PORT');

$config['internal_cart_url'] = !empty($cart_port) ?
    str_replace('tcp://', 'http://', $cart_port) :
    'http://cart:8081';

$config['external_cart_url'] = !empty($cart_dl_port) ?
    str_replace('tcp://', 'https://', $cart_dl_port) :
    'http://cart.emsl.pnl.gov';

$config['external_file_url'] = !empty($files_dl_port) ?
    str_replace('tcp://', 'https://', $files_dl_port) :
    'http://files.emsl.pnl.gov';

$config['main_overview_template'] = "external_view.html";

$config['template'] = 'emsl';
$config['site_color'] = 'orange';
// $config['theme_name'] = 'external';
// $config['site_identifier'] = 'EMSL User Portal Data Retrieval';

// $config['theme_name'] = 'pacifica';
// $config['site_identifier'] = 'dēmos';
$config['application_version'] = "1.99.0";
?>
