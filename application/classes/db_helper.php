<?php

class db_helper {

    private $db = false ;
    public $page_start  = 0 ;
    public $page_offset = 20 ;

    function __construct ( $db = false ) {
        if ( $db !== false )
            $this->db = $db ;
    }

    function fetchAll ( $sql ) {
        $inst = $this->db->prepare ( $sql ) ;
        $inst->execute() ;
        return $inst->fetchAll ( PDO::FETCH_ASSOC ) ;
    }

    function insert ( $sql ) {
        $inst = $this->db->prepare ( $sql ) ;
        // echo "<pre>".print_r ( $inst, true )."</pre>" ;
        $inst->execute() ;
        return $this->db->lastInsertId() ;
    }

    function exec ( $sql ) {
        $inst = $this->db->prepare ( $sql ) ;
        $inst->execute() ;
    }

    function lookup_names ( $arr = array () ) {
        return $this->fetchAll (
                "SELECT id,name,label FROM xs_topic WHERE name IN ( ".$this->_fieldNames($arr, true).") "
        ) ;
    }

    function delete ( $arr ) {
        if ( !is_array ( $arr ) )
            $arr = array ( $arr ) ;

        foreach ( $arr as $topic_id ) {

            // delete from properties all with parent TOPIC_ID

            $this->exec ( "DELETE FROM xs_property WHERE parent = $topic_id" ) ;

            // delete from topics TOPIC_ID

            $this->exec ( "DELETE FROM xs_topic WHERE id = $topic_id" ) ;

            // delete from assoc all members with TOPIC_ID where count(members) <= 2

        }

        return null ;
    }

    function create ( $arr ) {

        $w = $this->_getProperties ( $arr ) ;
        $a = $this->_removeFields ( $arr, $w ) ;
        $w = $this->_getProperties ( $arr, true ) ;

        $scheme = $this->_fieldNames ( $a ) ;
        $values = $this->_fieldValues ( $a ) ;

        $sql = "INSERT INTO xs_topic ( $scheme ) VALUES ( $values )" ;

        $newtopic = $this->insert ( $sql ) ;
        echo "<br>[$newtopic]=[$sql]<br><br>" ;

        $lut = $this->_associate (
           $this->lookup_names ( $w ),
           'name'
        ) ;

        // print_r ( $lut ) ;

        $sql = "INSERT INTO xs_property ( type, type_name, parent, value ) VALUES " ;
        $counter = 0 ;
        $max = count ( $w ) ;

        foreach ( $w as $field => $value ) {
            $t = 'NULL' ;
            if ( isset ( $lut[$field] ) )
                $t = $lut[$field]['id'] ;

            $sql .= "( '$t', '$field', $newtopic, ".$this->_quote( $value )." )" ;
            if ( ++$counter != $max ) $sql .= " , " ;
        }
        $ret = $this->insert ( $sql ) ;
        // echo "<br>[$ret]=[$sql]<br><br>" ;

        if ( $ret > 0 )
            return $ret ;

        return null ;
    }

    function read ( $table, $criteria = "" ) {

        $start  = $this->page_start ;
        $offset = $this->page_offset ;

        $sql = "SELECT * FROM $table $criteria LIMIT $start,$offset" ;
        $res = $this->db->query ( $sql ) ;

        $res->setFetchMode ( PDO::FETCH_ASSOC ) ;

        $r = $res->fetchAll() ;

        if ( $r )
            return $r ;

        return null ;
    }

    function update ( $table, $arr ) {
        return null ;
    }


