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
require_once 'Baseline_controller.php';

/**
 * Status is a CI Controller class that extends Baseline_controller
 *
 * The *Status* class is the main entry point into the status
 * website. It provides overview pages that summarize a filtered
 * set of all uploads, as well as a single-transaction view
 * that shows the status of a specified upload transaction
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Status extends Baseline_controller
{
    /**
     * Constructor
     *
     * Defines the base set of scripts/CSS files for every
     * page load
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('status_model', 'status');
        $this->load->model('Myemsl_model', 'myemsl');
        $this->load->model('Cart_model', 'cart');
        $this->load->helper(
            array(
            'inflector', 'item', 'url',
            'form', 'network'
            )
        );
        $this->load->library(array('table'));
        $this->status_list = array(
          0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
          3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived',
        );
        $this->valid_search_term_types = array();

        $this->last_update_time = get_last_update(APPPATH);

        $this->page_data['script_uris'] = array(
            '/resources/scripts/spinner/spin.min.js',
            '/resources/scripts/fancytree/jquery.fancytree-all.js',
            '/resources/scripts/jquery-crypt/jquery.crypt.js',
            '/resources/scripts/myemsl_file_download.js',
            '/project_resources/scripts/status_common.js',
            '/resources/scripts/select2-4/dist/js/select2.js',
            '/resources/scripts/moment.min.js'
        );
        $this->page_data['css_uris'] = array(
            '/resources/scripts/fancytree/skin-lion/ui.fancytree.min.css',
            '/resources/stylesheets/status.css',
            '/resources/stylesheets/status_style.css',
            '/resources/scripts/select2-4/dist/css/select2.css',
            '/resources/stylesheets/file_directory_styling.css',
            '/resources/stylesheets/bread_crumbs.css',
        );

    }

    /**
     * Primary index redirect method.
     *
     * @return void
     */
    public function index()
    {
        redirect('status/overview');
    }

    /**
     * Provides a page that shows detailed information for a
     * specific upload transaction.
     *
     * @param string $lookup_type string of the lookup type (job or trans)
     * @param type   $id          the ID of the lookup_type
     *
     * @return void
     */
    public function view($lookup_type, $id = -1)
    {
        $valid_lookup_types = array(
          'j' => 'j', 'job' => 'j', 't' => 't', 'transaction' => 't',
        );
        $lookup_type_descriptions = array('j' => 'job', 't' => 'transaction');
        $this->page_data['load_prototype'] = FALSE;
        $this->page_data['load_jquery'] = TRUE;
        $this->page_data['status_list'] = $this->status_list;
        $inst_id = -1;
        if (!array_key_exists($lookup_type, $valid_lookup_types)) {
            //not a valid lookup type, so try as job first
            $this->page_data['lookup_type_desc'] = 'Lookup Type';
            $this->page_data['lookup_type'] = $lookup_type;
            $this->page_data['error_message'] = "'{$lookup_type}' is not a valid ".
                "lookup type. Try 't' (for transactions) or 'j' (for jobs)";
            $this->load->view('status_error_page.html', $this->page_data);
            // redirect(base_url()."index.php/status/view/{$lookup_type}/{$id}");
        }

        if(array_key_exists($lookup_type, $lookup_type_descriptions)) {
            $lookup_type_description = $lookup_type_descriptions[$lookup_type];
        } else {
            $lookup_type_description = 'job';
        }
        if (!is_numeric($id) || $id < 0) {
            //that doesn't look like a real id
            //send to error page saying so
            $err_msg = 'No '.ucwords($lookup_type_description)." with the ".
                    "{$id} could be found in the system";
            $this->page_data['error_message'] = $err_msg;
            $this->page_data['lookup_type_desc'] = $lookup_type_description;
            $this->page_data['lookup_type'] = $lookup_type;
            $this->load->view('status_error_page.html', $this->page_data);
        }

        $this->page_data['page_header'] = 'Upload Report';
        $this->page_data['title'] = 'Upload Report';

        $this->page_data['css_uris']
            = array_merge(
                $this->page_data['css_uris'], array(
                '/project_resources/stylesheets/view.css'
                )
            );


        $this->page_data['script_uris']
            = array_merge(
                $this->page_data['script_uris'], array(
                '/resources/scripts/single_item_view.js',
                '/resources/scripts/jquery-dateFormat/jquery-dateFormat.min.js'
                )
            );


        if ($lookup_type == 'j' || $lookup_type == 'job') {
            //lookup transaction_id from job
            $tx_info = $this->status->get_transaction_info($id);
            $tx_id = $tx_info['transaction_id'];
            if ($tx_info['transaction_id'] > 0 && $tx_info['current_step'] >= 5) {
                redirect(base_url()."view/t/{$tx_id}");
            } else {
                $job_status_info = $this->status->get_formatted_object_for_job($id);
                if (empty($job_status_info)) {
                    $err_msg = "No {$lookup_type_description} with an identifier ".
                            "of {$id} was found";
                    $this->page_data['message'] = $err_msg;
                    $this->page_data['script_uris'] = array();
                }
                $this->page_data['transaction_data'] = $job_status_info;
            }
        } else {
            $this->page_data['transaction_sizes']
                = $this->status->get_total_size_for_transactions(array($id));
            $inst_id = $this->status->get_instrument_for_id('t', $id);
            $transaction_list = array();
            $transaction_list[] = $id;

            $transaction_info
                = $this->status->get_formatted_object_for_transactions(
                    $transaction_list
                );
            if (empty($transaction_info)) {
                $err_msg = "No {$lookup_type_description} with an identifier of ".
                        "{$id} was found";
                $this->page_data['message'] =  $err_msg;
                $this->page_data['script_uris'] = array();
            }
            $this->page_data['transaction_data'] = $transaction_info;
        }

        $this->page_data['cart_data'] = array(
            'carts' => $this->cart->get_active_carts($this->user_id, FALSE)
        );

        $this->page_data['request_type'] = $lookup_type;
        $this->page_data['enable_breadcrumbs'] = TRUE;
        $this->page_data['js'] = "var initial_inst_id = '{$inst_id}';
                            var lookup_type = '{$lookup_type}';
                            var email_address = '{$this->email}';
                            ";
        $this->page_data['show_instrument_data'] = TRUE;
        $this->load->view('single_item_view.html', $this->page_data);
    }

    /**
     * Primary index page shows overview of status for that user.
     *
     * @param string $proposal_id   id of the proposal to display
     * @param string $instrument_id id of the instrument to display
     * @param string $time_period   time period the status should be displayed
     *
     * @return void
     */
    public function overview(
        $proposal_id = FALSE,
        $instrument_id = FALSE,
        $time_period = FALSE
    )
    {
        if($this->input->cookie('myemsl_status_last_timeframe_selector')) {
            $time_period
                = $this->input->cookie('myemsl_status_last_timeframe_selector');
        }
        if($this->input->cookie('myemsl_status_last_instrument_selector')) {
            $instrument_id
                = $this->input->cookie('myemsl_status_last_instrument_selector');
        }
        if($this->input->cookie('myemsl_status_last_proposal_selector')) {
            $proposal_id
                = $this->input->cookie('myemsl_status_last_proposal_selector');
        }
        if (!$this->input->is_ajax_request()) {
            $view_name = 'emsl_mgmt_view.html';
            $this->page_data['page_header'] = 'MyEMSL Status Reporting';
            $this->page_data['title'] = 'Overview';
            $this->page_data['informational_message'] = '';
            $this->page_data['css_uris']
                = array_merge(
                    $this->page_data['css_uris'], array(
                    '/project_resources/stylesheets/selector.css',
                    '/project_resources/stylesheets/overview.css'
                    )
                );
            $this->page_data['script_uris']
                = array_merge(
                    $this->page_data['script_uris'], array(
                    '/project_resources/scripts/emsl_mgmt_view.js'
                    )
                );

            $this->benchmark->mark('get_user_info_from_ws_start');
            $full_user_info = $this->myemsl->get_user_info();
            $this->benchmark->mark('get_user_info_from_ws_end');

            $proposal_list = array();
            if (array_key_exists('proposals', $full_user_info)) {
                foreach ($full_user_info['proposals'] as $prop_id => $prop_info) {
                    if (array_key_exists('title', $prop_info)) {
                        $proposal_list[$prop_id] = $prop_info['title'];
                    }
                }
            }
            krsort($proposal_list);
            $js = "var initial_proposal_id = '{$proposal_id}';
                    var initial_instrument_id = '{$instrument_id}';
                    var initial_time_period = '{$time_period}';
                    var email_address = '{$this->email}';
                    var lookup_type = 't';
                    var initial_instrument_list = [];";

            $this->page_data['proposal_list'] = $proposal_list;

            $this->page_data['load_prototype'] = FALSE;
            $this->page_data['load_jquery'] = TRUE;
            $this->page_data['selected_proposal'] = $proposal_id;
            $this->page_data['time_period'] = $time_period;
            $this->page_data['instrument_id'] = $instrument_id;
            $this->page_data['js'] = $js;
        } else {
            $view_name = 'upload_item_view.html';
        }

        if (isset($instrument_id) && isset($time_period) && $time_period > 0) {
            // $inst_lookup_id = $instrument_id >= 0 ? $instrument_id : "";
            $group_lookup_list
                = $this->status->get_instrument_group_list($instrument_id);
            if ($instrument_id > 0
                && array_key_exists(
                    $instrument_id,
                    $group_lookup_list['by_inst_id']
                )
            ) {
                $results = $this->status->get_transactions_for_group(
                    array_keys($group_lookup_list['by_inst_id'][$instrument_id]),
                    $time_period,
                    $proposal_id
                );
            } elseif ($instrument_id <= 0) {
                //this should be the "all instruments" trigger
                //  get all the instruments for this proposal

                $results = array(
                    'transaction_list' => array(),
                    'time_period_empty' => FALSE,
                    'message' => '',
                );
                foreach (
                    $group_lookup_list['by_inst_id'] as $inst_id => $group_id_list
                ) {
                    $transaction_list
                        = $this->status->get_transactions_for_group(
                            array_keys($group_id_list),
                            $time_period,
                            $proposal_id
                        );
                    if (!empty($transaction_list['transaction_list'])) {
                        foreach (
                            $transaction_list['transaction_list']['transactions']
                            as $group_id => $group_info
                        ) {
                            if(!array_key_exists(
                                'transactions',
                                $results['transaction_list']
                            )
                            ) {
                                $results['transaction_list']
                                    ['transactions'] = array();
                            }
                            if (!array_key_exists(
                                $group_id,
                                $results['transaction_list']['transactions']
                            )
                            ) {
                                $results['transaction_list']
                                    ['transactions'][$group_id] = $group_info;
                            }
                        }
                    }
                    if (!empty($transaction_list['transaction_list']['times'])) {
                        foreach (
                            $transaction_list['transaction_list']['times']
                            as $ts => $tx_id) {
                            if(!array_key_exists(
                                'times',
                                $results['transaction_list']
                            )
                            ) {
                                $results['transaction_list']['times'] = array();
                            }
                            if(!array_key_exists(
                                $ts,
                                $results['transaction_list']['times']
                            )
                            ) {
                                $results['transaction_list']['times']
                                    [$ts] = $tx_id;
                            }
                        }
                    }
                }
            } else {
                $results = array(
                    'transaction_list' => array(),
                    'time_period_empty' => TRUE,
                    'message' => 'No data uploaded for this instrument',
                );
            }
        } else {
            $results = array(
                'transaction_list' => array(),
                'time_period_empty' => TRUE,
                'message' => 'No data uploaded for this instrument',
            );
        }
        $this->page_data['cart_data'] = array(
            'carts' => $this->cart->get_active_carts($this->user_id, FALSE)
        );
        if(!empty($results) && array_key_exists('transaction_list', $results)) {
            if(array_key_exists('transactions', $results['transaction_list'])) {
                krsort($results['transaction_list']['transactions']);
            }
            if(array_key_exists('times', $results['transaction_list'])) {
                krsort($results['transaction_list']['times']);
            }
        }
        $this->page_data['enable_breadcrumbs'] = FALSE;
        $this->page_data['status_list'] = $this->status_list;
        $this->page_data['transaction_data'] = $results['transaction_list'];
        if (array_key_exists('transactions', $results['transaction_list'])
            && !empty($results['transaction_list']['transactions'])
        ) {
            $this->page_data['transaction_sizes']
                = $this->status->get_total_size_for_transactions(
                    array_keys($results['transaction_list']['transactions'])
                );
        } else {
            $this->page_data['transaction_sizes'] = array();
        }
        $this->page_data['informational_message'] = $results['message'];
        $this->page_data['request_type'] = 't';

        $this->load->view($view_name, $this->page_data);
    }

    /**
     * Get files for a specific transaction ID
     *
     * @param string $transaction_id transaction id to get files from
     *
     * @return void
     */
    public function get_files_by_transaction($transaction_id = FALSE)
    {
        if (!isset($transaction_id) || !$transaction_id) {
            $output_array = array();
        } else {
            $treelist = $this->status->get_files_for_transaction($transaction_id);
            $output_array = format_folder_object_json($treelist['treelist']);
        }
        transmit_array_with_json_header($output_array);
    }

    /**
     * Get the most current transactions
     *
     * @param string $instrument_id instrument ID for the transactions
     * @param string $proposal_id   proposal ID for the transactions
     * @param string $latest_id     specific latest ID of transaction
     *
     * @return void
     */
    public function get_latest_transactions(
        $instrument_id = '',
        $proposal_id = '',
        $latest_id = ''
    )
    {
        $group_list = $this->status->get_instrument_group_list();
        $new_transactions = array();
        if (array_key_exists($instrument_id, $group_list['by_inst_id'])) {
            $new_transactions = $this->status->get_latest_transactions(
                array_keys($group_list['by_inst_id'][$instrument_id]),
                $proposal_id,
                $latest_id
            );
        }
        if (empty($new_transactions)) {
            echo '';
            return;
        }
        $results = $this->status->get_formatted_object_for_transactions(
            $new_transactions
        );
        $group_list = $this->status->get_groups_for_transaction($new_transactions);
        foreach ($group_list['groups'] as $tx_id => $group_info) {
            $results['transactions'][$tx_id]['groups'] = $group_info;
        }

        $this->page_data['status_list'] = $this->status_list;
        $this->page_data['transaction_data'] = $results;
        $view_name = 'upload_item_view.html';
        if (!empty($results['times'])) {
            $this->load->view($view_name, $this->page_data);
        } else {
            echo '';
        }
    }

    /**
     * Get the status of a particular job
     *
     * @param string $lookup_type either job or transaction
     * @param int    $id          ID of lookup_type
     *
     * @return void
     */
    public function get_status($lookup_type, $id = 0)
    {
        //lookup by (j)ob or (t)ransaction
        //check for list of transactions in post
        if ($this->input->post('item_list')) {
            $item_list = $this->input->post('item_list');
        } elseif ($id > 0) {
            $item_list = array($id => $id);
        }
        $item_keys = array_keys($item_list);
        sort($item_keys);
        $last_id = array_pop($item_keys);

        $status_info = array();

        $status_obj = $this->status->get_status_for_transaction(
            $lookup_type,
            array_keys($item_list)
        );
        if (!empty($status_obj)) {
            foreach ($status_obj as $item_id => $item_info) {
                $sortable = $item_info;
                krsort($sortable);
                $latest_step_obj = array_shift($sortable);
                $latest_step = intval($latest_step_obj['step']);
                $status_info_temp = array(
                    'latest_step' => $latest_step,
                    'status_list' => $this->status_list,
                    'transaction_id' => $item_info[$latest_step]['trans_id'],
                );
                $item_text = trim(
                    $this->load->view(
                        'status_breadcrumb_insert_view.html',
                        $status_info_temp,
                        TRUE
                    )
                );
                if ($item_list[$item_id] != sha1($item_text)) {
                    $status_info[$item_id] = array(
                        'bar_text' => $item_text,
                        'transaction_id' => $status_info_temp['transaction_id'],
                        'current_step' => $status_info_temp['latest_step'],
                    );
                }
            }
            krsort($status_info);
            if ($this->input->is_ajax_request()) {
                transmit_array_with_json_header($status_info);
            } elseif (sizeof($status_info) == 1) {
                $this->load->view(
                    'status_breadcrumb_insert_view.html',
                    $status_info[$id]
                );
            }
        }
    }

    /**
     * Get Lazy Load Folder
     *
     * @return void
     */
    public function get_lazy_load_folder()
    {
        if (!$this->input->post('parent')) {
            echo '';
            return;
        }
        $node = intval(str_replace('treeData_', '', $this->input->post('parent')));
        $treelist = $this->status->get_files_for_transaction($node);
        $output_array = format_folder_object_json($treelist['treelist'], 'test');
        transmit_array_with_json_header($output_array);
    }

    /**
     * Get the job status for a particular job ID
     *
     * @param int $job_id job ID integer
     *
     * @return void
     */
    public function job_status($job_id = -1)
    {
        $HTTP_RAW_POST_DATA = file_get_contents('php://input');
        $values = json_decode($HTTP_RAW_POST_DATA, TRUE);
        if (!$values && $job_id > 0) {
            //must not have a list of values, so just check the one
            $values = array($job_id);
        }
        $results = $this->status->get_job_status($values, $this->status_list);
        // send_json_array($results);
        if($results) {
            transmit_array_with_json_header($results);
        }else{
            send_json_array(array());
        }
    }

    /**
     * Get instrument list for a proposal ID
     *
     * @param string $proposal_id proposal ID
     *
     * @return void
     */
    public function get_instrument_list($proposal_id)
    {
        // $instruments = $this->eus->get_instruments_for_proposal($proposal_id);
        $full_user_info = $this->myemsl->get_user_info();
        $instruments = array();
        if($this->is_emsl_staff) {
            $instruments = $this->eus->get_instruments_for_proposal($proposal_id);
        }else{
            $instruments_available
                = $full_user_info['proposals'][$proposal_id]['instruments'];
            foreach ($instruments_available as $inst_id) {
                $instruments[$inst_id] = "Instrument {$inst_id}: ".
                    $full_user_info['instruments'][$inst_id]['eus_display_name'];
            }
        }
        $instruments[-1] = "All Available Instruments for Proposal {$proposal_id}";

        asort($instruments);

        format_array_for_select2(array('items' => $instruments));
    }

    /**
     * Get instrument info for a specific instrument ID
     *
     * @param int $instrument_id instrument ID to lookup
     *
     * @return void
     */
    public function get_instrument_info($instrument_id = 0)
    {
        $results = array();
        if ($instrument_id) {
            $results = $this->eus->get_instrument_name($instrument_id);
        }
        transmit_array_with_json_header($results);
    }
}
