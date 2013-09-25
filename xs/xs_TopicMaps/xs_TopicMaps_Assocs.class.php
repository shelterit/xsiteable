<?php

class xs_TopicMaps_Assocs extends xs_Properties {

    public $assoc = array () ;
 
    function __construct ( $incoming = false ) {
        parent::__construct();
        if ( $incoming)
            $this->inject ( $incoming) ;
    }
    
    function member_resolve () {
        foreach ( $this->assoc as $a )
            $a->member_resolve () ;
    }
    
    function inject ( $arr = array () ) {

        if ( is_array ( $arr ) )
            foreach ( $arr as $assoc_id => $assoc )
                $this->assoc[$assoc_id] = new xs_TopicMaps_Assoc ( $assoc ) ;
    }
    function remove_members_of_type ( $type ) {
        foreach ( $this->assoc as $assoc )
            $assoc->remove_members_of_type ( $type ) ;
    }
    
    function get_other_members_of_member_id ( $id ) {
        $ret = array () ;
        if ( is_array ( $this->assoc ) )
            foreach ( $this->assoc as $idx => $assoc ) {
                $m = $assoc->get_members_full ( $type ) ;
            // debug_r($assoc, 'bbbbbbbbbbbbbbb '.$type);
                if ( is_array ( $m ) && count ( $m ) > 0 ){
                    foreach ( $m as $t => $r ) {
                        $ret[$t] = $r ;
                        // debug_r ( $m ) ;
                    }
                }
                // $ret[$idx] = 
            }
            // debug_r($ret, 'llllllllllllllllllll '.$type);
            foreach ( $ret as $idx => $assoc ) {
                if ( count ( $assoc ) < 1 )
                    unset ( $ret[$idx] ) ;
                // $ret[$idx] = $assoc->get_members_full ( $type ) ;
            }
        return $ret ;
    }
    
    function get_members_of_type ( $type, $assoc = null ) {
        $ret = array () ;
        if ( is_array ( $this->assoc ) )
            // debug_r($this->assoc, 'aaaaaaaaaaaaaaaaa '.$type);
            $a = $this->assoc ;
            if ( $assoc !== null ) {
                if ( isset ( $this->assoc[$assoc] ) ) {
                    $a = array ( $this->assoc[$assoc] ) ;
                }
            }
            foreach ( $a as $idx => $assoc ) {
                $m = $assoc->get_members_full ( $type ) ;
            // debug_r($assoc, 'bbbbbbbbbbbbbbb '.$type);
                if ( is_array ( $m ) && count ( $m ) > 0 ){
                    foreach ( $m as $t => $r ) {
                        $ret[$t] = $r ;
                        // debug_r ( $m ) ;
                    }
                }
                // $ret[$idx] = 
            }
            // debug_r($ret, 'llllllllllllllllllll '.$type);
            foreach ( $ret as $idx => $assoc ) {
                if ( count ( $assoc ) < 1 )
                    unset ( $ret[$idx] ) ;
                // $ret[$idx] = $assoc->get_members_full ( $type ) ;
            }
        return $ret ;
    }

    function get_members_not_of_type ( $type ) {
        $ret = array () ;
        if ( is_array ( $this->assoc ) )
            foreach ( $this->assoc as $idx => $assoc ) {
                $ret[$idx] = $assoc->get_members_full_not ( $type ) ;
            }
        return $ret ;
    }

    function __get_array () {
        return array (
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'members' => $this->members
        ) ;
    }

}

