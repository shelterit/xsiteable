<?php

/*
 * This widget displays a table with what plugins, widgets and modules have
 * registered various data sources and queries. Handy for admin purposes.
 */

class xs_widget_data_manager extends \xs\Action\WidgetController {

    public $meta = array (
        'name' => 'Data and XS Manager widget',
        'description' => 'Admin widget for dealing with data sources and queries',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'admin'
    ) ;
    
    public $settings = array (
        'title' => 'DataManager widget',
        'style' => 'min-height:300px;',
        'color' => 'color-red',
    ) ;
    
    public $types = array ( 
            -10 => 'XS_SYSTEM',
             -8 => 'XS_MODULE',
             -6 => 'XS_PLUGIN',
             -4 => 'XS_WIDGET',
             -2 => 'XS_RESOURCE',
        ) ;
    
    private $html = null ;
    private $data = array () ;

    function ___this () {
        if ($this->html === null )
            $this->html = new \xs\Gui\Html () ;
    }

    // Get the widget initial content
    function GET_content () {
        
        if ( $this->html == null )
            $this->___this () ;
        
        $menu = array ( 
            'index' => 'Index',
            'data' => 'Data query/caching',
            'plugins' => 'Plugins',
            // 'modules' => 'Modules',
            'files' => 'Included files',
            'resources' => 'Registered resources',
            'events' => 'Events',
        ) ;

        return $this->prepare ( 
           $this->html->create_simple_widget ( 'data_manager',  $menu ) 
        ) ;
        
    }
    
    function ___security () {
        $test = $this->glob->request->_cache_reset ;
        if ( $test != '' ) {
            $this->glob->data->reset ( $test ) ;
        }
    }
    
    function GET () {
        
        // make sure we use all the stuff initialized when the widget is active,
        // also when we are getting stuff directly from the resource
        
        $this->___this () ;
    
        switch ( $this->glob->request->data_manager_view ) {
            
            case 'data'    : $this->view_data () ; break ;
            case 'events'  : $this->view_events () ; break ;
            case 'resources' : $this->view_resources () ; break ;
            case 'plugins' : $this->view_plugins () ; break ;
            // case 'modules' : $this->view_modules () ; break ;
            case 'files'   : $this->view_files () ; break ;
            
            case 'index':
            case ''     :
            default     :    ?>

                
                <?php break ;
        } 
        
        return ;
        
        // table configuration options
        $conf = array ( 
            '_export' => array ( 'user_manager', '', $this->glob->dir->api ),
            'id'  =>  $this->html->create_link ( $this->glob->dir->home.'/_tm/topic', '[id]'  ),

        ) ;

        $data = $this->glob->data->get ( 'users-all' ) ;
        
        $data = $this->html->_render_table ( $data, $conf, $render ) ;
        
        debug_r($this->glob->request);
        echo $data ;
        echo " <script>oTable = $('#".$this->html->id."').dataTable({'sScrollY': '400px','bPaginate': false,'bJQueryUI': true,'sPaginationType': 'two_button' });</script>" ;
    }

    function view_plugins () {
        global $xs_stack ;
        $list = $xs_stack->get_plugins () ;
        $res = array () ; $c = 0 ;

        foreach ( $list as $idx => $item ) {
            $res[$idx]['name'] = $item['_']->name ;
            $res[$idx]['type'] = $this->types [ $item['_']->type ] ;
            $res[$idx]['ver'] = isset ( $item['m']['version'] ) ? $item['m']['version'] : 'n/a' ;
            $res[$idx]['category'] = isset ( $item['m']['category'] ) ? $item['m']['category'] : 'misc' ;
            $res[$idx]['id'] = $item['_']->id ;
            $res[$idx]['path'] = $item['_']->file ;
            $res[$idx]['class'] = $item['_']->class ;
        }
        echo $this->render_table ( $res ) ;
    }
    
    function view_modules () {
        global $xs_stack ;
        $list = $xs_stack->get_modules () ;
        debug_r($list);
    }
    
    function view_files () {
        global $xs_stack ;
        $res = array () ;
        $list1 = $xs_stack->get_files () ;
        $list2 = $xs_stack->get_dynamic_files () ;
        $c = -1 ;
        foreach ( $list1 as $id => $path ) {
            $c++ ;
            $res[$c]['id'] = $id ;
            $res[$c]['path'] = $path ;
            $res[$c]['type'] = 'found' ;
        }
        foreach ( $list2 as $id => $path ) {
            $c++ ;
            $res[$c]['id'] = $id ;
            $res[$c]['path'] = $path ;
            $res[$c]['type'] = 'invoked' ;
        }
        echo $this->render_table ( $res ) ;
    }
    
    function view_events () {
        global $xs_stack ;
        $list1 = $xs_stack->get_events () ;
        // $list2 = $xs_stack->get_event_owners () ;
        $res = array () ; $c = -1 ;
        foreach ( $list1 as $event => $members ) {
            if ( is_array ( $members ) && count ( $members > 0 ) ) {
                foreach ( $members as $idx => $member ) {
                    if ( isset ( $member['instance'] ) ) {
                        $i = $member['instance'] ;
                        if ( is_object ( $i ) ) {
                            $c++ ;
                            if ( isset ( $i->_meta ) ) {
                                $res[$c]['id'] = $c ;
                                $res[$c]['event'] = $event ;
                                $res[$c]['name'] = $i->_meta->name ;
                                $res[$c]['class'] = $i->_meta->class ;
                                $res[$c]['type'] = $this->types [ $i->_meta->type ] ;

                            }
                        }
                    }
                }
            } else {
                $c++ ;
                $res[$c]['id'] = $c ;
                $res[$c]['event'] = $event ;
                $res[$c]['name'] = 'n/a' ;
                $res[$c]['class'] = 'n/a' ;
                $res[$c]['type'] = 'n/a' ;
            }
        }
        echo $this->render_table ( $res ) ;
    }
    
