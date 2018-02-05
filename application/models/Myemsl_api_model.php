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
 * PHP Version 5
 *
 * @package Pacifica-upload-status
 * @author  Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */

/**
 *  MyEMSL model
 *
 *  The **Myemsl_model** class queries the EUS database clone
 *  for information on proposals, instruments and users
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Myemsl_api_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('myemsl_api');
    }

    /**
     *  Return any information known to the system about a
     *  specified user
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_user_info()
    {

        return get_user_details($this->user_id);
    }
}
