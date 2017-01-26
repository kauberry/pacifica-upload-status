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

$config['internal_cart_url'] = !empty(getenv('CART_PORT')) ?
    str_replace('tcp://', 'http://', getenv('CART_PORT')) :
    'http://cart:8081';

$config['external_cart_url'] = !empty(getenv('CART_PORT')) ?
    str_replace('tcp://', 'http://', getenv('CART_DOWNLOAD_PORT')) :
    'http://download.my.emsl.pnl.gov';

$config['template'] = 'emsl';
$config['site_color'] = 'orange';

$config['application_version'] = "1.99.0";
?>


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

$config['internal_cart_url']
    = !empty(getenv('CART_PORT')) ?
    str_replace('tcp://', 'http://', getenv('CART_PORT')) :
    'http://cart:8081';

$config['external_cart_url']
    = !empty(getenv('CART_PORT')) ?
    str_replace('tcp://', 'http://', getenv('CART_DOWNLOAD_PORT')) :
    'http://download.my.emsl.pnl.gov';

$config['template'] = 'emsl';
$config['site_color'] = 'orange';

$config['application_version'] = "0.99.11";
?>
