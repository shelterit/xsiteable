<?php


    namespace xs\TopicMaps ;
    
    define ( 'XS_LUT_TOPIC', 0 ) ;
    define ( 'XS_LUT_ASSOC', 1 ) ;
    define ( 'XS_LUT_TYPE', 2 ) ;
    
class Engine extends \xs\Events\Plugin {

    private $pdo = false ;

    public $page_start  = 0 ;
    public $page_offset = 20 ;

    private $debug = false ;

    public $schema = array (

        // basic topic properties
        'id'=>true, 'label'=>true, 'type1'=>true, 'type2'=>true, 'type3'=>true, 
        'status'=>true, 'name'=>true, 'parent'=>true, 'scheme'=>true, 
        'value'=>true, 'topicmap'=>true, 

        // basic topic time markers
        'm_c_date'=>true, 'm_c_who'=>true, 'm_p_date'=>true, 'm_p_who'=>true,
        'm_u_date'=>true, 'm_u_who'=>true, 'm_d_date'=>true, 'm_d_who'=>true

    ) ;

    public $assoc_schema = array (

        // basic assoc properties
        'id'=>true, 'type'=>true, 'topicmap'=>true, 

        // basic assoc time markers
        'm_c_date'=>true, 'm_c_who'=>true, 'm_p_date'=>true, 'm_p_who'=>true,
        'm_u_date'=>true, 'm_u_who'=>true, 'm_d_date'=>true, 'm_d_who'=>true

    ) ;

    private $tm_cache = array () ;
    
    
    // ready-made resolvers
    static $resolve_author = array ( 'm_p_who' => array ( 'label' => false, 'name' => 'username=substr($in,5)' ) ) ;
    static $resolve_type = array ( 'type1' => array ( 'label' => false ) ) ;
    static $resolve_parent = array ( 'parent' => array ( 'label' => false, 'type1' => false ) ) ;

    function __construct ( ) {
        parent::__construct();
    }
    
    function add_topic_to_cache ( $topic ) {
        
        // can't add to cache queried topics without an identifier
        if ( ! isset ( $topic['id'] ) )
            return ;
        
        $id = $topic['id'] ;
        foreach ( $topic as $k => $v )
            $this->tm_cache[XS_LUT_TOPIC][$id][$k] = $v ;
        if ( isset ( $topic['type1'] ) ) {
            $type = $topic['type1'] ;
            $this->tm_cache[XS_LUT_TYPE][$type][$id] = $id ;
        }
    }
    
    function add_to_cache ( $topics ) {
        foreach ( $topics as $topic )
            $this->add_topic_to_cache ( $topic ) ;
    }

    function ___datastore_end () {

        // fetch the native PDO driver out of it. Yes, naughty,
        // but we had written native PDO calls into the class and it
        // was easier to keep it as a PDO rather than covert everything
        // to some new fandangled query language that I'm sure will end up
        // on some todo list in my near future. Damn the lack of
        // premature optimization! Damn you!
        
        $this->pdo = $this->glob->data->get_native_driver ( 'xs' ) ;
        
        if ( $this->glob->request->_debug_db == 'true' )
            $this->debug = true ;

    }

    function fetchAll ( $sql ) {

        if ( ! $this->pdo ) return null ; 
        
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[fetch_all, from ".debugPrintCallingFunction()."]" ;
        if ( $this->debug ) echo "<pre style='background-color:#edd'>".print_r ( $sql, true )."</pre>" ;

        $inst = $this->pdo->prepare ( $sql ) ;

        $inst->execute() ;
        $ret = $inst->fetchAll ( \PDO::FETCH_ASSOC ) ;
        
        if ( $this->debug ) {
            // echo "<pre style='background-color:green'>".print_r ( $ret, true )."</pre>" ;
            $dem = array () ;
            $max = 4 ;
            $c = 0 ;
            if ( count ( $ret ) > $max ) {
                foreach ( $ret as $idx => $item ) {
                    if ( $c++ < $max )
                        $dem[$idx] = $item ;
                }
                echo "<pre style='background-color:yellow'><span style='background-color:red'>".count ( $ret )." items, showing top ".count ( $dem )." only!</span> ".print_r ( $dem, true )."</pre>" ;
            } else {
                echo "<pre style='background-color:yellow'>".print_r ( $ret, true )."</pre>" ;
            }      
        }
        
        if ( $this->debug ) echo "</div>" ;
        
        $this->add_to_cache ( $ret ) ;
        
        return $ret ;
        
    }

    function insert ( $sql ) {
        if ( ! $this->pdo ) return null ; 
        
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[insert, from ".debugPrintCallingFunction()."]" ;
        if ( $this->debug ) echo "<pre style='background-color:#edd'>".print_r ( $sql, true )."</pre>" ;
        
        $inst = $this->pdo->prepare ( $sql ) ;
        $inst->execute() ;
        $ret = $this->pdo->lastInsertId() ;
        
        if ( $this->debug ) echo "<pre style='background-color:gray'>".print_r ( $ret, true )."</pre>" ;
        if ( $this->debug ) echo "</div>" ;
        
        return $ret ;
    }

    function exec ( $sql ) {
        if ( ! $this->pdo ) return null ; 
        
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[exec, from ".debugPrintCallingFunction()."]" ;
        if ( $this->debug ) echo "<pre style='background-color:#edd'>".print_r ( $sql, true )."</pre>" ;
        
        $inst = $this->pdo->prepare ( $sql ) ;
        $ret = $inst->execute() ;
        
        if ( $this->debug ) echo "<pre style='background-color:gray'>ret=".print_r ( $ret, true )."</pre>" ;
        if ( $this->debug ) echo "</div>" ;
        
        return $inst ;
    }

    function lookup_names ( $arr = array () ) {
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px green;'>[lookup_names, from ".debugPrintCallingFunction()."]" ;
        if ( ! is_array ( $arr ) )
            $arr = array ( $arr => true ) ;
        $e = $this->fetchAll (
                "SELECT id,name,label FROM xs_topic WHERE name IN ( ".$this->_fieldNames($arr, true).") "
        ) ;
        if ( $this->debug ) echo "</div>" ;
        return $e ;
    }

