<?php
require_once APPPATH.'libraries/Requests.php';
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Myemsl_model                                                            */
/*                                                                             */
/*             functionality dealing with MyEMSL API Access calls, etc.        */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class API_model extends CI_Model {
  
  function __construct(){
    parent::__construct();
    $this->load->helper('myemsl');
    Requests::register_autoloader();
    $this->myemsl_ini = read_myemsl_config_file('general');
    $this->load->database('default');
  }
  
  function get_available_group_types($filter = ""){
    if(!empty($filter)){
      $this->db->like('type',$filter);
    }
    $results = array();
    $query = $this->db->select('type')->distinct()->order_by('type')->get('groups');
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $results[] = $row->type;
      }
    }
    return $results;
  }

  function search_by_metadata($metadata_pairs){
    //check for valid types
    $clean_pairs = $this->clean_up_metadata_pairs($metadata_pairs);
    
    //build the search query for group_id list
    $found_group_ids = array();
    $group_id_relationships = array();
    foreach($clean_pairs as $field => $value){
      if(!array_key_exists($field,$group_id_relationships)){
        $group_id_relationships[$field] = array();
      }
      $where_array = array(
        'type' => $field, 'name' => $value
      );
      $this->db->select('group_id')->where($where_array);
      $group_query = $this->db->get('groups',1);
      if($group_query && $group_query->num_rows() > 0){
        foreach($group_query->result() as $group_row){
          $group_id_relationships[$field][] = $group_row->group_id;
          $found_group_ids[] = $group_row->group_id;
        }
      }
    }
    
    //now use the group_ids to find the items related to each one
    $item_results = array();
    $item_query = $this->db->where_in('group_id', $found_group_ids)->get('group_items');
    if($item_query && $item_query->num_rows()>0){
      foreach($item_query->result() as $item_row){
        if(!array_key_exists($item_row->group_id)){
          $item_results[$item_row->group_id] = array();
        }
        $item_results[$item_row->group_id][] = $item_row->item_id;
      }
      $item_list = array_shift($item_results);
      foreach($item_results as $filter){
        $item_list = array_intersect($item_list, $filter);
      }
    }
    
    
    //now that we have a list of items shared amongst the selection criteria
    // let's get the info for all of them
  }


  private function get_metadata_entries($item_results){
    // $this->db->
  }
  
  
  private function clean_up_metadata_pairs($pairs){
    //check for valid types
    $cleaned_types = array();
    $clean_pairs = array();
    $valid_types = $this->get_available_group_types();
    foreach($pairs as $field => $value){
      if(!in_array($field, $valid_types)){
        //hmmm... try stripping off group.* if it exists
        if(preg_match('/group\.(.+)/i',$field,$m)){
          $new_field = $m[1];
          if(in_array($new_field, $valid_types)){
            $cleaned_types[$field] = $new_field;
            continue;
          }
        }
      }else{
        $cleaned_types[$field] = $field;
      }
    }
    foreach($pairs as $field => $value){
      $clean_pairs[$cleaned_types[$field]] = $value;
    }
    return $clean_pairs;
  }

}
?>