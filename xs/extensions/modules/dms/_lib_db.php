<?php
 
class dms_lib_db {
    
    private $glob = null ;
    
    function __construct ( $glob = null ) {
        $this->glob = $glob ;
    }
    
    function find_db_properties ( $type, $property ) {
        $ret = array () ;
        $props = $this->glob->tm->get_all_prop_for_topic_type ( $type, $property ) ;
        // debug_r ( $props, 'found relative path props of type: ' . $type ) ;
        foreach ( $props as $a => $b ) {
            // $ret[$b['parent']]['parent'] = $b['id'] ;
            $ret[$b['parent']]['id'] = $b['id'] ;
            $ret[$b['parent']]['value'] = $b['value'] ;
        }
        return $ret ;
    }

    function create_label ( $str ) {
        $e = explode ( '/', $str ) ;
        return trim ( substr ( end ( $e ), 0, -4 ) ) ;
    }
    
  
}
