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
 * Status API Model
 *
 * The **Status_api_model** performs most of the heavy lifting for the status site.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Data_transfer_api_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->config->load('data_release');
        $this->dh_username = getenv('DRHUB_USERNAME') ?: $this->config->item('drhub_username');
        $this->dh_password = getenv('DRHUB_PASSWORD') ?: $this->config->item('drhub_password');
        $this->drhub_url_base = getenv('DRHUB_URL_BASE') ?: $this->config->item('drhub_url_base');
        $this->load->helper(array('item', 'network', 'time'));
        $this->load->model('Status_api_model', 'status');
        $this->ds_table = 'drhub_data_sets';
        $this->dr_table = 'drhub_data_records';
        $this->sess = false;
        $this->json_headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        // $this->sess = $this->get_drhub_session();
    }

    private function get_drhub_session()
    {
        if (!$this->sess) {
            $sess = new Requests_Session($this->drhub_url_base);
            $post_data = [
                'username' => $this->dh_username,
                'password' => $this->dh_password
            ];
            // $headers = [
            //     'Accept' => 'application/json'
            // ];
            $dh_url = "{$this->drhub_url_base}/dataset/user/login";
            $response = $sess->post("{$this->drhub_url_base}/dataset/user/login", $this->json_headers, $post_data);
            $response_object = json_decode($response->body);
            // var_dump($response_object);
            $sess->headers['X-CSRF-Token'] = $response_object->token;
            $this->sess = $sess;
        }
    }

    /**
     * This is going to need a fairly specific set of data to push this back out to DRHub
     *
     * @param  [type] $release_info [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function publish_doi_externally($release_info, $dataset_id)
    {
        $url = $this->config->item('external_release_base_url');
        $transaction_id = $release_info['transaction_id'];
        $transient_info = $this->get_transient_record_for_transaction($transaction_id, $dataset_id);
        $publishing_skeleton = [
            'title' => $release_info['release_name'],
            'body' => $release_info['release_description'],
            'field_link_api' => '',  //release URL
            'field_format' => '',  //data format
            'field_ceii' => 1,  //holdover from DRPower, set to '1'
            'field_repository_name' => $this->config->item('drhub_default_repository_name'),
            'field_science_theme' => '',  //pull from proposal info
            'field_instrument_id' => '',  //pull from transaction info
            'field_instrument_name' => '',  //pull from instruments table
            'field_project_id' => '',  //pull from transaction info
            'field_project_name' => '',  //pull from proposals list
            'field_data_creator_name' => '',  //pull from user record
            'field_dataset_ref' => $dataset_id
        ];
        $stored_release_info = $this->get_release_info($release_info['transaction_id']);
        $url .= "released_data/{$stored_release_info['transaction_id']}";
        $stored_release_info['field_link_api'] = $url;
        $publishing_data = array_merge($publishing_skeleton, $stored_release_info);
        $resource_id = $this->create_new_data_resource($publishing_data);
        $success = $this->link_resource_to_dataset($dataset_id, $resource_id, $transaction_id);
        $this->update_resource_owner($resource_id, $dataset_id);
        return $success;
    }

    private function get_release_info($transaction_id)
    {
        //now that we have a transaction, get transaction-level info
        $transaction_info = $this->status->get_formatted_transaction($transaction_id);
        $transaction_info = $transaction_info['transactions'][$transaction_id];
        $release_info = [
            'field_project_id' => $transaction_info['metadata']['proposal_id'],
            'field_instrument_id' => $transaction_info['metadata']['instrument_id'],
            'field_data_creator_name' => $this->user_info['display_name'],
            'transaction_id' => $transaction_id
        ];

        //get proposal_info
        $proposal_info = $this->lookup_external_info(
            $transaction_info['metadata']['proposal_id'],
            $transaction_info['metadata']['instrument_id']
        );

        $release_info = array_merge($release_info, $proposal_info);
        return $release_info;
    }

    private function lookup_external_info($proposal_id, $instrument_id)
    {
        $md_url = "{$this->metadata_url_base}/proposalinfo/by_proposal_id/{$proposal_id}";
        $query = Requests::get($md_url, ['Accept' => 'application/json']);
        $result = json_decode($query->body);

        $output = [
            'field_project_name' => $result->display_name,
            'field_instrument_name' => $result->instruments->$instrument_id->display_name,
            'field_data_creator_name' => $this->user_info['display_name'],
            'field_science_theme' => $result->science_theme,

        ];
        return $output;
    }

    /**
     * [get_release_states description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_release_states($transaction_list, $data_set_id = '')
    {
        $md_url = "{$this->metadata_url_base}/transactioninfo/release_state";
        $query = Requests::post($md_url, array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ), json_encode($transaction_list));
        $results = json_decode($query->body, true);
        $transient_info = [];
        foreach ($results as $result_item) {
            $transient_info = $this->get_transient_record_for_transaction($result_item['transaction'], $data_set_id);
            $results[$result_item['transaction']]['transient_info'] = $transient_info;
        }
        return $results;
    }


    private function create_new_data_resource($publishing_data)
    {
        $this->get_drhub_session();
        $lang = 'und';
        $formatted_request = [
            'title' => $publishing_data['title'],
            'body' => $publishing_data['body'],
            'type' => 'resource',
            'field_link_api' => [
                $lang => [
                    [
                        'attributes' => [],
                        'title' => $publishing_data['field_link_api'],
                        'url' => $publishing_data['field_link_api'],
                    ]
                ]
            ],
            'og_user_permission_inheritance' => [
                $lang => ['value' => 0]
            ]
        ];
        unset($publishing_data['field_dataset_ref']);
        unset($publishing_data['field_link_api']);
        foreach ($publishing_data as $name => $value) {
            if (substr($name, 0, 6) === "field_" && !empty($value)) {
                $formatted_request[$name] = [
                    $lang => [
                        ['value' => $value]
                    ]
                ];
            }
        }
        $dh_url = "{$this->drhub_url_base}/dataset/node";
        $success = false;
        $query = $this->sess->post($dh_url, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], json_encode($formatted_request));
        switch ($query->status_code) {
            case 200:
                $results = json_decode($query->body);
                if (array_key_exists('nid', $results)) {
                    $resource_id = $results->nid;
                    $success = $resource_id;
                }
                break;
            case 404:
                break;
            case 406:
                $success = false;
                echo "invalid choice";
                var_dump($query);
                break;
        }
        return $success;
    }

    private function update_resource_owner($resource_id, $data_set_id)
    {
        $ds_info = $this->get_drhub_node($data_set_id);
        $update_object = [
            'uid' => $ds_info['uid'],
            'name' => $ds_info['name']
        ];
        return $this->update_resource($resource_id, $update_object);
    }

    private function update_resource($resource_id, $update_object)
    {
        $dh_url = "{$this->drhub_url_base}/dataset/node/{$resource_id}";
        $success = false;
        $query = $this->sess->put($dh_url, array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ), json_encode($update_object));
        if ($query->status_code == 200) {
            echo $query->body;
            $results = json_decode($query->body);
            if ($results->nid == strval($dataset_id)) {
                $success = true;
            }
        }
        return $success;
    }

    private function link_resource_to_dataset($dataset_id, $resource_id, $transaction_id, $lang = "und")
    {
        $this->get_drhub_session();
        $ds_data = $this->get_drhub_node($dataset_id);
        $existing_ids = [];
        if (array_key_exists('field_resources', $ds_data) && !empty($ds_data['field_resources'])) {
            $existing_links = $ds_data['field_resources']['und'];
            foreach ($existing_links as $link_object) {
                $existing_ids[] = $link_object['target_id'];
            }
        }
        if (in_array($resource_id, $existing_ids)) {
            return true;
        }
        $existing_links[] = ['target_id' => $resource_id];
        $existing_ids[] = $resource_id;
        $field_resources = [];
        foreach ($existing_links as $link_object) {
            $dr_data = $this->get_drhub_node($link_object['target_id']);
            $str_resource_id = strval($link_object['target_id']);

            $formatted_target = "{$dr_data['title']} ({$str_resource_id})";
            $field_resources[] = ['target_id' => $formatted_target];
        }
        $formatted_request = [
            'field_resources' => [
                $lang => $field_resources
            ]
        ];
        $success = $this->update_resource($dataset_id, $formatted_request);
        $this->update_transient_data_records($dataset_id);
        return $success;
    }

    public function store_transient_data_set($dataset_id)
    {
        $success = false;
        if ($this->transient_record_exists($this->ds_table, $dataset_id)) {
            return true;
        }
        $ds_data = $this->get_drhub_node($dataset_id);
        if (!!($ds_data) && $ds_data['type'] == 'dataset') {
            $insert_data = [
                'node_id' => $dataset_id,
                'title' => $ds_data['title']
            ];
            if ($ds_data['body']) {
                $insert_data['description'] = $ds_data['body']['und'][0]['value'];
            }
            $this->db->insert($this->ds_table, $insert_data);
            $success = !!($this->db->affected_rows());
        }
        return $success;
    }

    public function store_transient_data_record($drhub_resource_info, $dataset_id, $transaction_id, $lang = 'und')
    {
        $this->store_transient_data_set($dataset_id);
        $success = false;
        $ds_data = $this->get_drhub_node($dataset_id);
        $target_list = [];
        if ($drhub_resource_info && $ds_data) {
            $record_id = $drhub_resource_info['nid'];
            $insert_data = [
                'node_id' => $record_id,
                'data_set_node_id' => $dataset_id,
                'transaction_id' => $transaction_id,
                'accessible_url' => $drhub_resource_info['field_link_api'][$lang][0]['url']
            ];
            if (!$this->transient_record_exists($this->dr_table, $record_id)) {
                $this->db->insert($this->dr_table, $insert_data);
                $success = !!($this->db->affected_rows());
            } else {
                $success = true;
            }
        }
        return $success;
    }

    public function update_transient_data_records($data_set_id)
    {
        $data_set_info = $this->get_drhub_node($data_set_id);
        $transient_data_records = $this->get_transient_records_for_data_set($data_set_id);
        $drhub_resource_id_list = [];
        $transient_resource_id_list = array_keys($transient_data_records['by_resource_id']);
        if (array_key_exists('field_resources', $data_set_info) && $data_set_info['field_resources']) {
            $linked_resources = $data_set_info['field_resources']['und'];
            $resource_info = [];
            foreach ($linked_resources as $resource_object) {
                $resource_id = $resource_object['target_id'];
                $drhub_resource_id_list[] = $resource_id;
                $full_resource_info = $this->get_drhub_node($resource_id);
                if (!array_key_exists($resource_id, $transient_data_records['by_resource_id'])) {
                    if (preg_match(
                        '/https?:\/\/.+\/(\d+)/',
                        $full_resource_info['field_link_api']['und'][0]['url'],
                        $matches
                    )) {
                        $extracted_txn_id = $matches[1];
                    }
                    $this->store_transient_data_record($full_resource_info, $data_set_id, $extracted_txn_id);
                } else {
                    $extracted_txn_id = $transient_data_records['by_resource_id'][$resource_id]['transaction_id'];
                }
                $resource_info = [
                    'resource_id' => $resource_id,
                    'title' => $full_resource_info['title'],
                    'release_url' => $full_resource_info['field_link_api']['und'][0]['url'],
                    'transaction_id' => $extracted_txn_id
                ];
            }
        }
        //clear out records from the transient table that no longer exist in drhub
        $extra_resource_records = array_diff($transient_resource_id_list, $drhub_resource_id_list);
        $this->remove_transient_data_records($extra_resource_records, $data_set_id);


        return $this->get_transient_records_for_data_set($data_set_id);
    }

    public function remove_transient_data_records($record_id_list, $data_set_id)
    {
        if ($record_id_list) {
            $this->db->where('data_set_node_id', $data_set_id);
            $this->db->where_in('node_id', $record_id_list);
            $this->db->delete($this->dr_table);
        }
        //check for remaining records
        $check_query = $this->db->get_where($this->dr_table, ['data_set_node_id' => $data_set_id]);
        if ($check_query->num_rows() == 0) {
            //no records left, so remove the data set entry too
            $this->db->delete($this->ds_table, ['node_id' => $data_set_id]);
        }
    }

    public function get_transient_record_for_transaction($transaction_id, $dataset_id = "")
    {
        $md_url = "{$this->metadata_url_base}/transaction_release?";
        $url_args_array = [
            'transaction' => $transaction_id
        ];
        // if (!empty($dataset_id)) {
        //     $url_args_array['data_set_node_id'] = $dataset_id;
        // }
        $resource_results = [];
        $md_url .= http_build_query($url_args_array, '', '&');
        $query = Requests::get($md_url, ['Accept' => 'application/json']);
        $results = json_decode($query->body, true);
        if ($results) {
            $results = array_pop($results);
            //go look for these release id values
            if (!empty($dataset_id)) {
                $this->db->where('data_set_node_id', $dataset_id);
            }
            $resource_query = $this->db->get_where($this->dr_table, ['transaction_id' => $transaction_id]);
            if ($resource_query->num_rows() > 0) {
                $resource_results = $resource_query->row_array();
            }
        }
        return $resource_results;
    }

    public function get_transient_records_for_data_set($dataset_id)
    {
        $transient_records = [
            'by_resource_id' => [],
            'by_transaction_id' => []
        ];
        $resource_query = $this->db->get_where($this->dr_table, ['data_set_node_id' => $dataset_id]);
        if ($resource_query->num_rows() > 0) {
            foreach ($resource_query->result() as $row) {
                $transient_records['by_resource_id'][$row->node_id] = [
                    'resource_id' => $row->node_id,
                    'transaction_id' => $row->transaction_id
                ];
                $transient_records['by_transaction_id'][$row->transaction_id][$row->node_id] = [
                    'resource_id' => $row->node_id,
                    'transaction_id' => $row->transaction_id
                ];
            }
        }
        return $transient_records;
    }

    private function transient_record_exists($table_name, $record_id)
    {
        $check_query = $this->db->get_where($table_name, ['node_id' => $record_id]);
        return !!($check_query->num_rows());
    }

    public function get_drhub_node($node_id)
    {
        $this->get_drhub_session();
        $dh_url = "{$this->drhub_url_base}/dataset/node/{$node_id}";
        $response = $this->sess->get($dh_url, ['Accept' => 'application/json']);
        $results = json_decode($response->body, true);
        if (!array_key_exists('type', $results)) {
            return false;
        } else {
            return $results;
        }
    }

    public function get_data_set_summary($data_set_id)
    {
        $data_set_info = [];
        if (!empty($data_set_id) && preg_match('/\d+/', $data_set_id)) {
            $full_data_set_info = $this->get_drhub_node($data_set_id);
            if (!$full_data_set_info) {
                return false;
            }
            $data_set_info = [
                'title' => $full_data_set_info['title'],
                'description' => '',
                'linked_resources' => [],
                'linked_transactions' => []
            ];

            if ($full_data_set_info['body']) {
                $data_set_info['description'] = $full_data_set_info['body']['und'][0]['value'];
            }
            $transient_data_records = $this->update_transient_data_records($data_set_id);
            if (array_key_exists('field_resources', $full_data_set_info) && $full_data_set_info['field_resources']) {
                $linked_resources = $full_data_set_info['field_resources']['und'];
                $resource_info = [];
                foreach ($linked_resources as $resource_object) {
                    $resource_id = $resource_object['target_id'];
                    $full_resource_info = $this->get_drhub_node($resource_id);
                    $resource_info = [
                        'resource_id' => $resource_id,
                        'title' => $full_resource_info['title'],
                        'release_url' => $full_resource_info['field_link_api']['und'][0]['url'],
                        'transaction_id' => $transient_data_records['by_resource_id'][$resource_id]['transaction_id']
                    ];
                    $data_set_info['linked_resources'][$resource_id] = $resource_info;
                    $data_set_info['linked_transactions'][] = $resource_info['transaction_id'];
                }
            }
            if (array_key_exists('field_associated_doi_request', $full_data_set_info) && $full_data_set_info['field_associated_doi_request']) {
                //if this field is present, then we've sent off the minting request
                $doi_requests = $full_data_set_info['field_associated_doi_request']['und'];
                foreach ($doi_requests as $request_object) {
                    $request_id = $request_object['target_id'];
                    $request_info = $this->extract_doi_request_info($request_id);
                    $data_set_info['doi_requests'][$request_id] = $request_info;
                }
            }
        }
        return $data_set_info;
    }

    private function extract_doi_request_info($doi_request_id)
    {
        $full_request_info = $this->get_drhub_node($doi_request_id);
        $doi_string = $full_request_info['field_doi'] ? $full_request_info['field_doi']['und'][0]['value'] : '';
        $osti_id = $full_request_info['field_osti_id'] ? $full_request_info['field_osti_id']['und'][0]['value'] : '';
        $ref_data_set = $full_request_info['field_referenced_dataset'] ? $full_request_info['field_referenced_dataset']['und'][0]['target_id'] : '';
        $results = [
            'data_set_id' => $ref_data_set,
            'doi' => $doi_string,
            'name' => $full_request_info['title']
        ];
        if (!empty($osti_id)) {
            $results['osti_id'] = $osti_id;
        }
        return $results;
    }

    public function set_doi_info($data_set_id)
    {
        $ds_info = $this->get_drhub_node($data_set_id);
        $doi_dataset_insert_url = "{$this->metadata_url_base}/doidatasets";
        $doi_transaction_doi_update_url = "{$this->metadata_url_base}/doitransaction";
        if (array_key_exists('field_associated_doi_request', $ds_info) && $ds_info['field_associated_doi_request']) {
            $doi_requests = $full_data_set_info['field_associated_doi_request']['und'];
            foreach ($doi_requests as $request_object) {
                $request_id = $request_object['target_id'];
                $request_info = $this->extract_doi_request_info($request_id);
                $doi_string = $request_info['doi'];
            }
        }
        foreach ($doi_info as $doi_entry) {
            $data_set_id = $doi_entry['data_set_id'];
            $doi_string = $doi_entry['doi'];
            $transient_info = $this->update_transient_data_records($data_set_id);
            if (!$transient_info) {
                $this->store_transient_data_set($data_set_id);
            }
        }
    }

    private function insert_doi_dataset_entries($request_info)
    {
        //check if it's already in the system
        $insert_url = "{$this->metadata_url_base}/doidatasets";
        $check_request = Requests::get($insert_url, $this->json_headers);
        $check_results = json_decode($check_request, true);
        if ($check_results) {
            if ($request_info['name'] != $check_results['name']) {
                $update_object = [
                    'name' => $request_info['name']
                ];
                $update_url_args = [
                    'doi' => $request_info['doi']
                ];
                $update_url .= http_build_query($update_url_args, '', '&');

                $update_request = Requests::post($update_url, $this->json_headers, $update_object);
            }
        }

        $success = false;
        // TODO: need to figure out who the creator is supposed to be
        $insert_object = [
            [
                'doi' => $request_info['doi'],
                'name' => $request_info['name'],
                'creator' => ''
            ]
        ];
        $insert_request = Requests::put($insert_url, $this->json_headers, $insert_object);
        if ($insert_request->status_code == 200) {
            $success = true;
        }
        return $success;
    }

    private function insert_doi_transaction_entries($request_info, $data_set_id)
    {
        $this->db->where('node_id', $data_set_id)->update($this->ds_table, ['doi_reference_string' => $request_info['doi']]);
        $transaction_id_query = $this->db->get_where($this->dr_table, ['data_set_node_id' => $data_set_id]);
        $transaction_list = [];
        if ($transaction_id_query && $transaction_id_query->num_rows() > 0) {
            foreach ($transaction_id_query->rows() as $row) {
                $transaction_list[] = $row->transaction_id;
            }
        }
        $doi_transaction_insert_url = "{$this->metadata_url_base}/doitransaction";
        $insert_objects = [];
        $success = false;
        foreach ($transaction_list as $transaction_id) {
            $insert_object = [
                'doi_id' => $request_info['doi'],
                'transaction_id' => $transaction_id
            ];
            $insert_objects[] = $insert_object;
        }
        $insert_request = Requests::post($doi_transaction_insert_url, $this->json_headers, $insert_objects);
        if ($insert_request->status_code == 200) {
            $success = true;
        }
        return $success;
    }
}
