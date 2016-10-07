<?php
/**
 * CI Production Pacifica
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Production_Pacifica
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$config['allowed-resources'] = array(
    'https://a4.my.emsl.pnl.gov',
    'https://a5.my.emsl.pnl.gov'
);
?>
