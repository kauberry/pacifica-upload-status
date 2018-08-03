<?php
/**
 * Data Release Credentials and Settings
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Data_Release_Config
 * @author   Ken Auberry <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-reporting
 */

defined('BASEPATH') or exit('No direct script access allowed');

$config['drhub_url_base'] = 'https://lampdev02.pnl.gov/drhub/drhub/api';
// $config['drhub_username'] = 'svcDataHub';
// $config['drhub_password'] = 'D@taHubP@ss73';
$config['drhub_username'] = 'svcDataHubAdmin';
$config['drhub_password'] = 'password';
$config['drhub_default_repository_name'] = 'EMSL';

$config['default_contract_title'] = "PNNL 1830 Contract";
$config['default_contract_number'] = "DE-AC05-76RL01830";