    function lookup_labels ( $arr = array () ) {
        if ( !$this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px green;'>[lookup_labels, from ".debugPrintCallingFunction()."]" ;
        if ( ! is_array ( $arr ) )
            $arr = array ( $arr => true ) ;
        $sql = "SELECT id,name,label FROM xs_topic WHERE label LIKE ( ".$this->_fieldNames($arr, true).") " ;
        $e = $this->fetchAll ( $sql ) ;
        if ( !$this->debug ) echo "[$sql][".count($e)."] </div>" ;
        return $e ;
    }

    function lookup_topics ( $arr = array () ) {
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px green;'>[lookup_topics, from ".debugPrintCallingFunction()."] " ;
        $sql = "SELECT id,name,label,type1 FROM xs_topic WHERE id IN ( ".$this->_fieldNames($arr, true).") " ;
        // debug($sql, 'sql');
        // echo '['.$sql.']' ;
        
        if ( $this->debug ) echo " [$sql] </div>" ;
        $e = $this->fetchAll ( $sql ) ;
        $r = array () ;

        foreach ( $e as $ee )
            $r[$ee['id']] = $ee ;
        // echo "</div>" ;
        if ( $this->debug ) echo "</div>" ;
        return $r ;
    }

    function lookup_prop ( $arr = array () ) {
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px green;'>[lookup_topics, from ".debugPrintCallingFunction()."]" ;
        // echo "((" ;
        // foreach ( $arr as $prop => $value ) 
            // echo "[$prop]=>[$value] " ;
        // echo ")) " ;
        
        $sql = "SELECT id,parent FROM xs_property WHERE " ; 
        //$this->_fieldNames($arr, true) IN ( ".$this->_fieldNames($arr, true).") " ;
        
        foreach ( $arr as $prop => $value ) {
            $sql .= "$prop = ".$this->_quote($value)." AND" ;
        }
        $sql = substr ( $sql, 0, -4 ) ;
        // echo ")) " ;
        echo 'sql=['.$sql.'] ' ;
        
        $e = $this->fetchAll ( $sql ) ;
        $r = array () ;

        foreach ( $e as $ee )
            $r[$ee['id']] = $ee ;
        // echo "</div>" ;
        if ( $this->debug ) echo "</div>" ;
        return $r ;
    }
    
    function update_property ( $id, $value ) {
        $sql = "UPDATE xs_property SET value = ".$this->_quote ( $value )." WHERE id = '$id' " ;
        echo "<pre style='background-color:yellow;font-size:0.8em;'>$sql</pre>" ;
        $this->exec ( $sql ) ;
    }

    function create_property ( $topic_id, $type_name, $value, $type = 0 ) {
        $sql = "INSERT INTO xs_property ( parent, type, type_name, value ) VALUES ( {$topic_id}, {$type}, '{$type_name}', {$this->_quote($value)} ) ;" ;
        // echo "<pre style='background-color:yellow;font-size:0.8em;'>$sql</pre>" ;
        // $sql = "UPDATE xs_property SET value = ".$this->_quote ( $value )." WHERE id = $id ;" ;
        return $this->insert ( $sql ) ;
    }

    function get_all_prop ( $name ) {
        $sql = "SELECT id,parent,value FROM xs_property WHERE type_name = '$name'" ; 
        return $this->fetchAll ( $sql ) ;
    }

    function get_all_prop_for_topic ( $topic_id, $name ) {
        $sql = "SELECT id,parent,value FROM xs_property WHERE parent=$topic_id AND type_name = '$name'" ; 
        return $this->fetchAll ( $sql ) ;
    }
    
    function delete_prop_from_topic ( $topic_id, $name ) {
        $sql = "DELETE FROM xs_property WHERE parent=$topic_id AND type_name = '$name'" ; 
        return $this->fetchAll ( $sql ) ;
    }
    
    function lookup_assocs_with_one_or_less_member () {
        $sql = "SELECT a.id,count(a.id) FROM xs_assoc AS a LEFT JOIN xs_assoc_member AS m " ;
        $sql .= "ON WHERE m.assoc=a.id GROUP BY m.assoc " ;
        $e = $this->fetchAll ( $sql ) ;
        return $e ;
    }
    /*
    
select s.name
       , p.name
       , count(p.id) 
from   Shop as s
       left join Product as p on p.shop=s.id AND p.status <> '8796107276379'
group by 
       s.id, p.name
     
     
      */  

    function lookup_assocs ( $arr = array (), $pick = null ) {
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px green;'>[lookup_assocs, from ".debugPrintCallingFunction()."]" ;
        $sql = "SELECT id,type FROM xs_assoc WHERE id IN ( ".$this->_fieldNames($arr, true).") " ;
        // echo '['.$sql.']' ;
        
        $e = $this->fetchAll ( $sql ) ;
        $r = $s = array () ;

        foreach ( $e as $ee )
            $r[$ee['id']] = $ee ;

        // debug_r ( $r);
        if ( $pick !== null ) {
            foreach ( $r as $id => $rr )
                if ( isset ( $rr[$pick] ) )
                    $s[$id] = $rr[$pick] ;
            return $s ;
        }
        // echo "</div>" ;
        if ( $this->debug ) echo "</div>" ;
        return $r ;
    }

    function merge ( $topics, $meta_topics, $schema ) {

        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px blue;'>[merge, from ".debugPrintCallingFunction()."]" ;
        foreach ( $topics as $idx => $topic ) {
            $i = $topic['id'] ;
            // echo "\n<br><hr>\n TOPIC '$i': " ;
            foreach ( $schema as $thing ) {
                // echo "[$thing] " ;
                if ( isset ( $topic[$thing] ) ) {
                    $t = $topic[$thing] ;
                    // echo "F=($t) " ;
                    if ( isset ( $meta_topics[$t] ) ) {
                        // echo "M" ;
                        $topics[$idx][$thing.'label'] = $meta_topics[$t]['label'] ;
                    }
                    //$lut[$i] = $topic ;
                    //if ( isset ( $types[$t] ) ) {
                        // echo "found $t: " ;
                        //$lut[$i]['type1label'] = $types[$t]['label'] ;
                    //}
                } // else
                   // $lut[] = $topic ;
            }
        }
        if ( $this->debug ) echo "</div>" ;
        return $topics ;
    }

    function lookup_types ( $topics = array (), $scheme = array () ) {

        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:dotted 1px #777;'>[lookup_types, from ".debugPrintCallingFunction()."]" ;
        
        $schema = array ( 'type1' => true, 'type2' => true, 'type3' => true ) ;

        if ( count ( $scheme ) > 0 )
            foreach ( $scheme as $n )
                $schema[$n] = true ;

        if ( count ( $topics ) < 1 )
            $topics = $this->query ( array (
                'sort_by'   => 'id ASC',
            ) ) ;

        // go through, find all types
        $types = array () ;

        foreach ( $topics as $topic )
            foreach ( $schema as $thing => $val )
                if ( isset ( $topic[$thing] ) )
                    $types[$topic[$thing]] = $topic[$thing] ;

        $ret = $this->lookup_topics ( $types ) ;
        if ( $this->debug ) echo "</div>" ;
        return $ret ;
    }

    function delete ( $arr ) {
        if ( !is_array ( $arr ) )
            $arr = array ( $arr ) ;

        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:solid 2px #999;'>[delete, from ".debugPrintCallingFunction()."]" ;
        
        foreach ( $arr as $topic_id ) {

            // delete all topics that are children of this topic
            $this->exec ( "DELETE FROM xs_topic WHERE parent = $topic_id" ) ;

            // delete from properties all with parent TOPIC_ID
            $this->exec ( "DELETE FROM xs_property WHERE parent = $topic_id" ) ;

            // delete from topics TOPIC_ID
            $this->exec ( "DELETE FROM xs_topic WHERE id = $topic_id" ) ;

            // delete from assoc all members with TOPIC_ID where count(members) <= 2

        }

        if ( $this->debug ) echo "</div>" ;
        
        return null ;
    }

    function pick_in ( $arr ) {
        $ret = array () ;
        foreach ( $arr as $key => $value ) {
            if ( isset ( $this->schema[$key] ) ) $ret[$key] = $value ;
        }
        return $ret ;
    }
    function pick_out ( $arr ) {
        $ret = array () ;
        foreach ( $arr as $key => $value ) {
            if ( ! isset ( $this->schema[$key] ) ) $ret[$key] = $value ;
        }
        return $ret ;
    }
    
    public function assoc_delete_all_members ( $assoc_id = null ) {

        $sql = "DELETE FROM xs_assoc_member WHERE assoc = $assoc_id" ;
        $this->exec ( $sql ) ;
        
    }

    public function assoc_delete_members ( $assoc_id = null, $of_type = array () ) {

        $z = "role IN (" ;
        $count = 0 ;
        
        if ( ! is_array ( $of_type ) )
            $of_type = array ( $of_type ) ;
        
        foreach ( $of_type as $idx ) {
            if ( $count != 0 )
                $z .= ',' ;
            $z .= "$idx" ;
            $count++ ;
        }
        $z .= ')' ;
        
        $sql = "DELETE FROM xs_assoc_member WHERE assoc = $assoc_id AND $z" ;
        
        // print_r ( $sql ) ;
        
        $this->exec ( $sql ) ;
        
    }

    public function assoc_delete_members_not ( $assoc_id = null, $of_type = array () ) {

        $z = "role not IN (" ;
        $count = 0 ;
        
        if ( ! is_array ( $of_type ) )
            $of_type = array ( $of_type ) ;
        
        foreach ( $of_type as $idx ) {
            if ( $count != 0 )
                $z .= ',' ;
            $z .= "$idx" ;
            $count++ ;
        }
        $z .= ')' ;
        
        $sql = "DELETE FROM xs_assoc_member WHERE assoc = $assoc_id AND $z" ;
        
        // print_r ( $sql ) ;
        
        $this->exec ( $sql ) ;
        
    }

    function assoc_create_members ( $assoc_id, $in ) {
        
        if ( ! $this->pdo ) return null ; 
        
        foreach ( $in as $member ) {
            
            $sql = "INSERT INTO xs_assoc_member ( assoc, topic, role ) VALUES ( {$assoc_id}, {$member['topic']}, {$member['role']} );" ;
            $this->exec ( $sql ) ;
            print_r ( $sql ) ; // die () ;
        }
   
        
        
    }
    
    function assoc_create ( $in ) {
        
        if ( ! $this->pdo ) return null ; 
        
        $id = null ;
        $newassoc = null ;
        $ret = null ;
        
        if ( isset ( $arr['id'] ) )
            $id = $arr['id'] ;
        
        //if ( $update )
        //    unset ( $arr['id'] ) ;
/*        
   xs_assoc:
      _type:                  "meta"
      id:                     int.primary
      type:                   int topic.id *
      m_c_date:               time *
      m_c_who:                int topic.id *
      m_p_date:               time *
      m_p_who:                int topic.id *
      m_u_date:               time *
      m_u_who:                int topic.id *
      m_d_date:               time *
      m_d_who:                int topic.id *
   xs_assoc_member:
      _type:                  "meta"
      assoc:                  int assoc.id *
      topic:                  int topic.id *
      role:                   int topic.id *        
*/

        $sql = "INSERT INTO xs_assoc ( type ) VALUES ( {$in['type']} )" ;
        // print_r ( $in ) ; print_r ( $sql ) ; // die () ;
        
        $newassoc = $this->insert ( $sql ) ;
        // $newassoc = 45 ;
        
        $this->assoc_create_members ( $newassoc, $in['members'] ) ;
        
    }

    function create ( $arr, $update = false ) {

        if ( ! $this->pdo ) return null ; 
        // $this->debug = true ;
        
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:solid 2px #999;'>[create, from ".debugPrintCallingFunction()."]" ;
        
        $id = null ;
        $newtopic = null ;
        $ret = null ;
        
        if ( isset ( $arr['id'] ) )
            $id = $arr['id'] ;

        if ( $update )
            unset ( $arr['id'] ) ;
        
        $identity = 1 ;
        // if ( $this->glob->config['framework']['auto_signing'] ) {
            if ( $update ) {
                if ( isset ( $arr['m_u_who'] ) )
                    $identity = $arr['m_u_who'] ;
                elseif ( isset ( $arr['who'] ) ) {
                    $identity = $arr['who'] ;
                    unset ( $arr['who'] ) ;
                }
                if ( isset ( $this->glob->config['framework']['auto_signing'] ) && $this->glob->config['framework']['auto_signing'] && $identity == 1 )
                    $this->glob->user->id ;
                $arr['m_u_date'] = date ( XS_DATE ) ;
                $arr['m_u_who'] = $identity ;
                // debug_r ( $arr, "TM update") ;
            } else {
                if ( isset ( $arr['m_c_who'] ) )
                    $identity = $arr['m_c_who'] ;
                elseif ( isset ( $arr['who'] ) ) {
                    $identity = $arr['who'] ;
                    unset ( $arr['who'] ) ;
                }
                if ( isset ( $this->glob->config['framework']['auto_signing'] ) && $this->glob->config['framework']['auto_signing'] && $identity == 1 )
                    $this->glob->user->id ;
                $arr['m_c_date'] = date ( XS_DATE ) ;
                $arr['m_c_who'] = $identity ;
                $arr['m_p_date'] = date ( XS_DATE ) ;
                $arr['m_p_who'] = $identity ;
                // debug_r ( $arr, "TM create") ;
            }
        // }

        // $tf = $tp = array () ;

        $tf = $this->pick_in ( $arr ) ;
        $tp = $this->pick_out ( $arr ) ;


        // $w = $this->_getProperties ( $arr ) ;

        // var_dump ( $tf ) ; var_dump ( $tp ) ;
        
        // $a = $this->_removeFields ( $arr, $w ) ;
        // $w = $this->_getProperties ( $arr, true ) ;

        $scheme = $this->_fieldNames ( $tf ) ;
        $values = $this->_fieldValues ( $tf ) ;


        if ( $update ) {
            $sql = "UPDATE xs_topic SET ". $this->_fieldNameValues ( $tf ) ." WHERE id = $id" ;
            // if ( !$this->debug )
                $newtopic = $id ;
                $this->exec ( $sql ) ;
        } else {
            $sql = "INSERT INTO xs_topic ( $scheme ) VALUES ( $values )" ;
            // if ( !$this->debug )
                $newtopic = $this->insert ( $sql ) ;
        }
        // if ( $this->debug ) echo "[create:sql]=<pre style='background-color:gray'>".print_r ( $sql, true )."</pre>" ;


        // If we don't have any properties to deal with, just exit here
        if ( count ( $tp ) < 1 ) {
            if ( $this->debug ) echo "</div>" ;
            return $newtopic ;
        }


/*
        $lut = $this->associate (
           $this->lookup_names ( $tp ),
           'name'
        ) ;
*/
        // $this->debug = true ;
        // var_dump ( $lut ) ;
        // if ( $this->debug ) echo "[create:lut]=<pre style='background-color:gray'>".print_r ( $lut, true )."</pre>" ;
        
        $props = $this->fetchAll ( "SELECT * FROM xs_property WHERE (parent = '$id') " ) ;
        
        if ( $this->debug ) echo "[create:find_props]=<pre style='background-color:pink'>".print_r ( $props, true )."</pre>" ;
        if ( $this->debug ) echo "[create:find_props tp]=<pre style='background-color:pink'>".print_r ( $tp, true )."</pre>" ;
        
        $lip = array () ;
        foreach ( $props as $p ) {
            $lip[$p['type_name']] = $p['value'] ;
        }
        if ( $this->debug ) echo "[create:find_props lip]=<pre style='background-color:pink'>".print_r ( $lip, true )."</pre>" ;
        
        $update = $create = array () ;
        
        foreach ( $tp as $idx => $val ) {
            if ( isset ( $lip[$idx] ) )
                $update[$idx] = $val ;
            else
                $create[$idx] = $val ;
        }
        
        if ( $this->debug ) echo "[create:find_props update]=<pre style='background-color:green'>".print_r ( $update, true )."</pre>" ;
        if ( $this->debug ) echo "[create:find_props create]=<pre style='background-color:green'>".print_r ( $create, true )."</pre>" ;
        
        if ( count ( $update ) > 0 ) {
            foreach ( $update as $idx => $t ) {
                $sql = "UPDATE xs_property SET value = ".$this->_quote ( $t )." WHERE parent = $id AND type_name = '$idx';" ;
                $ret = $this->fetchAll ( $sql ) ;
                // if ( $this->debug ) print_r ( $ret ) ;
            }
        }
        
        if ( count ( $create ) > 0 ) {
            $sql = "INSERT INTO xs_property ( type, type_name, parent, value ) VALUES " ;

            $counter = 0 ;
            $max = count ( $create ) ;
            
            $lut = $this->associate (
                $this->lookup_names ( $create ),
                'name'
            ) ;

            foreach ( $create as $field => $value ) {
                $t = 'NULL' ;
                if ( isset ( $lut[$field] ) ) $t = $lut[$field]['id'] ;

                $sql .= "( '$t', '$field', $newtopic, ".$this->_quote( $value )." )" ;
                if ( ++$counter != $max ) $sql .= " , " ;
            }
            $this->insert ( $sql ) ;
        }
        
        /*
        if ( $update ) {
            
            
            // if ( $this->debug ) echo "[create:lut:update]=<pre style='background-color:green'>".print_r ( $sql, true )."</pre>" ;
            // if ( $this->debug ) echo "[create:lut:update]=<pre style='background-color:yellow'>".print_r ( $props, true )."</pre>" ;

            foreach ( $tp as $idx => $t ) {
                $sql = "UPDATE xs_property SET value = ".$this->_quote ( $t )." WHERE parent = $id AND type_name = '$idx';" ;
                $ret = $this->fetchAll ( $sql ) ;
                // if ( $this->debug ) print_r ( $ret ) ;
            }

        } else {
            $sql = "INSERT INTO xs_property ( type, type_name, parent, value ) VALUES " ;

            $counter = 0 ;
            $max = count ( $tp ) ;

            foreach ( $tp as $field => $value ) {
                $t = 'NULL' ;
                if ( isset ( $lut[$field] ) )
                    $t = $lut[$field]['id'] ;

                $sql .= "( '$t', '$field', $newtopic, ".$this->_quote( $value )." )" ;
                if ( ++$counter != $max ) $sql .= " , " ;
            }
            $this->insert ( $sql ) ;
        }
         * 
         */

        // echo "<br>[$ret]($newtopic)=[$sql]<br><br>" ;
        if ( $this->debug ) echo "</div" ;

        return $newtopic ;

    }

    function read ( $table, $criteria = "" ) {

        $start  = $this->page_start ;
        $offset = $this->page_offset ;

        $sql = "SELECT * FROM $table $criteria LIMIT $start,$offset" ;
        $res = $this->pdo->query ( $sql ) ;

        $res->setFetchMode ( PDO::FETCH_ASSOC ) ;

        $r = $res->fetchAll() ;

        if ( $r )
            return $r ;

        return null ;
    }

    function update ( $arr, $check_if_exists = false ) {

        if ( ! $this->pdo ) return null ; 
        
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:solid 2px #999;'>[update, from ".debugPrintCallingFunction()."]" ;
        // if ( $this->debug ) echo "[update]=<pre style='background-color:yellow'>".print_r ( $arr, true )."</pre>" ;

        // if we don't need to check, just delegate
        if ( ! $check_if_exists ) {
            $ret = $this->create ( $arr, true ) ;
            if ( $this->debug ) echo "</div>" ;
            return $ret ;
        }

        // we need to check
        $what = array () ;
        if ( isset ( $arr['id'] ) ) $what = array ( 'id' => $arr['id'] ) ;
        if ( isset ( $arr['name'] ) ) $what = array ( 'name' => $arr['name'] ) ;

        $result = $this->query ( $what ) ;

        if ( count ( $result ) > 0 ) {
            // var_dump ( $result ) ;
            $found = array_pop ( $result ) ;
            $arr['id'] = $found['id'] ;

            $ret = $this->create ( $arr, true ) ;
            if ( $this->debug ) echo "</div>" ;
            return $ret ;
        }

        $ret = $this->create ( $arr, false ) ;
        if ( $this->debug ) echo "</div>" ;
        return $ret ;

    }
    
    public function query_prop ( $in = array (), $topic_lookup = false ) {
        // debug_r ( $in, "INCOMING!" ) ;

        if ( ! $this->pdo ) return null ; 
        
        $where = false ;
        $lut = false ;
        $what = array() ;

        $selector = 'DISTINCT p.id,p.type,p.type_name,p.parent' ;
        $from = 'xs_property p, xs_topic t' ;
        
        
        if ( isset ( $in['select'] ) )
            $selector = $in['select'] ;
        
        if ( isset ( $in['from'] ) )
            $from = $in['from'] ;
        
         if ( isset ( $in['orphans'] ) ) {

                $sql = "SELECT p.id,p.type,p.type_name,p.parent FROM xs_property AS p LEFT JOIN xs_topic AS t ON p.parent = t.id WHERE t.id IS NULL" ;
                // debug($sql,'sql');
                $res = $this->fetchAll ( $sql ) ;
                // return $res ;
                $ret = array () ;
                if ( count ( $res ) > 0 ) {
                    foreach ( $res as $a )
                       $ret[$a['id']] = $a ;
                }
                return $ret ;
         }
         
         if ( isset ( $in['property_type'] ) ) {
             
             $select = 'p.id,p.type,p.type_name,p.parent,p.value,t.id as topic_id,t.type1,t.label' ;
             $from = 'xs_property' ;
             $sql = "SELECT {$select} FROM {$from} AS p LEFT JOIN xs_topic AS t ON p.parent = t.id WHERE " ;
             
             $int = (int) $in['property_type'] ;
             if ( $int > 0 ) {
                 // integer used; type
                 $sql .= "p.type = {$int} " ;
             } else {
                 // integer 0; name
                 $sql .= "p.type_name = '{$in['property_type']}' " ;
             }  
             
            if ( isset ( $in['property_value'] ) && trim ( $in['property_value'] ) != '' ) {
                $sql .= "AND p.value LIKE '{$in['property_value']}' " ;
            }
             
            $sql .= "GROUP BY p.id LIMIT 200 " ;
            
            // debug($sql,'sql');
            $res = $this->fetchAll ( $sql ) ;
            // return $res ;
            $ret = array () ;
            if ( count ( $res ) > 0 ) {
                foreach ( $res as $a )
                    $ret[$a['id']] = $a ;
            }
            return $ret ;
             
         }
    }
    
    public function query_assoc ( $in = array (), $topic_lookup = false ) {
        // debug_r ( $in, "INCOMING!" ) ;

        if ( ! $this->pdo ) return null ; 
        
        $where = false ;
        $lut = false ;
        $what = array() ;

        $selector = 'DISTINCT a.id,a.type,m.topic,m.role' ;
        $from = 'xs_assoc a, xs_assoc_member m' ;
        
        
        if ( isset ( $in['select'] ) )
            $selector = $in['select'] ;
        
        if ( isset ( $in['from'] ) )
            $from = $in['from'] ;

        // $sql = "SELECT $selector FROM xs_topic t " ;
        
        // $what[] = "(a.type='$t')" ;
        
        // check for members first, as it does some lookin' up first, 
        // and may inject stuff in the search query
        
         if ( isset ( $in['member_orphans'] ) ) {

                $sql = "SELECT * FROM xs_assoc RIGHT JOIN xs_assoc_member ON xs_assoc.id = xs_assoc_member.assoc WHERE xs_assoc.id IS NULL" ;
                $res = $this->fetchAll ( $sql ) ;
                // return $res ;
                $ret = array () ;
                if ( count ( $res ) > 0 ) {
                    foreach ( $res as $a )
                       $ret[$a['topic']] = $a['assoc'] ;
                }
                return $ret ;
         }
            
         if ( isset ( $in['member_none'] ) ) {

                $sql = "SELECT * FROM xs_assoc LEFT JOIN xs_assoc_member ON xs_assoc.id = xs_assoc_member.assoc WHERE xs_assoc_member.assoc IS NULL" ;
                $res = $this->fetchAll ( $sql ) ;
                $ret = array () ;
                if ( count ( $res ) > 0 ) {
                    foreach ( $res as $a )
                       $ret[$a['id']] = $a['id'] ;
                }
                return $ret ;
         }
            
         if ( isset ( $in['member_one'] ) ) {

                $sql = "SELECT assoc,count(assoc) AS ac FROM xs_assoc_member GROUP BY assoc" ;
                $res = $this->fetchAll ( $sql ) ;
                // debug_r($res);
                $ret = array () ;
                if ( count ( $res ) > 0 ) {
                    foreach ( $res as $a )
                        if ( isset ( $a['ac'] ) && (int) $a['ac'] == 1 )
                            $ret[$a['assoc']] = $a['ac'] ;
                }
                return $ret ;
         }
            
         if ( isset ( $in['member_id'] ) ) {

            $w = $ww = '' ;
            
            if ( isset ( $in['type'] ) ) {
                
                if ( is_array ( $in['type'] ) ) {
                    // $where = true ;
                    $z = "a.type IN (" ;
                    $count = 0 ;
                    foreach ( $in['type'] as $idx ) {
                        if ( $count != 0 )
                            $z .= ', ' ;
                        $z .= $idx ;
                        $count++ ;
                    }
                    $z .= ')' ;
                    $ww = $z ;
                } else {
                    $z = $in['type'] ;
                    $where = true ;
                    $ww = "a.type=$z" ;
                }
            }
            
            if ( is_array ( $in['member_id'] ) ) {
                $w = true ;
                $z = "m.topic IN (" ;
                $count = 0 ;
                foreach ( $in['member_id'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ', ' ;
                    $z .= $idx ;
                    $count++ ;
                }
                $w = $z .= ")" ;
            } else {
                $t = $in['member_id'] ;
                $where = true ;
                $w = "m.topic=$t" ;
            }
            
            
            
            // $ww = null ;
            // $z = '' ;
            
            // echo "<pre style='background-color:yellow;border:solid 2px blue;padding:5px;margin:5px;'>" ;
            
            $sql = "SELECT m.assoc FROM xs_assoc_member m, xs_assoc a WHERE $ww AND $w AND a.id=m.assoc" ;
            // debug_r ( $sql, 'SQL' ) ;
            $res = $this->fetchAll ( $sql ) ;
            // debug_r ( $res ) ;
            
            if ( is_array ( $res ) && count ( $res ) > 0 ) {
                
                $a = array () ;
                foreach ( $res as $t )
                    $a[$t['assoc']] = $t['assoc'] ;
                
                $lut = $this->lookup_assocs ( $a ) ;
                // debug_r($lut);
                $sql = "SELECT a.id,a.type,m.assoc,m.topic,m.role FROM xs_assoc a INNER JOIN xs_assoc_member m ON a.id=m.assoc AND " ;
                $sql .= "m.assoc IN (" ;
                $count = 0 ;
                foreach ( $a as $idx ) {
                    if ( $count != 0 )
                        $sql .= ',' ;
                    $sql .= $idx ;
                    $count++ ;
                }
                $sql .= ')' ;

                // debug ( $sql, 'SQL' ) ;
                $res = $this->fetchAll ( $sql ) ;
                // debug_r ( $res ) ;
 
                
                foreach ( $res as $t )
                    $lut[$t['assoc']]['members'][] = array ( 'topic' => $t['topic'], 'role' => $t['role'] ) ;

                // debug_r ( $a ) ;
                // debug_r ( $lut ) ;
                    
        // debug_r($where,'111111111111111111111111111111111111111111111111111111');
                return $lut ;
            }
            
            
            if ( count ( $res ) > 0 ) {
                // found a match
                foreach ( $res as $found ) {
                    $in['id'][] = $found['assoc'] ;
                    // break ;
                }
                if ( count ( $res ) > 1 ) {
                    // debug_r($res);
                    // echo '<pre style="padding:2px;margin:2px;color:blue;font-size:0.78em;">'; print_r ( $sql ) ;print_r ( $res ) ;echo '</pre>';
                } else {
                    // echo '[only_one]';
                }
                // echo "<br/>SOLID HIT:" ; print_r ( $in['id'] ) ; echo ' - ' . count ( $res ) . " <br/>" ;
            } else {
                $in['id'] = $in['member_id'] ;
            }
            // print_r ( $res ) ;
            // echo "</pre>" ;
            // die () ;
            // debug($in);
        }
        

        if ( isset ( $in['id'] ) ) {
            // debug($in, 'what is input IDs?');
            if ( is_array ( $in['id'] ) ) {
                $where = true ;
                $z = "a.id IN (" ;
                $count = 0 ;
                foreach ( $in['id'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= $idx ;
                    $count++ ;
                }
                $z .= ')' ;
                $what[] = $z ;
            } else {
                $t = $in['id'] ;
                $where = true ;
                $what[] = "(m.assoc=$t AND a.id=m.assoc)" ;
            }
            // $selector = 'a.id,a.type,m.topic,m.role' ;
            // $from = 'xs_assoc a, xs_assoc_member m' ;
        }
        
        if ( isset ( $in['type'] ) ) {
            if ( is_array ( $in['type'] ) ) {
                $where = true ;
                $z = "a.type IN (" ;
                $count = 0 ;
                foreach ( $in['type'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= $idx ;
                    $count++ ;
                }
                $z .= ')' ;
                $what[] = $z ;
            } else {
                $t = $in['type'] ;
                $where = true ;
                $what[] = "(a.type=$t)" ;
            }
        }
        
        $what[] = 'a.id=m.assoc' ;
        
        // echo "<pre style='background-color:orange;border:solid 2px blue;padding:5px;margin:5px;'>" ;
        $sql = "SELECT $selector FROM $from " ; // WHERE a.id = m.assoc AND m.topic = t.id" ;
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

        // echo "<div style='padding:4px;margin:4px;background-color:green;'>query : [$sql]</div>" ;

        if ( ! $where ) {
            if ( $this->debug ) echo "No 'where' clauses, exiting.</div>" ;
            return ;
        }
        // echo "@" ;
        // print_r ( $sql ) ;
        // debug_r($sql, 'SQL');
        $res = $this->fetchAll ( $sql ) ;
        // debug_r($res);
        
        if ( isset ( $in['return_members'] ) ) {
        
            $ret = array () ;
            foreach ( $res as $idx => $t ) {
                $ret[$t['id']]['id'] = $t['id'] ;
                $ret[$t['id']]['type'] = $t['type'] ;
                $ret[$t['id']]['members'][$t['topic']]['topic'] = $t['topic'] ;
                $ret[$t['id']]['members'][$t['topic']]['role'] = $t['role'] ;
                
            }
            return $ret ;
        }
        
 // print_r ( $res ) ;
        // $sql = 't.label,t.type,t.id FROM xs_topic t, xs_assoc a WHERE a.type = t.id' ;
        // $res2 = $this->fetchAll ( $sql ) ;
        // print_r ( $res2 ) ;
// echo "</pre>" ;        
        return $res ;
        
        // if ( $this->debug )
                // echo "[array]=<pre style='background-color:orange'>".print_r ( $in, true )." </pre>" ;
                // echo "[query]=<pre style='background-color:yellow'>".$sql." </pre>" ;

    }


    public function query ( $in = array (), $assoc_lookup = true ) {

        // if ( isset ( $in['debug'] ) ) print_r($where, 'where');
        if ( ! $this->pdo ) return null ; 
        // echo"!";
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:solid 2px green;'>[<b>query</b>, from ".debugPrintCallingFunction()."]" ;
        
        $selector = '*' ;

        // $this->debug = true ;

        if ( isset ( $in['select'] ) )
            $selector = $in['select'] ;

        $sql = "SELECT $selector FROM xs_topic t " ;
        // $sql .= 'INNER JOIN xs_property_data p ON t.topic_id = p.ref_topic_id' ;

        $where = false ;
        $lut = false ;
        $what = array() ;

        // $this->debug = false ;

        // if ( $this->debug ) 
        // echo "[query]=[$sql]<pre style='background-color:orange'>".print_r ( $in, true )."</pre>" ;

        if ( isset ( $in['type'] ) ) {
            if ( is_array ( $in['type'] ) ) {
                $where = true ;
                $z = "type1 IN (" ;
                $count = 0 ;
                foreach ( $in['type'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= "$idx" ;
                    $count++ ;
                }
                $z .= ')' ;
                $what[] = $z ;
            } else {
                $t = $in['type'] ;
                $where = true ;
                $what[] = "(type1='$t' OR type2='$t' OR type3='$t')" ;
            }
        }

        if ( isset ( $in['id'] ) ) {

            if ( is_array ( $in['id'] ) ) {
                $where = true ;
                $z = "id IN (" ;
                $count = 0 ;
                foreach ( $in['id'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= "'$idx'" ;
                    $count++ ;
                }
                $what[] = $z .= ")" ;
            } else {
                $t = $in['id'] ;
                $where = true ;
                $what[] = "(id='$t')" ;
            }
        }

        if ( isset ( $in['status'] ) ) {
            $t = $in['status'] ;
            $where = true ;
            $what[] = "(status='$t')" ;
        }

        if ( isset ( $in['parent'] ) ) {
            if ( is_array ( $in['parent'] ) ) {
                $where = true ;
                $z = "parent IN (" ;
                $count = 0 ;
                foreach ( $in['parent'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= "'$idx'" ;
                    $count++ ;
                }
                $what[] = $z .= ")" ;
            } else {
                $t = $in['parent'] ;
                $where = true ;
                $what[] = "(parent='$t')" ;
            }
        }

        if ( isset ( $in['name'] ) ) {
            if ( is_array ( $in['name'] ) ) {
                $where = true ;
                $z = "name IN (" ;
                $count = 0 ;
                foreach ( $in['name'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= "'$idx'" ;
                    $count++ ;
                }
                $what[] = $z .= ")" ;
            } else {
                $t = $in['name'] ;
                $where = true ;
                $what[] = "(name='$t')" ;
            }
        }

        if ( isset ( $in['name:like'] ) ) {
            if ( is_array ( $in['name:like'] ) ) {
                $where = true ;
                $z = "name IN (" ;
                $count = 0 ;
                foreach ( $in['name:like'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= "'$idx'" ;
                    $count++ ;
                }
                $what[] = $z .= ")" ;
            } else {
                $t = $in['name:like'] ;
                $where = true ;
                $what[] = "(name LIKE '$t')" ;
            }
        }

        if ( isset ( $in['label:like'] ) ) {
            if ( is_array ( $in['label:like'] ) ) {
                $where = true ;
                $z = "label IN (" ;
                $count = 0 ;
                foreach ( $in['label:like'] as $idx ) {
                    if ( $count != 0 )
                        $z .= ',' ;
                    $z .= "'$idx'" ;
                    $count++ ;
                }
                $what[] = $z .= ")" ;
            } else {
                $t = $in['label:like'] ;
                $where = true ;
                $what[] = "( LOWER ( label ) LIKE '%$t%')" ;
            }
        }

        if ( isset ( $in['between'] ) ) {
            $where = true ;
            $what[] = $in['between'] ;
        }

        if ( isset ( $in['m_p_date'] ) ) {
            $t = $in['m_p_date'] ;
            $where = true ;
            $what[] = "(m_p_date=$t)" ;
        }

        if ( isset ( $in['m_p_who'] ) ) {
            $t = $in['m_p_who'] ;
            $where = true ;
            $what[] = "(m_p_who=$t)" ;
        }

        if ( isset ( $in['m_c_who'] ) ) {
            $t = $in['m_c_who'] ;
            $where = true ;
            $what[] = "(m_c_who=$t)" ;
        }

        if ( isset ( $in['m_c_p_u_who'] ) ) {
            $t = $in['m_c_p_u_who'] ;
            $where = true ;
            $what[] = "(m_c_who=$t OR m_p_who=$t OR m_u_who=$t)" ;
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

        // if ( $this->debug ) echo "<div style='padding:4px;margin:4px;background-color:yellow;'>query : [$sql]</div>" ;

        if ( isset ( $in['debug'] ) ) print_r($where, 'where');
        
        if ( count ( $where ) == 0 ) {
            if ( $this->debug ) echo "No 'where' clauses, exiting.</div>" ;
            return ;
        }

        $res = $this->fetchAll ( $sql ) ;
        
        // debug_r($in);
        // if ( isset ( $in['debug'] ) ) 
            // debug_r($sql,' ');
            // debug_r($res);
        
        /*
        try {
            $inst = $this->pdo->prepare ( $sql ) ;
            if ( !$this->debug )
                $inst->execute() ;

            $res = $inst->fetchAll ( PDO::FETCH_ASSOC ) ;
            // echo '<pre style="background-color:#2af;">[' ; print_r ( $res ) ; echo ']</pre>' ;
        } catch ( exception $ex ) {
            print_r ( $ex ) ;
        }
         * 
         */

        if ( isset ( $in['count'] ) ) {
            switch ( $in['count']['what'] ) {
                case 'sub_topics' :

                    $type = $in['count']['type'] ;

                    $arr = array () ;

                    foreach ( $res as $idx => $topic )
                        $arr[$topic['id']] = true ;

                    $sql = "SELECT id,parent,count(id) FROM xs_topic WHERE parent IN ( ".$this->_fieldNames($arr, true).") GROUP BY parent;" ;
                    // echo '<br><hr>['.$sql.']<br><hr>' ;

                    $e = $this->fetchAll ( $sql ) ;
                    foreach ( $e as $ee )
                        $r[$ee['parent']] = $ee ;

                    // echo '<br><hr>' ; print_r ( $r ) ;

                    foreach ( $res as $idx => $topic ) {
                        $id = $topic['id'] ;
                        if ( isset ( $r[$id] ) )
                            $res[$idx]['count'] = $r[$id]['count(id)'] ;
                        else
                            $res[$idx]['count'] = 0 ;
                    }

                    // print_r ( $res ) ;
                    
                    // echo '<br><hr>' ;
                    
                    // $r = array () ;

                    // foreach ( $e as $ee )
                        // $r[$ee['id']] = $ee ;


                    break ;
                default :
                    break ;
            }

        }

        if ( isset ( $in['lookup_name'] ) ) {
               // echo "!!!" ;
            $lookup = $final = array () ;
            $lut = explode ( ',', $in['lookup_name'] ) ;
            // debug_r ( $lut, 'lookup' ) ;
            // debug_r ( $res, 'lookup' ) ;
            foreach ( $res as $idx => $topic ) {
                foreach ( $lut as $item )
                    if ( isset ( $topic[$item] ) )
                        $lookup[$topic[$item]] = $topic[$item] ;
            }
            // debug_r ( $lookup, 'lookup' ) ;
            
            if ( count ( $lookup > 0 ) ) {
                $names = $this->lookup_topics ( $lookup ) ;
                // print_r ( $names ) ;
                foreach ( $res as $idx => $topic ) {
                    foreach ( $lut as $item )
                    if ( isset ( $topic[$item] ) ) {
                        $lut_id = $topic[$item] ;
                        if ( isset ( $names[$lut_id] ) && is_array ( $names[$lut_id] ) )
                            foreach ( $names[$lut_id] as $n => $p )
                                $res[$idx][$item.'_'.$n] = $p ;
                    }
                }
            }
        }

        // echo "<pre style='padding:10px;margin:10px;border:solid 1px #ccc;'>[$sql]";print_r ( $in ) ;echo "</pre>" ;
        // echo "[associate, from ".debugPrintCallingFunction()."]" ;
        
        if ( isset ( $in['return'] ) && trim($in['return']) == trim('topics') ) {
            if ( $this->debug ) echo "(only returning topics)</div>" ;
            // echo "@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@" ;
            return $res ;
        }

        $res = $this->associate ( $res, 'id' ) ;
        // if ( $this->debug ) echo '<pre style="background-color:#fed;border:solid 3px red;">[' ; print_r ( $res ) ; echo ']</pre>' ;

        if ( count ( $res ) > 0 ) {

            $ids = array() ;
            $idsx = array() ;

            foreach ( $res as $idx => $val )
                $ids[$idx] = $idx ;
              //      $idsx[$val['id']] = $idx ;
             //   }

            if ( count ( $ids ) > 0 ) {

                $sql = "SELECT id,type,type_name,parent,value FROM xs_property WHERE parent IN ( ".$this->_fieldValues($ids).") " ;

                $props = $this->fetchAll ( $sql ) ;

                // Create an associated array from the result, grouped by parent id
                $props = $this->associate ( $props, 'parent', true ) ;

                $finder = 'type_name' ;
                
                if ( $assoc_lookup != true )
                    $finder = 'type' ;

                foreach ( $props as $parent_id => $properties ) {
                    foreach ( $properties as $prop => $val ) {
                        $res[$parent_id][$val[$finder]] = $val['value'] ;
                    }
                    if ( isset ( $res[$parent_id] ) && isset ( $res[$parent_id]['pub_full'] ) ) {

                        // echo "yes, pub_full! <br/>" ;

                        $res[$parent_id]['pub_full'] = str_replace('&nbsp;', ' ', $res[$parent_id]['pub_full'] ) ;

                        if ( ! @simplexml_load_string ( $res[$parent_id]['pub_full'] ) ) {

                            // echo "not well-formed! <br/>" ;
                            // echo "[[".$res[$parent_id]['pub_full']."]]" ;

                            $config = array(
                                       'output-xhtml'   => true,
                                       'wrap'           => 200);

                            // Tidy
                            $tidy = new \tidy;
                            $tidy->parseString($res[$parent_id]['pub_full'], $config, 'utf8');
                            $tidy->cleanRepair();

                            $res[$parent_id]['pub_full'] = tidy_get_body($tidy) ;
                            $res[$parent_id]['pub_full'] = str_replace('&nbsp;', ' ', $res[$parent_id]['pub_full'] ) ;


                            // echo "[[".tidy_get_body($tidy)."]]" ;

                            if ( simplexml_load_string ( $res[$parent_id]['pub_full'] ) ) {
                                // echo "FIXED!<br/>" ;
                            } else {
                                // echo "Still bad!<br/>" ;
                            }

                        }
                    }
                }

                if ( isset ( $in['filter_by'] ) ) {
                    $t = $in['filter_by'] ;

                }
                // echo '<pre style="background-color:orange;">[' ; print_r ( $res ) ; echo ']</pre>' ;

            }

            

        }

        if ( $this->debug ) echo "</div>" ;
        // echo "</div><br><hr>" ;

        return $res ;

    }



    function associate ( $arr, $id, $multiple = false ) {
        $ret = array () ;
        $new = -1 ;
        if ( $this->debug ) echo "<div style='margin:10px;padding:10px;border:solid 2px #999;'>[associate, from ".debugPrintCallingFunction()."]" ;
        // if ( $this->debug ) echo "<div style='margin:0;padding:10px;border:solid 1px orange;'><pre>".print_r ( $arr, true )."</pre></div>" ;
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
       // if ( $this->debug ) echo "<div style='margin:0;padding:10px;border:solid 1px orange;'><pre>".print_r ( $ret, true )."</pre></div>" ;
       if ( $this->debug ) echo "</div>" ;
       return $ret ;
    }


    function _quote ( $str ) {
        // echo "<pre style='background-color:gray;'>$str</pre>" ;
        return $this->pdo->quote ( $str ) ;
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

