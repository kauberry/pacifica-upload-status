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
                log_message('info', 'Attempting to create database instance...');
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
        $table_list = [
            'cart' => [
                'fields' => [
                    'cart_uuid' => [
                        'type' => 'VARCHAR',
                        'constraint' => '64',
                        'unique' => true
                    ],
                    'name' => [
                        'type' => 'VARCHAR'
                    ],
                    'description' => [
                        'type' => 'VARCHAR',
                        'null' => true
                    ],
                    'owner' => [
                        'type' => 'VARCHAR',
                        'constraint' => '64'
                    ],
                    'json_submission' => [
                        'type' => 'VARCHAR'
                    ],
                    'last_known_state' => [
                        'type' => 'VARCHAR',
                        'default' => 'waiting'
                    ],
                    'created TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
                    'updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
                    'deleted' => [
                        'type' => 'TIMESTAMP',
                        'null' => true
                    ]
                ],
                'keys' => [
                    'cart_uuid'
                ]
            ],
            'cart_items' => [
                'fields' => [
                    'id' => [
                        'type' => 'INTEGER',
                        'auto_increment' => true,
                        'unsigned' => true
                    ],
                    'file_id' => [
                        'type' => 'BIGINT'
                    ],
                    'cart_uuid' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64
                    ],
                    'hashtype' => [
                        'type' => 'VARCHAR',
                        'default' => 'sha1'
                    ],
                    'hashsum' => [
                        'type' => 'VARCHAR',
                        'constraint' => 40
                    ],
                    'relative_local_path' => [
                        'type' => 'VARCHAR'
                    ],
                    'file_size_bytes' => [
                        'type' => 'BIGINT'
                    ],
                    'file_mime_type' => [
                        'type' => 'VARCHAR',
                        'null' => true
                    ],
                    'created TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
                    'updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
                    'deleted' => [
                        'type' => 'TIMESTAMP',
                        'null' => true
                    ]
                ],
                'keys' => [
                    'file_id', 'cart_uuid'
                ]
            ],
            'doi_records' => [
                'fields' => [
                    'registration_id' => [
                        'type' => 'INTEGER',
                        'unique' => true
                    ],
                    'title' => [
                        'type' => 'VARCHAR',
                        'null' => false
                    ],
                    'description' => [
                        'type' => 'TEXT',
                        'null' => true
                    ],
                    'doi_reference' => [
                        'type' => 'VARCHAR',
                        'null' => true
                    ],
                    'access_url' => [
                        'type' => 'VARCHAR',
                        'null' => false
                    ],
                    'transaction_id' => [
                        'type' => 'INTEGER',
                        'null' => false
                    ],
                    'created TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
                    'updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
                    'deleted' => [
                        'type' => 'TIMESTAMP',
                        'null' => true
                    ]
                ],
                'keys' => [
                    'registration_id'
                ]
            ]
        ];
        foreach ($table_list as $table_name => $item_collection) {
            $this->generate_table($table_name, $item_collection['fields'], $item_collection['keys']);
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

    private function generate_table($table_name, $field_collection, $key_collection = false)
    {
        if ($this->table_exists($table_name)) {
            return false;
        }
        $this->dbforge->add_field($field_collection);
        if ($key_collection) {
            if (!is_array($key_collection)) {
                $key_collection = [$key_collection];
            }
            $this->dbforge->add_key($key_collection, true);
        }
        if ($this->dbforge->create_table($table_name)) {
            log_message("info", "Created table => '{$table_name}'...");
        }
    }
}
