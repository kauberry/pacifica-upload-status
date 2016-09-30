<?php
/**
 * CI Testing Pacifica
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Test_Pacifica
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$config['allowed-resources'] = array(
    'https://test1.my.emsl.pnl.gov',
    'https://test2.my.emsl.pnl.gov'
);
?>