    function view_resources () {
        global $xs_stack ;
        $list = $xs_stack->get_resources () ;
        $res = array () ; $c = 0 ;
        foreach ( $list as $idx => $item ) {
            $c++ ;
            if ( isset ( $item['instance'] ) ) {
                $i = $item['instance'] ;
                if ( is_object ( $i ) ) {
                    if ( isset ( $i->_meta ) ) {
                        $res[$c]['id'] = $c ;
                        $res[$c]['uri'] = $idx ;
                        $res[$c]['name'] = $i->_meta->name ;
                        $res[$c]['class'] = $i->_meta->class ;
                        $res[$c]['type'] = $this->types [ $i->_meta->type ] ;
                        // $res[$c]['path'] = $i->_meta->file ;
                    } else {
                        // echo '[no_meta]' ;
                    }
                } else {
                    // echo '[no_object]' ;
                }
            } else {
                // echo '[no_instance]' ;
            }
            // if ( isset ( $item['instance']->_meta ) ) debug_r ( $item['instance']->_meta ) ;
        }
        echo $this->render_table ( $res ) ;
    }
    
    function view_data () {

        $this->data = $this->glob->data->register_info () ;
        
        $id = 'd-m-'.rand(99,999999) ;

        $r  = "<style>#$id { margin:0;padding:0; } </style>\n" ;
        $r .= "<style>#$id tr td { border-left:solid 1px #999;border-bottom:solid 1px #999;margin:0;padding:1px 2px;font-size:10px; } </style>\n" ;

        $r .= "<table id='$id' style='border:solid 3px #ccc;margin:0;padding:0;'><tr style='background-color:#cdf !important;'>" ;
        $r .= "<th>Query id</th><th>Database</th><th>Adapter</th><th>Driver</th><th>Source</th><th>expire</th><th>action</th>" ;
        $r .= "</tr>" ;

        foreach ( $this->data as $what => $data ) {
                        $r .= "<tr><td colspan='7' style='background-color:#eee;'>Section [$what]</td></tr>" ;
            foreach ( $data as $db => $query ) {
                foreach ( $query as $token => $next ) {
                    foreach ( $next as $source => $count ) {
                        $r .= '<tr>' ;
                        $r .= "<td>$token</td><td>$db</td>" ;
                        // print_r ( $db ) ;

                        $a = 'not found ['.$db.']' ;
                        if ( $db !== 0 && $db !== null )
                            $a = $this->glob->data->get_adapter ( $db ) ;
                        if ( is_object ( $a ) )
                            $cl = get_class ( $a ) ;
                        else 
                            $cl = $a ;

                        $nd = 'not found ['.$db.']' ;
                        if ( $db !== 0 && $db !== null )
                            $nd = $this->glob->data->get_native_driver ( $db ) ;
                        if ( is_object ( $nd ) )
                            $cla = get_class ( $nd ) ;
                        else 
                            $cla = $nd ;
                        
                        $cache = $this->glob->data->get_cache ( $token ) ;
                        
                        // if ( $token == 'news-top-20' ) debug_r ( $cache->status () ) ;

                        $age = timed_mili ( $cache->cache_timestamp ) . ' - '.$cache->file ;
                        // $age = timed_mili ( $cache->file_timestamp ) ;
                        // $age = date ( DATE_ATOM, $cache->now_timestamp - $cache->cache_timestamp ) ;
                        
                        $r .= "<td>$cl</td><td>$cla</td><td>$source</td>
                            <td>{$age}</td>
                            <td><button style='margin:0;padding:0;font-size:10px;' type='button' onclick='cache_reset(\"{$token}\");'>clear</button></td>" ;
                        $r .= "</tr>\n" ;
                    }
                }
            }
        }
        $r .= "</table>\n" ;
        
        $html = $this->glob->html ;
        
        $r .= "
            <script type='text/javascript'>
               function cache_reset ( token ) {
                  // 1. grab the link for this report (ajax-link)
                  // 2. ammend it with ?_cache_reset={token}
                  // 3. run the link
               } ;
            </script>
            " ;
        
        echo $r ; //$this->prepare ( $r ) ;
        

    }
    
    
    
    function render_table ( $result = array () ) {

        $rnd = rand ( 10000, 99999 ) ;
 
        $keys = array () ;
        foreach ( $result as $v )
            if ( is_array ( $v ) )
                foreach ( $v as $key => $val )
                    $keys[$key] = $key ;

        $html  = '<form id="result-set"><fieldset>' ;
        $html .= '<table id="tm-data-table-'.$rnd.'" class="data-table"><thead><tr>' ; // <th>Assoc</th><th>Assoc id</th></tr></thead> <tbody> ' ;
        foreach ( $keys as $key )
            $html .= '<th>'.$key.'</th>' ;
        $html .= '</tr></thead> <tbody> ' ;
        foreach ( $result as $l => $m ) {
            $html .= '<tr>' ;
            foreach ( $keys as $key )
                $html .= "<td>{$m[$key]}</td>" ;
            $html .= '</tr> ' ;
        }
        $html .= '</tbody></table> <script> 
                oTable = $("#tm-data-table-'.$rnd.'").dataTable({ "bJQueryUI": true, "bPaginate": false, "bLengthChange": true });
            </script> ' ;
        return $html . '</fieldset><hr/><button name="action" style="display:none;" value="delete">Delete selected</button></form>' ;
    }

}