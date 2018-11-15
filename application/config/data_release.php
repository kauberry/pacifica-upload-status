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

$config['default_contract_title'] = "PNNL 1830 Contract";
$config['default_contract_number'] = "DE-AC05-76RL01830";

$config['doi_ui_base'] = "https://doi-reg-emsl.datahub.pnl.gov/";
$config['doi_url_base'] = "https://doi-api-emsl.datahub.pnl.gov/";
$config['originating_research_organizations'] = ["EMSL"];
