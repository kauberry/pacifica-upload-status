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
 * Status Model
 *
 * The **Status_model** performs most of the heavy lifting for the status site.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Status_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->local_timezone = 'US/Pacific';
        $this->load->library('EUS', '', 'eus');
        $this->load->helper('item');
        $this->status_list = array(
            0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
            3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived',
        );
    }

    /**
     *  Return a set of status items for a list of instruments over a given
     *  range of times.
     *
     *  @param array  $instrument_list The list of instruments to use in this query this query
     *                                       this query
     *  @param string $time_period     a strtotime() parseable relative datetime object specifier datetime object specifier
     *                                       datetime object specifier
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_status_for_instrument_over_range($instrument_list, $time_period = '1 day')
    {
        $current_time_obj = new DateTime();
        if(strtotime($time_period)) {
            $current_time_obj->setTime($current_time_obj->getHours(), 0, 0);
            $start_time = date_modify($current_time_obj, "-{$time_period}");
        }else{
            $start_time = $current_time_obj;
        }

        return $this->get_status_for_instrument_before_date($instrument_list, $start_time);
    }

    /**
     *  Retrieve the list of internal Pacifica groups that refer to
     *  a given instrument id
     *
     *  @param string $inst_id_filter partial instrument id
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instrument_group_list($inst_id_filter = '')
    {
        $DB_metadata = $this->load->database('default', TRUE);

        $DB_metadata->select(array('group_id', 'name', 'type'));
        $where_clause = "(type = 'omics.dms.instrument_id' or type ilike 'instrument.%') and name not in ('foo')";

        $DB_metadata->where($where_clause);
        $query = $DB_metadata->order_by('name')->get('groups');
        $results_by_group = array();
        $results_by_inst_id = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                if ($row->type == 'omics.dms.instrument_id') {
                    $inst_id = intval($row->name);
                } elseif (strpos($row->type, 'Instrument.') >= 0) {
                    $inst_id = intval(str_replace('Instrument.', '', $row->type));
                } else {
                    continue;
                }
                $results_by_inst_id[$inst_id][$row->group_id] = $row->name;
            }
        }
        $results = array('by_group' => $results_by_group, 'by_inst_id' => $results_by_inst_id);

        return $results;
    }

    /**
     *  Retrieve the list of internal Pacifica groups that refer to
     *  a given proposal id
     *
     *  @param string $proposal_id partial proposal_id
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_proposal_group_list($proposal_id = '')
    {
        $DB_metadata = $this->load->database('default', TRUE);

        $DB_metadata->select(array('group_id', 'name', 'type'));
        if (!empty($proposal_id)) {
            $where_clause = "(type = 'proposal') and name not in ('foo') and (group_id = '{$proposal_id}')";
        } else {
            $where_clause = "(type = 'proposal') and name not in ('foo')";
        }

        $DB_metadata->where($where_clause);
        $query = $DB_metadata->order_by('name')->get('groups');
        echo $DB_metadata->last_query();
        $results_by_group = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $found_proposal_id = $row->name;
                $results_by_group[$row->group_id] = $found_proposal_id;
            }
        }
        $results = array('by_group' => $results_by_group);
        // var_dump($results);
        return $results;
    }

    /**
     *  Return the list of files and their associated metadata
     *  for a given transaction id
     *
     *  @param integer $transaction_id The transaction to pull
     *
     *  @return [type]   [description]
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_files_for_transaction($transaction_id)
    {
        $DB_metadata = $this->load->database('default', TRUE);

        $file_select_array = array(
            'f.item_id',
            'f.name',
            'f.subdir',
            "DATE_TRUNC('second',t.stime) AT TIME ZONE 'US/Pacific' as stime",
            'f.mtime as modified_time',
            'f.ctime as created_time',
            'f.transaction',
            'f.size',
        );

        $DB_metadata->trans_start();
        $DB_metadata->query("set local timezone to '{$this->local_timezone}';");
        $DB_metadata->select($file_select_array)->from('transactions t')->join('files f', 't.transaction = f.transaction');
        $DB_metadata->where('f.transaction', $transaction_id);
        $DB_metadata->order_by('f.subdir, f.name');
        $files_query = $DB_metadata->get();
        $DB_metadata->trans_complete();
        $files_list = array();

        if ($files_query && $files_query->num_rows() > 0) {
            foreach ($files_query->result_array() as $row) {
                $files_list[$row['item_id']] = $row;
            }
            $file_tree = array();

            $dirs = array();
            foreach ($files_list as $item_id => $item_info) {
                $subdir = preg_replace('|^proposal\s[^/]+/[^/]+/\d{4}\.\d{1,2}\.\d{1,2}/?|i', '', $item_info['subdir']);
                $filename = $item_info['name'];
                $path = !empty($subdir) ? "{$subdir}/{$filename}" : $filename;
                $path_array = explode('/', $path);
                build_folder_structure($dirs, $path_array, $item_info);
            }

            return array('treelist' => $dirs, 'files' => $files_list);
        }
    }

    /**
     *  Retrieve the latest set of transactions for a set of
     *  filter criteria
     *
     *  @param array   $group_id_list group filter list
     *  @param string  $proposal_id   proposal id to reference
     *  @param integer $last_id       The most recent transaction id to consider
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_latest_transactions($group_id_list, $proposal_id, $last_id)
    {
        //if last_id is -1, grab the last transaction so we can display its date as a pointer
        $transaction_list = array();
        $DB_metadata = $this->load->database('default', TRUE);
        $select_array = array(
            'max(f.transaction) as transaction_id',
            'max(gi.group_id) as group_id',
        );
        if (!is_array($group_id_list)) {
            $group_id_list = array($group_id_list);
        }

        $DB_metadata->select('group_id')->where('type', 'proposal')->where('name', $proposal_id);
        $prop_query = $DB_metadata->get('groups', 1);
        $proposal_group_id = $prop_query && $prop_query->num_rows() > 0 ? $prop_query->row()->group_id : -1;

        $raw_transaction_list = array();
        $DB_metadata->trans_start();
        $DB_metadata->query("set local timezone to '{$this->local_timezone}';");
        $DB_metadata->select($select_array)->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
        $DB_metadata->group_by('f.transaction')->order_by('f.transaction desc');
        $DB_metadata->where_in('gi.group_id', $group_id_list);
        $DB_metadata->where('gi.group_id', $proposal_group_id);
        $DB_metadata->order_by('f.transaction desc');
        if ($last_id > 0) {
            $DB_metadata->where_in('gi.group_id', $group_id_list)->where('f.transaction >', $last_id);
        } else {
            $DB_metadata->limit(1);
        }
        $query = $DB_metadata->get();
        $DB_metadata->trans_complete();
        if ($query && $query->num_rows() > 0) {
            //must have some new transactions
            foreach ($query->result() as $row) {
                $raw_transaction_list[] = intval($row->transaction_id);
            }
        }

        sort($raw_transaction_list);

        return $raw_transaction_list;
    }

    /**
     *  Given an internal Pacifica group id, proposal_id and
     *  date range specifier, returns the set of associated
     *  transactions
     *
     *  @param integer $group_id        The Pacifica internal group_id
     *  @param integer $num_days_back   strtotime() parseable string
     *  @param string  $eus_proposal_id filtering proposal id
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_transactions_for_group($group_id, $num_days_back, $eus_proposal_id)
    {
        $transaction_list = array();
        $is_empty = FALSE;
        $DB_metadata = $this->load->database('default', TRUE);
        $results = array();
        $message = '';
        $raw_transaction_list = array();

        $eligible_tx_list = array();

        if (!empty($eus_proposal_id)) {
            //get proposal group id
            $DB_metadata->select('group_id')->where('type', 'proposal')->where('name', $eus_proposal_id);
            $prop_query = $DB_metadata->get('groups', 1);
            $proposal_group_id = $prop_query && $prop_query->num_rows() > 0 ? $prop_query->row()->group_id : -1;

            //go grab the list of eligible tx_id's
            $DB_metadata->select('max(f.transaction) as transaction_id');
            $DB_metadata->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
            $DB_metadata->group_by('f.transaction')->order_by('f.transaction desc');
            $query = $DB_metadata->where('gi.group_id', $proposal_group_id)->get();
            if ($query && $query->num_rows() > 0) {
                foreach ($query->result() as $row) {
                    $eligible_tx_list[] = $row->transaction_id;
                }
            }
        } else {
            $message = 'Select an EUS Proposal and Instrument to load data';

            return array('transaction_list' => $results, 'time_period_empty' => $is_empty, 'message' => $message);
        }

        $select_array = array(
            'max(f.transaction) as transaction_id',
            'max(gi.group_id) as group_id',
        );
        $DB_metadata->trans_start();
        $DB_metadata->query("set local timezone to '{$this->local_timezone}';");
        $DB_metadata->select($select_array)->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
        $DB_metadata->group_by('f.transaction')->order_by('f.transaction desc');
        if ($group_id && $group_id > 0) {
            if (is_array($group_id)) {
                $DB_metadata->where_in('gi.group_id', $group_id);
            } else {
                $DB_metadata->where('gi.group_id', $group_id);
            }
        }
        if (!empty($eligible_tx_list)) {
            $DB_metadata->where_in('f.transaction', $eligible_tx_list);
            $query = $DB_metadata->get();
        }
        $DB_metadata->trans_complete();
        //filter the transactions for date
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $raw_transaction_list[] = $row->transaction_id;
            }
            $today = new DateTime();
            $earliest_date = clone $today;
            $earliest_date->modify("-{$num_days_back} days");
            $DB_metadata->trans_start();
            $DB_metadata->query("set local timezone to '{$this->local_timezone}';");
            $DB_metadata->select('transaction')->where_in('transaction', $raw_transaction_list)->where("stime AT TIME ZONE 'US/Pacific' >=", $earliest_date->format('Y-m-d'));
            $trans_query = $DB_metadata->get('transactions');
            $DB_metadata->trans_complete();
            if ($trans_query && $trans_query->num_rows() > 0) {
                foreach ($trans_query->result() as $row) {
                    $transaction_list[] = $row->transaction;
                }
            }
            if (empty($transaction_list)) {
                $DB_metadata->select('transaction')->where_in('transaction', $raw_transaction_list)->order_by('transaction desc')->limit(3);
                $trans_query = $DB_metadata->get('transactions');
                if ($trans_query && $trans_query->num_rows() > 0) {
                    foreach ($trans_query->result() as $row) {
                        $transaction_list[] = $row->transaction;
                    }
                }
                $is_empty = TRUE;
                $list_size = $trans_query->num_rows();
                $message = "No uploads were found during this time period.<br />The {$list_size} most recent entries for this instrument are below.";
            }
            $results = $this->get_formatted_object_for_transactions($transaction_list);
            $group_list = $this->get_groups_for_transaction($transaction_list, FALSE);
            foreach ($group_list['groups'] as $tx_id => $group_info) {
                $results['transactions'][$tx_id]['groups'] = $group_info;
            }
        }
        return array('transaction_list' => $results, 'time_period_empty' => $is_empty, 'message' => $message);
    }

    /**
     *  For a given set of transaction id's, return the total
     *  size for each transaction
     *
     *  @param array $transaction_id_list the list of transactions to interrogate interrogate
     *                                           interrogate
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_total_size_for_transactions($transaction_id_list)
    {
        if (!is_array($transaction_id_list)) {
            $transaction_id_list = explode(',', $transaction_id_list);
        }
        $DB_metadata = $this->load->database('default', TRUE);
        $select_array = array('transaction as id', 'sum(size) as total_size');
        $DB_metadata->select($select_array)->group_by('transaction')->order_by('transaction');
        $results = array();

        if (!empty($transaction_id_list)) {
            $query = $DB_metadata->where_in('transaction', $transaction_id_list)->get('files');
            if ($query && $query->num_rows() > 0) {
                foreach ($query->result() as $row) {
                    $results[$row->id] = $row->total_size;
                }
            }
        }

        return $results;
    }

    /**
     *  Given a specific set of transaction id's, return the list of
     *  applicable Pacifica internal group id's, grouped by
     *  transaction id
     *
     *  @param array $transaction_id_list simple list of transaction id's
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_groups_for_transaction($transaction_id_list)
    {
        $DB_metadata = $this->load->database('default', TRUE);

        $select_array = array(
            'g.group_id as group_id', 'g.name as group_name',
            'g.type as group_type', 'f.transaction as tx_id',
        );

        $DB_metadata->select($select_array)->distinct();
        $DB_metadata->from('files f')->join('group_items gi', 'gi.item_id = f.item_id');
        $DB_metadata->join('groups g', 'g.group_id = gi.group_id')->order_by('g.name');
        $query = $DB_metadata->where_in('f.transaction', $transaction_id_list)->get();

        $inst_group_pattern = '/Instrument\.(\d+)/i';
        if ($query && $query->num_rows() > 0) {
            $groups = array();
            foreach ($query->result() as $row) {
                if (preg_match($inst_group_pattern, $row->group_type, $inst_matches)) {
                    $groups[$row->tx_id]['instrument_id'] = "{$inst_matches[1]} [MyEMSL Group: {$row->group_id}]";
                    $groups[$row->tx_id]['instrument_name'] = !empty($row->group_name) ? "{$row->group_name}" : '[Not Specified]';
                } elseif ($row->group_type == 'proposal') {
                    $groups[$row->tx_id]['proposal_id'] = $row->group_name;
                    $groups[$row->tx_id]['proposal_name'] = $this->eus->get_proposal_name($row->group_name);
                } else {
                    $groups[$row->tx_id][$row->group_type] = !empty($row->group_name) ? $row->group_name : '[Not Specified]';
                }
            }
            $return_set['groups'] = $groups;
        }

        return $return_set;
    }


    /**
     *  Return the current status for a specific job_id,
     *  formatted properly for conversion to XML
     *
     *  @param integer $job_id a single job id to query
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_formatted_object_for_job($job_id)
    {
        $status = $this->get_job_status(array($job_id), $this->status_list);
        if (array_key_exists($job_id, $status)) {
            $status = $status[$job_id];
        } else {
            return array();
        }
        $time_now = new DateTime();
        $time_string = $time_now->format('Y-m-d H:i:s');
        $job_id = strval($job_id);
        $results = array(
            'transactions' => array(
                $job_id => array(
                    'status' => array(
                        $job_id => array(
                            $status['state'] => array(
                                'jobid' => $job_id,
                                'trans_id' => FALSE,
                                'person_id' => $status['person_id'],
                                'step' => $status['state'],
                                'message' => $this->status_list[$status['state']],
                                'status' => 'SUCCESS',
                            ),
                        ),
                    ),
                ),
            ),
            'times' => array(
                $time_string => $job_id,
            ),
        );

        return $results;
    }

    /**
     *  Return the current status for a set of transaction id's
     *  formatted properly for conversion to XML
     *
     *  @param array $transaction_list a list of transaction id's to query
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_formatted_object_for_transactions($transaction_list)
    {
        $results = array('transactions' => array(), 'times' => array());
        foreach ($transaction_list as $transaction_id) {
            $files_obj = $this->get_files_for_transaction($transaction_id);
            $groups_obj = $this->get_groups_for_transaction($transaction_id);
            if (!empty($files_obj['treelist'])) {
                $file_tree = $files_obj['treelist'];
                $flat_list = $files_obj['files'];
                foreach ($flat_list as $item) {
                    $sub_time = new DateTime($item['stime']);
                    break;
                }
                $time_string = $sub_time->format('Y-m-d H:i:s');

                $results['times'][$time_string] = $transaction_id;

                if (sizeof($files_obj) > 0) {
                    $status_list = $this->get_status_for_transaction('transaction', $transaction_id);
                    if (sizeof($status_list) > 0) {
                        $results['transactions'][$transaction_id]['status'] = $status_list;
                    } else {
                        $results['transactions'][$transaction_id]['status'] = array();
                    }
                    if (sizeof($groups_obj) > 0) {
                        $results['transactions'][$transaction_id]['groups'] = $groups_obj['groups'][$transaction_id];
                    } else {
                        $results['transactions'][$transaction_id]['groups'] = array();
                    }
                }
            }
        }
        if (!empty($results['times'])) {
            arsort($results['times']);
        }

        return $results;
    }

    /**
     *  Get in-progress status updates for a list of job id's
     *  as they are ingested
     *
     *  @param array $job_id_list list of job id's to query
     *  @param array $status_list list of available ingest statuses
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_job_status($job_id_list, $status_list = FALSE)
    {
        if(empty($job_id_list)) {
            return FALSE;
        }
        $status_list = !empty($status_list) ? $status_list : $this->status_list;
        $DB_metadata = $this->load->database('default', TRUE);
        $select_array = array(
            'jobid', 'min(trans_id) as trans_id', 'max(step) as step', 'max(person_id) as person_id',
        );
        if(!empty($job_id_list)) {
            $DB_metadata->where_in('jobid', $job_id_list);
        }
        $DB_metadata->select($select_array)->group_by('jobid');
        $query = $DB_metadata->get('ingest_state');
        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $item = array(
                    'state_name' => $status_list[$row->step],
                    'state' => $row->step,
                    'person_id' => $row->person_id,
                );
                $results[$row->jobid] = $item;
            }
        }

        return $results;
    }

    /**
     *  For a given list of job/transaction id's, return a
     *  list of status entries for each
     *
     *  @param string $lookup_type lookup transactions or jobs?
     *  @param array  $id_list     list of identifiers to use
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_status_for_transaction($lookup_type, $id_list)
    {
        $lookup_types = array(
            't' => 'trans_id', 'trans_id' => 'trans_id',
            'j' => 'jobid', 'job' => 'jobid',
        );
        if (!array_key_exists($lookup_type, $lookup_types)) {
            $lookup_field = 'trans_id';
        } else {
            $lookup_field = $lookup_types[$lookup_type];
        }
        $DB_metadata = $this->load->database('default', TRUE);
        $status_list = array();
        $select_array = array(
            'jobid', 'trans_id', 'person_id', 'step', 'message', 'status',
        );
        $DB_metadata->trans_start();
        $DB_metadata->query("set local timezone to '{$this->local_timezone}';");
        $DB_metadata->select($select_array)->where_in($lookup_field, $id_list);
        $ingest_query = $DB_metadata->get('ingest_state');
        $DB_metadata->trans_complete();
        if ($ingest_query && $ingest_query->num_rows() > 0) {
            foreach ($ingest_query->result_array() as $row) {
                if (intval($row['step']) >= 5 && strtoupper($row['status']) == 'SUCCESS' && $row['trans_id'] != -1) {
                    //need to check for validation progress
                    $DB_metadata->select('transaction')->group_by('transaction');
                    $DB_metadata->having("every(verified = 't')")->where('transaction', $row['trans_id']);
                    $validation_query = $DB_metadata->get('files');
                    if ($validation_query && $validation_query->num_rows() > 0) {
                        //looks like every file has been validated
                        $row['step'] = 6;
                        $row['status'] = 'SUCCESS';
                        $row['message'] = 'verified';
                    }
                }
                $status_list[$row[$lookup_field]][$row['step']] = $row;
            }
        }

        return $status_list;
    }

    /**
     *  For a given job/transaction id, return the
     *  associated instrument
     *
     *  @param string $lookup_type lookup transactions or jobs?
     *  @param array  $id          list of identifiers to use
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_instrument_for_id($lookup_type, $id)
    {
        $lookup_types = array(
            't' => 'trans_id', 'trans_id' => 'trans_id',
            'j' => 'jobid', 'job' => 'jobid',
        );
        if (!array_key_exists($lookup_type, $lookup_types)) {
            $lookup_field = 'trans_id';
        } else {
            $lookup_field = $lookup_types[$lookup_type];
        }
        if ($lookup_field == 'jobid') {
            $tx_info = $this->get_transaction_info($id);
            $id = $tx_info['transaction_id'];
            $lookup_field = 'trans_id';
        }

        $DB_metadata = $this->load->database('default', TRUE);
        $inst_id = FALSE;
        $select_array = array(
            'MAX(f.transaction) as transaction_id',
            'MAX(gi.group_id) as instrument_id',
        );
        $DB_metadata->select($select_array)->from('group_items gi')->join('files f', 'gi.item_id = f.item_id');
        $DB_metadata->having('f.transaction', $id)->group_by('f.transaction')->order_by('f.transaction DESC')->limit(1);
        $query = $DB_metadata->get();

        if ($query && $query->num_rows() > 0) {
            $inst_id = $query->row()->instrument_id;
        }

        return $inst_id;
    }

    /**
     *  For a given job id, return the details of
     *  the associated transaction
     *
     *  @param integer $job_id [description]
     *
     *  @return array
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_transaction_info($job_id)
    {
        $DB_metadata = $this->load->database('default', TRUE);
        $current_step = 0;
        $DB_metadata->trans_start();
        $DB_metadata->query("set local timezone to '{$this->local_timezone}';");
        $query = $DB_metadata->select(array('trans_id as transaction_id', 'step'))->get_where('ingest_state', array('jobid' => $job_id), 1);
        $DB_metadata->trans_complete();
        $transaction_id = -1;
        if ($query && $query->num_rows() > 0) {
            $transaction_id = !empty($query->row()->transaction_id) ? $query->row()->transaction_id : -1;
            $current_step = !empty($query->row()->step) ? $query->row()->step : 0;
        }

        return array('transaction_id' => $transaction_id, 'current_step' => $current_step);
    }
}
