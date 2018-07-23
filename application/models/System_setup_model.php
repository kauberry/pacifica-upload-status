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
        $this->phpfpm_log_dir = "/var/opt/rh/rh-php71/log/php-fpm";
        if (file_exists($this->phpfpm_log_dir."/db_create_completed.txt")) {
            return;
        }
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
        $table_list = ['cart', 'cart_items', 'drhub_data_sets', 'drhub_data_records'];
        foreach ($table_list as $table_name) {
            if (!$this->table_exists($table_name)) {
                $call_fn = "generate_{$table_name}_table";
                $this->$call_fn($table_name);
            }
        }
        if (file_exists($this->phpfpm_log_dir)) {
            touch($this->phpfpm_log_dir."/db_create_completed.txt");
        }
    }

    private function table_exists($table_name)
    {
        $query = $this->db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '{$table_name}';");
        return $query->num_rows() == 1;
    }

    private function generate_cart_table($table_name)
    {
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
                'default' => 'now'
            ),
            'updated' => array(
                'type' => 'TIMESTAMP',
                'default' => 'now'
            ),
            'deleted' => array(
                'type' => 'TIMESTAMP',
                'null' => true
            )
        );
        $this->dbforge->add_field($cart_fields);
        $this->dbforge->add_key('cart_uuid', true);
        if ($this->dbforge->create_table($table_name)) {
            log_message("info", "Created '{$table_name}' table...");
        };
    }

    private function generate_cart_items_table($table_name)
    {
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
        if ($this->dbforge->create_table($table_name)) {
            log_message("info", "Created '{$table_name}' table...");
        };
    }

    private function generate_drhub_data_sets_table($table_name)
    {
        $fields = array(
            'node_id' => array(
                'type' => 'INTEGER',
                'unique' => true
            ),
            'doi_reference_string' => array(
                'type' => 'VARCHAR',
                'null' => true
            ),
            'title' => array(
                'type' => 'VARCHAR',
                'null' => true
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => true
            ),
            'created' => array(
                'type' => 'TIMESTAMP',
                'default' => 'now'
            ),
            'updated' => array(
                'type' => 'TIMESTAMP',
                'default' => 'now'
            ),
            'deleted' => array(
                'type' => 'TIMESTAMP',
                'null' => true
            )
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key(array('node_id'), true);
        if ($this->dbforge->create_table($table_name)) {
            log_message("info", "Created '{$table_name}' table...");
        };
    }

    private function generate_drhub_data_records_table($table_name)
    {
        $fields = array(
            'node_id' => array(
                'type' => 'INTEGER',
                'unique' => true
            ),
            'data_set_node_id' => array(
                'type' => 'VARCHAR'
            ),
            'accessible_url' => array(
                'type' => 'VARCHAR',
                'null' => true
            ),
            'transaction_id' => array(
                'type' => 'INTEGER'
            ),
            'created' => array(
                'type' => 'TIMESTAMP',
                'default' => 'now'
            ),
            'updated' => array(
                'type' => 'TIMESTAMP',
                'default' => 'now'
            ),
            'deleted' => array(
                'type' => 'TIMESTAMP',
                'null' => true
            )
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key(array('node_id', 'data_set_node_id'), true);
        if ($this->dbforge->create_table($table_name)) {
            log_message("info", "Created '{$table_name}' table...");
        };
    }
}
