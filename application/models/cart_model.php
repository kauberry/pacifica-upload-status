<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Cart Model                                                              */
/*                                                                             */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Cart_model extends CI_Model {
  function __construct(){
    parent::__construct();
    $this->local_timezone = "US/Pacific";
    define("CART_TABLE", 'cart');
    define("ITEMS_TABLE", 'cart_items');
    define("CART_URL_BASE", '/myemsl/cart/download/');
  }
  
  function get_active_carts($eus_id){
    $DB_myemsl = $this->load->database('default',true);
    $select_array = array(
      'cart_id', 'submit_time', 'last_mtime as modification_time',
      'last_email as last_email_time','state',
      'size as size_in_bytes', 'items as item_count'
    );
    $state_array = array(
      'admin_notified' => 'admin',
      'ingest' => 'unsubmitted',
      'amalgam' => 'building',
      'downloading' => 'building',
      'email' => 'available',
      'download_expiring' => 'expired',
      'expiring' => 'expired',
      'expired' => 'expired',
    );
    $cart_list = array();
    $accepted_states = array('amalgam','downloading','email','expired');
    $DB_myemsl->select($select_array)->where('person_id',$eus_id)->order_by('last_mtime desc');
    $query = $DB_myemsl->where_in('state',$accepted_states)->get(CART_TABLE);
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $display_state = array_key_exists($row->state, $state_array) ? $state_array[$row->state] : "unknown";
        $display_size = format_bytes($row->size_in_bytes);
        $cart_list[$display_state][$row->cart_id] = array(
          'cart_id' => $row->cart_id, 'raw_state' => $row->state,
          'display_state' => $display_state, 'size_bytes' => $row->size_in_bytes,
          'display_size' => $display_size, 
          'times' => array(
            'submit' => $row->submit_time,
            'modified' => $row->modification_time,
            'email' => $row->last_email_time
          )
        );
        if($display_state == 'available'){
          $cart_url = CART_URL_BASE."{$this->user_id}/{$row->cart_id}.amalgam/{$row->cart_id}.tar";
          // echo "cart_id => {$row->cart_id} {$cart_url}\n";
          $cart_list[$display_state][$row->cart_id]['download_url'] = $cart_url;
        }
      }
    }
    return $cart_list;
  }
  
  
  
}



?>