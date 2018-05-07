<?php
/**
 * Pacifica.
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
 *
 * @link http://github.com/EMSL-MSC/pacifica-upload-status
 */
require_once 'Baseline_api_controller.php';

/**
 * Status API is a CI Controller class that extends Baseline_controller.
 *
 * The *Status API* class is the main entry point into the status
 * website. It provides overview pages that summarize a filtered
 * set of all uploads, as well as a single-transaction view
 * that shows the status of a specified upload transaction
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 *
 * @link http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Baseline_user_api_controller extends Baseline_api_controller
{
    /**
     * Constructor.
     *
     * Defines the base set of scripts/CSS files for every
     * page load
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(
            array(
                'url', 'html', 'myemsl_api',
                'file_info', 'theme', 'time'
            )
        );
        $this->load->helper(
            array(
                'inflector', 'item', 'form',
                'network', 'ingest_status'
            )
        );
        $this->load->library(array('table'));

        $this->last_update_time = get_last_update(APPPATH);
        $this->page_data['site_identifier'] = $this->config->item('site_identifier');
        $this->page_data['site_slogan'] = $this->config->item('site_slogan');
        $this->page_data['script_uris'] = array(
            '/resources/scripts/spinner/spin.min.js',
            '/resources/scripts/fancytree/dist/jquery.fancytree-all.js',
            '/resources/scripts/jquery-crypt/jquery.crypt.js',
            '/resources/scripts/select2-4/dist/js/select2.js',
            '/resources/scripts/bootstrap-daterangepicker/daterangepicker.js'
        );
        $this->page_data['css_uris'] = array(
            '/resources/scripts/bootstrap/css/bootstrap.css',
            '/resources/scripts/bootstrap-daterangepicker/daterangepicker.css',
            '/resources/scripts/fancytree/dist/skin-lion/ui.fancytree.min.css',
            '/project_resources/stylesheets/combined.css',
            '/resources/scripts/select2-4/dist/css/select2.css',
            '/resources/stylesheets/file_directory_styling.css',
            '/project_resources/stylesheets/cart.css',
            '/project_resources/stylesheets/font-awesome.min.css'
        );
        $this->page_data['load_prototype'] = false;
        $this->page_data['load_jquery'] = true;
    }
}
