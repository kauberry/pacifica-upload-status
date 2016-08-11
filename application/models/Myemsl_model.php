<?php

require_once APPPATH.'libraries/Requests.php';
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Myemsl_model                                                            */
/*                                                                             */
/*             functionality dealing with MyEMSL API Access calls, etc.        */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Myemsl_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('myemsl');
        Requests::register_autoloader();
        $this->myemsl_ini = read_myemsl_config_file('general');
    }

    public function get_user_info(){
        $DB_eus = $this->load->database('eus_for_myemsl', true);
        $select_array = array(
            'person_id','network_id','first_name','last_name',
            'email_address','last_change_date','emsl_employee'
        );
        $user_info = array();
        $query = $DB_eus->select($select_array)->get_where('users', array('person_id' => $this->user_id), 1);
        if($query && $query->num_rows() > 0){
            $user_info = $query->row_array();
        }

        $user_info['proposals'] = array();
        $user_info['instruments'] = array();

        $prop_select_array = array(
            'p.proposal_id','p.title','p.group_id','p.accepted_date','p.last_change_date','p.actual_end_date'
        );
        $DB_eus->select($prop_select_array);
        $DB_eus->from('proposals p')->join('proposal_members pm','p.proposal_id = pm.proposal_id');
        $prop_query = $DB_eus->where('pm.person_id',$this->user_id)->get();

        if($prop_query && $prop_query->num_rows() > 0){
            foreach($prop_query->result_array() as $row){
                $prop_id = $row['proposal_id'];
                unset($row['proposal_id']);
                $user_info['proposals'][$prop_id] = $row;
            }
        }

        $prop_id_list = array_map('strval', array_keys($user_info['proposals']));
        $inst_list = array();

        $prop_inst_select_array = array(
            'instrument_id','proposal_id'
        );
        $DB_eus->select($prop_inst_select_array)->where_in('proposal_id', $prop_id_list);
        $prop_inst_query = $DB_eus->get('proposal_instruments');
        if($prop_inst_query && $prop_inst_query->num_rows() > 0){
            foreach($prop_inst_query->result() as $row){
                $user_info['proposals'][$row->proposal_id]['instruments'][] = $row->instrument_id;
                $inst_list[] = $row->instrument_id;
            }
            $inst_list = array_unique($inst_list);
        }

        $inst_query_select_array = array(
            'instrument_id','instrument_name','last_change_date','name_short',
            'eus_display_name','active_sw'
        );
        $DB_eus->select($inst_query_select_array)->where_in('instrument_id', $inst_list);
        $inst_query = $DB_eus->get_where('instruments', array('active_sw' => 'Y'));

        if($inst_query && $inst_query->num_rows() > 0){
            foreach($inst_query->result_array() as $row){
                $inst_id = $row['instrument_id'];
                unset($row['instrument_id']);
                $user_info['instruments'][$inst_id] = $row;
            }
        }
        return $user_info;

    }

    public function get_user_info_old()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
        $basedir = 'myemsl';
        $url_base = 'https://a4.my.emsl.pnl.gov';
        $options = array(
            'verify' => false,
            'timeout' => 60,
            'connect_timeout' => 30,
        );
        $headers = array();

        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            $headers[] = "{$cookie_name}={$cookie_value}";
        }
        $headers = array('Cookie' => implode(';', $headers));
        $session = new Requests_Session($url_base, $headers, array(), $options);

        try {
            $response = $session->get('/myemsl/userinfo');
            $user_info = json_decode($response->body, true);
        } catch (Exception $e) {
            $user_info = array('error' => 'Unable to retrieve User Information');

            return $user_info;
        }

        $DB_myemsl = $this->load->database('default', true);

        //go retrieve the instrument/group lookup table
        $DB_myemsl->like('type', 'Instrument.')->or_like('type', 'omics.dms.instrument');
        $query = $DB_myemsl->get('groups');

        $inst_group_lookup = array();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                if ($row->type == 'omics.dms.instrument') {
                    $inst_id = intval($row->group_id);
                } elseif (strpos($row->type, 'Instrument.') >= 0) {
                    $inst_id = intval(str_replace('Instrument.', '', $row->type));
                } else {
                    continue;
                }
                $inst_group_lookup[$inst_id]['groups'][] = intval($row->group_id);
            }
        }

        $new_instruments_list = array();

        foreach ($user_info['instruments'] as $eus_instrument_id => $inst_info) {
            $new_instruments_list[$eus_instrument_id] = $inst_info;
            if (array_key_exists($eus_instrument_id, $inst_group_lookup)) {
                $new_instruments_list[$eus_instrument_id]['groups'] = $inst_group_lookup[$eus_instrument_id]['groups'];
            } else {
                $new_instruments_list[$eus_instrument_id]['groups'] = array();
            }
        }
        $user_info['instruments'] = $new_instruments_list;

        return $user_info;
    }
}