	public function query ( $in = array (), $table = 'xs_topic' ) {

            $sql = "SELECT * FROM $table t " ;
            // $sql .= 'INNER JOIN xs_property_data p ON t.topic_id = p.ref_topic_id' ;

            $where = false ;
            $what = array() ;

            if ( isset ( $in['type'] ) ) {
                $t = $in['type'] ;
                $where = true ;
                $what[] = "(type1='$t' OR type2='$t' OR type3='$t')" ;
            }

            if ( isset ( $in['id'] ) ) {
                $t = $in['id'] ;
                $where = true ;
                $what[] = "(id='$t')" ;
            }

            if ( isset ( $in['status'] ) ) {
                $t = $in['status'] ;
                $where = true ;
                $what[] = "(status='$t')" ;
            }

            if ( isset ( $in['parent'] ) ) {
                $t = $in['parent'] ;
                $where = true ;
                $what[] = "(parent='$t')" ;
            }

            if ( isset ( $in['m_p_date'] ) ) {
                $t = $in['m_p_date'] ;
                $where = true ;
                $what[] = "(m_p_date='$t')" ;
            }

            if ( $where ) {
                $c = 0 ;
                foreach ( $what as $item ) {
                    if ( $c++ == 0 )
                        $sql .= ' WHERE ' ;
                    else
                        $sql .= ' AND ' ;
                    $sql .= $item ;
                }

            }

            if ( isset ( $in['sort_by'] ) ) {
                $sql .= " ORDER BY ".$in['sort_by'] ;
            }

            if ( isset ( $in['limit'] ) ) {
                $sql .= " LIMIT ".$in['limit'] ;
            }

            // echo "<div style='padding:1em;margin:1em;background-color:yellow;'>[$sql]<br>" ;
            // die() ;

            try {
                $inst = $this->db->prepare ( $sql ) ;
                $inst->execute() ;

                $res = $inst->fetchAll ( PDO::FETCH_ASSOC ) ;
                // echo '<pre style="background-color:#2af;">[' ; print_r ( $res ) ; echo ']</pre>' ;
            } catch ( exception $ex ) {
                print_r ( $ex ) ;
            }

            $res = $this->associate ( $res, 'id' ) ;

            // echo '<pre style="background-color:#fed;border:solid 3px red;">[' ; print_r ( $res ) ; echo ']</pre>' ;

	if ( count ( $res ) > 0 ) {

                $ids = array() ;
                $idsx = array() ;

                foreach ( $res as $idx => $val )
                    $ids[$idx] = $idx ;
                  //      $idsx[$val['id']] = $idx ;
                 //   }

                if ( count ( $ids ) > 0 ) {

                    $sql = "SELECT * FROM xs_property WHERE parent IN ( ".$this->_fieldValues($ids).") " ;

                    // echo '<pre style="background-color:green;">[' ; print_r ( $ids ) ; echo ']</pre>' ;
                    // echo "<div style='margin-top:20px;background-color:gray;'>[$sql]</div>" ;

                    try {
                        // $props = $this->pdo->fetchAll ( $sql ) ;
                        $inst = $this->db->prepare ( $sql ) ;
                        $inst->execute() ;

                        $props = $inst->fetchAll ( PDO::FETCH_ASSOC ) ;


                    } catch ( exception $ex ) {
                        print_r ( $ex ) ;
                    }

                    // Create an associated array from the result, grouped by parent id
                    $props = $this->associate ( $props, 'parent', true ) ;

                    // echo '<pre style="background-color:green;">[' ; print_r ( $props ) ; echo ']</pre>' ;


                    foreach ( $props as $parent_id => $properties ) {
                        foreach ( $properties as $prop => $val ) {
                            $res[$parent_id][$val['type_name']] = $val['value'] ;
                        }
                    }

                    if ( isset ( $in['filter_by'] ) ) {
                        $t = $in['filter_by'] ;

                    }


                    // echo '<pre style="background-color:orange;">[' ; print_r ( $res ) ; echo ']</pre>' ;


                }

            }

            // echo "</div><br><hr>" ;

            return $res ;

        }



    function associate ( $arr, $id, $multiple = false ) {
        $ret = array () ;
        $new = -1 ;
        foreach ( $arr as $rec ) {
            if ( isset ( $rec[$id] ) ) {
                $new++ ;
                foreach ( $rec as $n => $v ) {
                    // if ( $n != $id ) {
                        if ( $multiple )
                            $ret[$rec[$id]][$new][$n] = $v ;
                        else
                            $ret[$rec[$id]][$n] = $v ;
                    // }
                }
            }
        }
        return $ret ;
    }


    function _quote ( $str ) {
        // echo "<pre style='background-color:gray;'>$str</pre>" ;
        return $this->db->quote ( $str ) ;
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

