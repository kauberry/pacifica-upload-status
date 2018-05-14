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

 // if (!is_cli()) exit('No URL-based access allowed');

/**
 * System setup model
 *
 * The **System_setup_model** configures the database backend and gets the
 * underlying system architecture in place during deployment.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class System_setup_model extends CI_Model
{
    /**
     *  Class constructor.
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();

        //quickly assess the current system status
        try {
            $this->setup_db_structure();
        } catch (Exception $e) {
            log_message('error', "Could not create database instance. {$e->message}");
            $this->output->set_status_header(500);
        }
        $this->global_try_count = 0;
    }

    /**
     *  Create the initial database entry
     *
     *  @param string $db_name The name of the db to create
     *
     *  @return [type]   [description]
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function _check_and_create_database($db_name)
    {
        if ($this->db->platform() != 'sqlite3') {
            if (!$this->dbutil->database_exists($db_name)) {
                log_message('info', 'Attempting to create database structure...');
                //db doesn't already exist, so make it
                if ($this->dbforge->create_database($db_name)) {
                    log_message('info', "Created {$db_name} database instance");
                } else {
                    log_message('error', "Could not create database instance.");
                    $this->output->set_status_header(500);
                }
            }
        } else {
            log_message('info', 'DB Type is sqlite3, so we don\'t have to explicitly make the db file');
        }
    }

    /**
     *  Configure the table structures in the database
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function setup_db_structure()
    {
        //check for database existence
        $this->load->database('default');
        $this->load->dbforge();
        $this->load->dbutil();

        $this->_check_and_create_database($this->db->database);

        //the database should already be in place. Let's make some tables
        if (!$this->db->table_exists('cart')) {
            $cart_fields = array(
                'cart_uuid' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '64',
                    'unique' => true
                ),
                'name' => array(
                    'type' => 'VARCHAR'
                ),
                'description' => array(
                    'type' => 'VARCHAR',
                    'null' => true
                ),
                'owner' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '64'
                ),
                'json_submission' => array(
                    'type' => 'VARCHAR'
                ),
                'last_known_state' => array(
                    'type' => 'VARCHAR',
                    'default' => 'waiting'
                ),
                'created' => array(
                    'type' => 'TIMESTAMP',
                    'default' => 'now()'
                ),
                'updated' => array(
                    'type' => 'TIMESTAMP'
                ),
                'deleted' => array(
                    'type' => 'TIMESTAMP',
                    'null' => true
                )
            );
            $this->dbforge->add_field($cart_fields);
            $this->dbforge->add_key('cart_uuid', true);
            if ($this->dbforge->create_table('cart')) {
                log_message("info", "Created 'cart' table...");
            };
        }


        if (!$this->db->table_exists('cart_items')) {
            $cart_items_fields = array(
                'id' => array(
                    'type' => 'INTEGER',
                    'auto_increment' => true,
                    'unsigned' => true
                ),
                'file_id' => array(
                    'type' => 'BIGINT'
                ),
                'cart_uuid' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64
                ),
                'hashtype' => array(
                    'type' => 'VARCHAR',
                    'default' => 'sha1'
                ),
                'hashsum' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 40
                ),
                'relative_local_path' => array(
                    'type' => 'VARCHAR'
                ),
                'file_size_bytes' => array(
                    'type' => 'BIGINT'
                ),
                'file_mime_type' => array(
                    'type' => 'VARCHAR',
                    'null' => true
                )
            );
            $this->dbforge->add_field($cart_items_fields);
            $this->dbforge->add_key(array('file_id', 'cart_uuid'), true);
            if ($this->dbforge->create_table('cart_items')) {
                log_message("info", "Created 'cart_items' table...");
            };
        }
    }
}
