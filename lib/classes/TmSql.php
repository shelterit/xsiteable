<?php
	
    class TmSql {

        private $pdo = null ;

    	function __construct ( $pdo = null ) {
            $this->pdo = $pdo ;
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
                $inst = $this->pdo->prepare ( $sql ) ;
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
                        $inst = $this->pdo->prepare ( $sql ) ;
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



        public function _fetch ( $query ) {

            $result = array() ;
            $statements = explode ( ',', $query ) ;

            $_sort = false ;
            $_sort_order = 'ASC' ;
            $_sort_field = false ;
            $_limit = false ;
            $_topic_type = false ;
            $_what = array () ;
            $_props = array () ;

            foreach ( $statements as $idx => $statement ) {

                $statement = trim ( $statement ) ;
                $result[$idx]['action'] = 'get' ;

                $part = explode ( ' ', $statement ) ;

                foreach ( $part as $c => $notion ) {

                    $first = substr ( $notion, 0, 1 ) ;
                    $rest = substr ( $notion, 1 ) ;

                    switch ( $first ) {
                        case '#' :  $_where[] = ' ( type1="'.$rest.'" OR type2="'.$rest.'" OR type3="'.$rest.'" )' ; break ;
                                    $notion = 'topic_type '.$rest ; $_topic_type = $rest ; break ;
                        case '!' : $_props[] = $rest ; $notion = 'occurrence of_type '.$rest ; break ;
                    }

                    switch ( $notion ) {
                        case 'by' : break ;
                        case 'sort' : $result[$idx]['action'] = 'sort_by' ; break ;
                        case 'limit' : $result[$idx]['action'] = 'limit_by' ; $_limit = strstr ( $rest, '-', '-' ) ; break ;
                        default :
                            $result[$idx][$c] = $notion ;
                            break ;
                    }
                }

            }
/*
            $sql = "SELECT * FROM xs_topic WHERE topic_id=53" ;
            echo '<pre>[' ; print_r ( $this->glob->pdo->fetchAll ( $sql ) ) ; echo ']</pre>' ;

            $sql = "SELECT * FROM xs_topic INNER JOIN xs_property_data ON xs_topic.topic_id=xs_property_data.ref_topic_id WHERE xs_topic.topic_id=53 ORDER BY xs_topic.date_created LIMIT 0,3" ;
            echo '<pre>[' ; print_r ( $this->glob->pdo->fetchAll ( $sql ) ) ; echo ']</pre>' ;
*/

// print_r ( $_where ) ; die() ;

            $sql = 'SELECT * FROM xs_topic' ;

            foreach ( $result as $idx => $what ) {

                if ( $what['action'] == 'get' )
                    $sql .= 'SELECT * FROM xs_topic' ;

                foreach ( $what as $i => $step ) {

                    if ( $i != 'action' ) {

                        switch ( $step ) {
                            case 'action': break ;
                            case 'topic_type':
                        }

                    }


                }

            }
/*
            $sql = " WHERE ( type1='$rest' OR type2='$rest' OR type3='$rest' )" ;
            echo '<pre>[' ; print_r ( $this->glob->pdo->fetchAll ( $sql ) ) ; echo ']</pre>' ;

            $sql = "SELECT * FROM xs_topic INNER JOIN xs_property_data ON xs_topic.topic_id=xs_property_data.ref_topic_id WHERE xs_topic.topic_id=53 ORDER BY xs_topic.date_created LIMIT 0,3" ;
            echo '<pre>[' ; print_r ( $this->glob->pdo->fetchAll ( $sql ) ) ; echo ']</pre>' ;
*/

            return $result ;
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
            return $this->pdo->quote ( $str ) ;
        }

		function _fieldNames ( $arr ) {
			$sql = "" ;
			foreach ( $arr as $field => $value )
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


    }
