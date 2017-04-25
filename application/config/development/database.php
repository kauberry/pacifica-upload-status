<?php
/**
 * CI Development Configuration
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Development_Config
 * @author   Ken Auberry <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */

defined('BASEPATH') OR exit('No direct script access allowed');
defined('BASEPATH') OR exit('No direct script access allowed');
$db['default'] = array(
  'hostname' => "",
  'username' => "",
  'password' => "",
  'database' => "/tmp/status.sqlite3",
  'dbdriver' => "sqlite3",
  'dbprefix' => "",
  'pconnect' => FALSE,
  'db_debug' => (ENVIRONMENT !== 'production'),
  'cache_on' => FALSE,
  'cachedir' => ""
);
