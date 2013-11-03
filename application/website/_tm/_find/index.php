<?php
	
class xs_action_instance extends \xs\Action\Webpage {

    public $page = array (
        'title' => 'Topic Maps Editor :: Find'
    ) ;
    
    private $result = null ;
    private $menu = null ;

    function __construct () {
        parent::__construct() ;
    }

    function ___gui_js () {
        return ( '
    <script src="{$dir/js}/jqgrid/js/jquery.jqGrid.min.js" ></script>
    <script src="{$dir/js}/jqgrid/plugins/jquery.tablednd.js" type="text/javascript"></script>
    <script src="{$dir/js}/jqgrid/plugins/jquery.contextmenu.js" type="text/javascript"></script>
    ' ) ;
    }

    function pick_count ( $incoming ) {
        if ( isset ( $incoming[0] ) && isset ( $incoming[0]['count'] ) )
            return $incoming[0]['count'] ;
        return 0 ;
    }

    function ___action () {

        $js = "" ;

        $schema = array ( 'type1', 'type2', 'type3', 'parent', 'm_c_who', 'm_p_who', 'm_u_who', 'm_d_who' ) ;

        $tm = $this->glob->tm ;
        $br = $this->glob->breakdown ;

        $this->glob->data->register_query ( 'xs', 'xs_tm-total-topics', 'SELECT COUNT(1) AS count FROM xs_topic', '+1 second' ) ;
        $this->glob->data->register_query ( 'xs', 'xs_tm-total-assocs', 'SELECT COUNT(1) AS count FROM xs_assoc', '+1 second' ) ;
        $this->glob->data->register_query ( 'xs', 'xs_tm-total-props', 'SELECT COUNT(1) AS count FROM xs_property', '+1 second' ) ;

        $res = array () ;
        $this->menu = array (
            $this->glob->dir->home.'/_tm/_find/a/m/_1' => 'Find assocs w/members = 1',
            $this->glob->dir->home.'/_tm/_find/a/m/_0' => 'Find assocs w/members = 0',
            $this->glob->dir->home.'/_tm/_find/m/_0' => 'Find orphan assoc_members',
        ) ;
        
        $like = $this->glob->request->__fetch ( '_name', null ) ;
        $type = $this->glob->request->__fetch ( '_type', null ) ;
        $parent = $this->glob->request->__fetch ( '_parent', null ) ;

        $action = $this->glob->request->_action ;
        $incoming = $this->glob->request->__get_fields () ;
        
        // debug_r($this->glob->request->__get_array () ) ;
        // debug_r($this->glob->request->__get_fields () ) ;
        
        switch ( $br->id ) {

            case '_types' :
                $tm = $this->_get_module ( 'topic_maps' ) ;
                $all_types = $this->_get_type () ;
                $all_types_alias = $this->_get_type_alias () ;
                $all_types_cached = $tm->cache->get () ;
                // $all_types_alias_cached = $tm->cache_alias->get () ;
                // debug_r ( $all_types_alias ) ;
                foreach ( $all_types as $module => $types ) {
                    foreach ( $types as $type => $label ) {
                        $typed = null ;
                        $aliased = null ;
                        if ( isset ( $all_types_cached[$type] ) )
                            $typed = $all_types_cached[$type] ;
                        foreach ( $all_types_alias as $m => $ts ) {
                            foreach ( $ts as $t => $a ) {
                                if ( $a == $type )
                                    $aliased = $t ;
                            }
                        }
                        $re = $this->_type->$type ;
                        $res[$type] = array (
                            'type' => $type,
                            'label' => $label,
                            'id' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$typed.'">'.$typed.'</a>',
                            'overriding_alias' => '<b>'.$aliased.'</b>',
                            'type_resolves_to' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$re.'">'.$re.'</a>',
                        ) ;
                    }
                }
                if ( $action == '_delete' ) {
                    $res = $this->delete_data ( 'xs_topic', $res, 'id', $incoming ) ;
                }
                break ;
            case 'p' :
                // echo "P" ;
                switch ( $br->selector ) {
                    case '_0' :
                        if ( $this->glob->request->_delete_all == 'true' ) {
                            // $sql = "SELECT p.id,p.type,p.type_name,p.parent FROM xs_property AS p LEFT JOIN xs_topic AS t ON p.parent = t.id WHERE t.id IS NULL" ;
                            $sql = "DELETE
                                FROM    xs_property 
                                WHERE   parent NOT IN
                                        ( SELECT id FROM xs_topic )" ;
                            // $sql = "DELETE FROM xs_topic WHERE parent = {$parent}" ;
                            $r = $this->glob->tm->fetchAll ( $sql ) ;
                            // debug_r($r,$sql);
                        }
                        $get = $tm->query_prop ( array ( 
                            'orphans' => true
                        ) ) ;
                        foreach ( $get as $prop_id => $prop )
                            $res[$prop_id] = array (
                                'id' => $prop['id'],
                                'type_name' => $prop['type_name'],
                                'parent' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$prop['parent'].'">'.$prop['parent'].'</a>',
                            ) ;

                        if ( $action == '_delete' ) {
                            $res = $this->delete_data ( 'xs_property', $res, 'id', $incoming ) ;
                        }
                        
                        break ;
                    default:
                        break ;
                }
                break ;
            case 't' :
                
                switch ( $br->selector ) {
                    case '_name' :
                        if ( $like !== null ) {
                            if ( $this->glob->request->_delete_all == 'true' ) {
                                $sql = "DELETE FROM xs_topic WHERE name LIKE ('{$type}')" ;
                                $this->glob->tm->exec ( $sql ) ;
                            }
                            $find = $tm->query ( array ( 'name:like' => $like ) ) ;
                            foreach ( $find as $topic_id => $topic )
                                $res[$topic_id] = array (
                                    'id' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$topic['id'].'">'.$topic['id'].'</a>',
                                    'label' => htmlentities($topic['label']),
                                    'name' => $topic['name'],
                                ) ;
                            
                            if ( $action == '_delete' ) {
                                $res = $this->delete_data ( 'xs_topic', $res, 'id', $incoming ) ;
                            }
                        }
                        break ;
                    case '_type' :
                        if ( ! is_numeric ( $type ) ) {
                            $try = $this->_type->$type ;
                            if ( is_numeric ( $try ) )
                                $type = $try ;
                            else   
                                $type = null ;
                        }
                        // debug_r($type);
                        if ( $type !== null ) {
                            debug_r($this->glob->request->_delete_all);
                            if ( $this->glob->request->_delete_all == 'true' ) {
                                $sql = "DELETE FROM xs_topic WHERE type1 = {$type}" ;
                                $this->glob->tm->exec ( $sql ) ;
                            }
                            $find = $tm->query ( array ( 'type' => $type ) ) ;
                            // debug_r($find);
                            foreach ( $find as $topic_id => $topic )
                                $res[$topic_id] = array (
                                    'id' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$topic['id'].'">'.$topic['id'].'</a>',
                                    'type' => $topic['type1'],
                                    'label' => htmlentities ( $topic['label'] ),
                                    'name' => $topic['name'],
                                ) ;
                            if ( $action == '_delete' ) {
                                $res = $this->delete_data ( 'xs_topic', $res, 'id', $incoming ) ;
                            }
                        }
                        break ;
                    case '_parent' :
                        if ( $parent !== null ) {
                            if ( $this->glob->request->_delete_all == 'true' ) {
                                $sql = "DELETE FROM xs_topic WHERE parent = {$parent}" ;
                                $this->glob->tm->exec ( $sql ) ;
                            }
                            $find = $tm->query ( array ( 'parent' => $parent ) ) ;
                            // debug_r($find);
                            foreach ( $find as $topic_id => $topic )
                                $res[$topic_id] = array (
                                    'id' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$topic['id'].'">'.$topic['id'].'</a>',
                                    'parent' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$topic['parent'].'">'.$topic['parent'].'</a>',
                                    'type' => $topic['type1'],
                                    'label' => str_replace ( array (
                                        '&rdquo;',
                                        '&rsquo;',
                                        '&lsquo;',
                                        '&ldquo;',
                                        '&ndash;',
                                     ), ' ', htmlentities ( $topic['value'] ) ),
                                ) ;
                            if ( $action == '_delete' ) {
                                $res = $this->delete_data ( 'xs_topic', $res, 'id', $incoming ) ;
                            }
                        }
                        break ;
                    case '_property_type':
                        
                        $prop_type  = $this->glob->request->__fetch ( '_property_type', null ) ;
                        $prop_value = $this->glob->request->__fetch ( '_property_value', null ) ;
                        
                        $get = $tm->query_prop ( array ( 
                            'property_type' => $prop_type,
                            'property_value' => $prop_value,
                        ) ) ;
                        
                        // debug_r ( $get ) ;

                        foreach ( $get as $prop_id => $topic ) {
                            
                                $res[$topic['topic_id']] = array (
                                    'id' => '<a href="'.$this->glob->dir->home.'/_tm/topic/'.$topic['topic_id'].'">'.$topic['topic_id'].'</a>',
                                    'type' => $topic['type1'],
                                    'label' => str_replace ( array (
                                        '&rdquo;',
                                        '&rsquo;',
                                        '&lsquo;',
                                        '&ldquo;',
                                        '&ndash;',
                                     ), ' ', htmlentities ( $topic['label'] ) ),
                                    'prop_name' => $topic['type_name'],
                                    'prop_value' => $topic['value'],
                                 ) ;
                            
                        }
                        
                        
                        
                        break ;
                    default:
                        break ;
                }
                break ;
            case 'a' :
                // echo "[find assoc]" ;
                switch ( $br->selector ) {
                    case 'm' :
                        // echo "[with members]" ;
                        switch ( $br->specific ) {
                            case '_1' :
                                // echo "[where only one member]" ;
                                $res = array () ;
                                $get = $tm->query_assoc ( array ( 
                                    'member_one' => true
                                ) ) ;
                                // debug_r($get);
                                $types = $tm->lookup_assocs ( $get, 'type' ) ;
                                // debug_r($types);
                                $rest = $tm->lookup_topics ( array_keyify ( $types ) ) ;
                                // debug_r($rest);
                                foreach ( $types as $assoc_id => $assoc_type )
                                    $res[$assoc_id] = array (
                                        'label' => $rest[$assoc_type]['label'],
                                        'id' => $assoc_id,
                                    ) ;
                                
                                if ( $action == '_delete' ) {
                                    $res = $this->delete_data ( 'xs_assoc', $res, 'id', $incoming ) ;
                                }
                                break ;
                            case '_0' :
                                // echo "[where no members]" ;
                                $res = array () ;
                                $get = $tm->query_assoc ( array ( 
                                    'member_none' => true
                                ) ) ;
                                // debug_r ( $res ) ;
                                $types = $tm->lookup_assocs ( $get, 'type' ) ;
                                // debug_r($types);
                                $rest = $tm->lookup_topics ( array_keyify ( $types ) ) ;
                                // debug_r($rest);
                                foreach ( $types as $assoc_id => $assoc_type )
                                    $res[$assoc_id] = array (
                                        'label' => $rest[$assoc_type]['label'],
                                        'id' => $assoc_id,
                                    ) ;
                                if ( $action == '_delete' ) {
                                    $res = $this->delete_data ( 'xs_assoc', $res, 'id', $incoming ) ;
                                }
                                break ;
                            default: 
                                break ;
                        }
                        break ;
                    default: 
                        break ;
                }
                break ;
            case 'm':
                // echo '[m]';
                if ( $br->selector == '_0' ) {
                // echo '[_0]';
                    $res = array () ;
                    $get = $tm->query_assoc ( array ( 
                        'member_orphans' => true
                    ) ) ;
                    $rest = $tm->lookup_topics ( $get ) ;
                    // debug_r($get);
                    // debug_r($rest);
                    foreach ( $get as $topic_id => $assoc_id )
                        $res[$assoc_id] = array (
                            'assoc' => $assoc_id,
                            'label' => 'Lost member of assoc ['.$assoc_id.']',
                            'including_topic' => $rest[$topic_id]['label'],
                            'including_topic_id' => $topic_id,
                        ) ;
                    // debug_r($res);
                    if ( $action == '_delete' ) {
                        $res = $this->delete_data ( 'xs_assoc_member', $res, 'assoc', $incoming ) ;
                    }
                }
                break ;
            default       :
                break ;
        }
        $this->result = $res ;
    }
    
    function delete_data ( $from_table, $res, $pick, $incoming ) {
        $ret = $res ;
        $ids = array () ;
        if ( is_array ( $incoming ) && count ( $incoming > 0 ) ) {
            foreach ( $incoming as $idx => $id ) {
                $id = trim ( $id ) ;
                if ( $id == '' )
                    continue ;
                $ids[$idx] = substr ( $id, 3 ) ;
            }
            // debug_r ( $incoming ) ;
            // debug_r ( $ids ) ;
            
            $z = "( " ;
            $count = 0 ;
            foreach ( $ids as $idx ) {
                if ( $count != 0 ) $z .= ', ' ;
                $z .= "'$idx'" ;
                $count++ ;
                if ( isset ( $res[$idx] ) ) {
                    if ( isset ( $res[$idx][$pick] ) ) {
                        if ( isset ( $res[$idx]['label'] ) ) {
                            $res[$idx]['label'] = '<span class="deleted-item">'.$res[$idx]['label'].'</span>' ;
                        }
                    }
                }
            }
            $z .= " )" ;
            
            $sql = "DELETE FROM {$from_table} WHERE {$pick} IN {$z}" ;
            $this->glob->tm->exec ( $sql ) ;
            // debug($sql);
            
        }
        return $res ;
    }
    
    function ___gui_section1 () {
        // menu
        $html = '<ul>' ;
        foreach ( $this->menu as $l => $m )
            $html .= "<li><a href='{$l}'>{$m}</a></li>" ;
        return $html . '</ul>' ;
    }
    
    function ___gui_section2 () {

        $rnd = rand ( 10000, 99999 ) ;
        // $z = '<table id="tm-data-table-'.$rnd.'" style="width:100%;font-size:0.8em;">
        
        $keys = array () ;
        foreach ( $this->result as $v )
            if ( is_array ( $v ) )
                foreach ( $v as $key => $val )
                    $keys[$key] = $key ;
        
        // debug_r($this->result);
        $html  = '<form id="result-set"><fieldset> 
               <span><input type="checkbox" onclick="$(this).closest(\'fieldset\').find(\':checkbox\').prop(\'checked\', this.checked);" />Check all</span> 
               <span style="margin-left:15px;"><button type="button" style="background-color:#f83;font-size:0.8em;" onclick="xs_delete_from(\'#result-set\')">Delete selected</button></span>
               <hr/>' ;
        $html .= '<table id="tm-data-table-'.$rnd.'" class="data-table"><thead><tr><th></th>' ; // <th>Assoc</th><th>Assoc id</th></tr></thead> <tbody> ' ;
        foreach ( $keys as $key )
            $html .= '<th>'.$key.'</th>' ;
        $html .= '</tr></thead> <tbody> ' ;
        foreach ( $this->result as $l => $m ) {
            $html .= '<tr>' ;
            $n = true ;
            if ( isset ( $m['label'] ) ) {
                // debug_r(substr ( $m['label'], 0, 27 ));
                if ( substr ( $m['label'], 0, 27 ) == '<span class="deleted-item">' )
                    $n = false ;
            }
            $html .= '<td>' ;
            if ( $n ) 
                $html .= "<input type='checkbox' value='{$l}' id='id-{$l}' name='check' />" ;
            $html .= '</td>' ;
            foreach ( $keys as $key )
                $html .= "<td>{$m[$key]}</td>" ;
            // $html .= "   <td>{$m['label']}</td>" ;
            // $html .= "   <td>{$m['id']}</td>" ;
            $html .= '</tr> ' ;
        }
        $html .= '</tbody></table> <script> 
                oTable = $("#tm-data-table-'.$rnd.'").dataTable({ "bJQueryUI": true, "bPaginate": false, "bLengthChange": true });
            </script> ' ;
        return $html . '</fieldset><hr/><button name="action" style="display:none;" value="delete">Delete selected</button></form>' ;
    }
    
}