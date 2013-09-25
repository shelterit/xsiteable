<?php

class xs_TopicMaps_Assoc extends xs_Properties {

    public $id = null ;
    public $type = null ;
    public $type_label = null ;
    
    public $members = array () ;
 

    function __construct ( $incoming = false ) {
        parent::__construct();
        if ( $incoming)
            $this->inject ( $incoming) ;
    }
    
    function has_other_members_than_id ( $id ) {
        foreach ( $this->members as $idx => $member )
            if ( $member['topic'] != $id )
                return true ;
         return false ;
    }
    
    function remove_members_of_type ( $type ) {
        if ( is_array ( $this->members ) && count ( $this->members ) > 0 ) {
            foreach ( $this->members as $idx => $member )
                if ( $member['role'] == $type )
                    unset ( $this->members[$idx] ) ;
        }
    }
    
    function remove_members_not_of_type ( $type ) {
        if ( is_array ( $this->members ) && count ( $this->members ) > 0 ) {
            foreach ( $this->members as $idx => $member )
                if ( $member['role'] != $type )
                    unset ( $this->members[$idx] ) ;
        }
    }
    
    function get_members ( $type = null ) {
        
        if ( $type == null )
            return $this->members ;
        
        $ret = array () ;
        // debug_r($this->members);
        if ( is_array ( $this->members ) && count ( $this->members ) > 0 ) {
            foreach ( $this->members as $idx => $member )
                if ( $member['role'] == $type )
                    $ret[$member['role']] = $idx ;
        }
        return $ret ;
    }
    
    function get_members_full ( $type = null ) {
        
        if ( $type == null )
            return $this->members ;
        
        $ret = array () ;
        
        foreach ( $this->members as $idx => $member )
            if ( $member['role'] == $type )
                $ret[$idx] = $member ;
        
        return $ret ;
    }
    
    function get_members_full_not ( $type = null ) {
        
        if ( $type == null )
            return array () ;
        
        $ret = array () ;
        
        foreach ( $this->members as $idx => $member )
            if ( $member['role'] != $type )
                $ret[$idx] = $member ;
        
        return $ret ;
    }
    
    function member_resolve ( ) {
        
        $check = $this->members ;
        
        if ( isset ( $this->type ) ) $check[$this->type] = $this->type ;
        
        foreach ( $this->members as $idx => $member )
            $check[$member['role']] = $member['role'] ;
        
        $lut = $this->glob->tm->lookup_topics ( $check ) ;

        foreach ( $this->members as $idx => $member ) {
            
            if ( isset ( $lut[$member['role']] ) )
                $this->members[$idx]['role_label'] = $lut[$member['role']]['label'] ;
            
            if ( isset ( $lut[$idx] ) )
                $this->members[$idx]['label'] = $lut[$idx]['label'] ;
        }
        
        if ( isset ( $this->type ) && isset ( $lut[$this->type] ) ) {
            $this->type_label = $lut[$this->type]['label'] ;
        }
    }
    
    function inject ( $arr = array () ) {
            $this->piece_inject ( $arr ) ;
            return ;
        // debug_r($arr);
        // $test = current ( $arr ) ;
        // reset ( $arr ) ;
        if ( isset ( $arr['id'] ) ) {
            debug_r($arr, "!!!!!!!!!!!!!!!!!!!!");
            foreach ( $arr as $idx => $item ) {
                $this->piece_inject ( $item ) ;
            }
         } else
            $this->piece_inject ( $arr ) ;
    }

    function piece_inject ( $arr ) {
        
        // debug($arr,"piece inject");
        $status = false ;
        
        if ( isset ( $arr['type'] ) ) {
            $status = true ;
            $this->type = $arr['type'] ; // echo "t ";
        }

        if ( $this->type == false && isset ( $arr['type1'] ) ) {
            $status = true ;
            $this->type = $arr['type1'] ;// echo "t1 ";
        }

        if ( $this->id == false && isset ( $arr['id'] ) ) {
            $status = true ;
            $this->id = $arr['id'] ;// echo "id ";
        }

        if ( isset ( $arr['topic'] ) && isset ( $arr['role'] )) {
            $status = true ;
            // echo "t&r ";
            if ( ! isset ( $arr['label'] ) ) {
                $arr['label'] = '' ;// echo "l ";
            }
            $this->members[$arr['topic']] = array (
                'role' => $arr['role'],
                'topic' => $arr['topic'],
                'label' => $arr['label']
            ) ;
        }
        
        if ( isset ( $arr['members'] ) && is_array ( $arr['members'] ) ) {
            $status = true ;
            // echo "m ";
            foreach ( $arr['members'] as $count => $member ) {
                // echo "m{$count} ";
                // debug_r($member, "injecting [{$count}]");
                if ( isset ( $member['role'] ) && isset ( $member['topic'] ) ) {
                    $t = trim ( $member['topic'] ) ;
                    // echo "#" ;
                    $this->members[$t]['role'] = $member['role'] ;
                    $this->members[$t]['label'] = isset ( $member['label'] ) ? $member['label'] : '' ;
                }
            }
        }
        
        if ( ! $status && is_array ( $arr ) && count ( $arr ) > 0 ) {
            foreach ( $arr as $idx => $item ) {
                $this->piece_inject ( $item ) ;
            }
        }
    }

    function __get_array () {
        return array (
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'members' => $this->members
        ) ;
    }

    function _quote ( $str ) {
        return "'$str'" ;
    }

    function _fieldNames ( $arr, $quoted = false ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                if ( $quoted )
                    $sql .= "'$field', " ;
                else
                    $sql .= $field . ", " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 2 ) ;
            return $sql ;
    }

    function _fieldValues ( $arr ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                    $sql .= $this->_quote ( $value ) . ", " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 2 ) ;
            return $sql ;
    }

    function _fieldNameValues ( $arr ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                    $sql .= $field . "=".$this->_quote ( $value ).", " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 2 ) ;
            return $sql ;
    }

    function _fieldNameValuesAnd ( $arr ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                    $sql .= $field . "=".$this->_quote ( $value )." AND " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 5 ) ;
            return $sql ;
    }

    function _fieldNameValuesOr ( $arr, $reverse = false ) {
            $sql = "" ;
            if ( $reverse ) {
                    foreach ( $arr as $field => $value )
                            $sql .= $field . "=".$this->_quote ( $value )." OR " ;
            } else {
                    foreach ( $arr as $value => $field )
                            $sql .= $field . "=".$this->_quote ( $value )." OR " ;
            }
            $sql = substr ( $sql, 0, strlen ($sql) - 4 ) ;
            return $sql ;
    }

    function _removeFields ( $arr, $who ) {
        $res = array () ;
        foreach ( $arr as $idx => $val ) {
            $keep = true ;
            foreach ( $who as $i => $v )
                if ( $idx == $i )
                    $keep = false ;
            if ( $keep )
                $res[$idx] = $val ;
        }
        return $res ;
    }

    function _getProperties ( $arr, $clear = false, $prepend = 'p:' ) {

        $res = array () ;
        $s = strlen ( $prepend ) ;

        foreach ( $arr as $idx=>$val )
            if ( substr ( $idx, 0, $s ) == $prepend ) {
                if ( $clear )
                    $res[substr ( $idx, $s )] = $val ;
                else
                    $res[$idx] = $val ;
            }
        return $res ;
    }



}

