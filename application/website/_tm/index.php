<?php
	
class xs_action_instance extends xs_Action_Webpage {

    public $page = array (
        'title' => 'Topic Maps Editor'
    ) ;

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

        $total_topics = $this->pick_count ( $this->glob->data->get ( 'xs_tm-total-topics' ) ) ;
        $total_assocs = $this->pick_count ( $this->glob->data->get ( 'xs_tm-total-assocs' ) ) ;
        $total_props  = $this->pick_count ( $this->glob->data->get ( 'xs_tm-total-props' ) ) ;

        $this->glob->stack->add ( 'xs_tm_stats', array (
            'count_topics' => $total_topics,
            'count_assocs' => $total_assocs,
            'count_props' => $total_props,
        ) ) ;
        
        switch ( $br->section ) {

            case ''       :
            case 'index'  :
            default       :

                    break ;

            case 'topic' :

                $id = $br->id ;

                if ( trim ( $id ) !== '' ) {

                    // specific topic

                    // echo $this->glob->request->method () ;
                    
                    $t = trim ( $this->glob->request->_delete_prop ) ;
                    if ( $t != '' ) {
                        $this->glob->tm->delete_prop_from_topic ( $id, $t ) ;
                        // debug ( $t ) ;
                    }
                    
                    switch ( $this->glob->request->method () ) {
                        case 'GET': break ;
                        case 'DELETE': 
                            $this->glob->tm->delete ( $id ) ;
                            $this->alert ( 'notice', 'Okay', 'You successfully deleted the news item.' ) ;
                            break ;
                        case 'POST' : 
                            
                            $item = new xs_TopicMaps_Topic () ;

                            $fields = $this->glob->request->__get_fields () ;
                            $v_fields = $this->glob->request->__get_fields ( 'k:' ) ;
                            
                            // debug_r ( $this->glob->request ) ;
                            // debug_r ( $v_fields ) ;
                            
                            if ( is_array ( $v_fields ) && count ( $v_fields ) > 0 ) {
                                $res = array () ;
                                $name = '' ;
                                foreach ( $v_fields as $f => $v ) {
                                    $e = explode ( '-', $f ) ;
                                    if ( $e[0] == 'n' ) $name = $v ;
                                    if ( $e[0] == 'v' ) $res[$name] = $v ;
                                }
                                // debug_r ( $res ) ;
                                $fields = array_merge ( $fields, $res ) ;
                            }

                            $item->inject ( $fields ) ;
                            
                            $t = $item->get_as_array () ;
                            $t['who'] = $this->glob->user->id ;
                            
                            // echo "<pre>" ;
                            // print_r ( $t ) ;
                            
                            $topic = $this->glob->tm->query ( array ( 'id' => $t['id'] ) ) ;
                            
                            // print_r ( count ( $topic ) ) ;
                            // print_r ( $topic ) ;
                            
                            // echo "</pre>" ;
                            if ( count ( $topic ) > 0 )
                                $w = $this->glob->tm->update ( $t ) ;
                            else
                                $w = $this->glob->tm->create ( $t ) ;
                                

                            break ;
                    }
                    
                    $this->set_template ('topic') ;

                    $topics = $tm->query ( array ( 'id' => $id ), true ) ;
                    
                    $t = new xs_TopicMaps_Collection ( $topics ) ;
                    $t->resolve_topics ( xs_TopicMaps::$resolve_type ) ;
                    $t->resolve_topics ( xs_TopicMaps::$resolve_parent ) ;
                    // debug_r ( $t ) ;
                    $this->glob->stack->add ( 'xs_topics', $t->__get_array () ) ;

                } else {

                    // list of topics

                    $this->set_template ('topics') ;

                    $this->glob->page->per_page = 15 ;
                    $pager = new xs_Paginator ( $total_topics, $this->glob->page->per_page ) ;
                    $this->glob->page->current_page = $pager->getCurrentPage() ;
                    $this->glob->stack->add ( 'xs_result_pages', $pager->getPages() ) ;

                    $topics = $tm->query ( array (
                        'sort_by'   => 'id ASC',
                        'limit'     => ( $this->glob->page->current_page - 1 ) * $this->glob->page->per_page. ',' . $this->glob->page->per_page
                    ), false ) ;


                    $types = $tm->lookup_types ( $topics, $schema ) ;

                    $lut = $tm->merge ( $topics, $types, $schema ) ;

                    $this->glob->stack->add ( 'xs_topics', $lut ) ;


                }

                /*

                $this->glob->stack->add ( 'xs_content', array ( 'js_topic' => 'function run_auto(){'.
                    $tm->helper_tm2js_topics (
                        $tm->get_topic_by_id ( $id )
                    )
                .'}' ) ) ;

                $a1 = $tm->get_assoc_by_topic ( $id ) ;
                $a2 = $tm->get_occurrence_by_type ( $id ) ;
                $a3 = $tm->get_topic_by_type ( $id ) ;
                $a4 = $tm->get_topic_by_role ( $id ) ;
                $a5 = $tm->get_assoc_by_type ( $id ) ;

                $this->glob->stack->add ( 'xs_data', array (
                    'assoc' => count ( $a1 ),
                    'type' => count ( $a2 ),
                    'occ' => count ( $a3 ),
                    'role' => count ( $a4 ),
                    'assoc_type' => count ( $a5 ),
                ) ) ;

                $this->glob->stack->add ( 'xs_assocs', $tm->helper_tm2xml ( $a1 ) ) ;

                print_r ( $a1 ) ;
                // print_r ( $tm->tm ) ;
                
                $this->glob->stack->add ( 'xs_topics_type', $tm->helper_tm2xml ( $a2 ) ) ;
                $this->glob->stack->add ( 'xs_occurrence_type', $tm->helper_tm2xml ( $a3 ) ) ;
                $this->glob->stack->add ( 'xs_topics_role_type', $tm->helper_tm2xml ( $a4 ) ) ;
                $this->glob->stack->add ( 'xs_topics_assoc_type', $tm->helper_tm2xml ( $a5 ) ) ;

                    // if ( $this->glob->breakdown->special == 'x' )
                    //     print_r ( $this->glob->request->getFields() ) ;

                    break ;
                 *
                 */
                break ;
                case 'assoc' :
        			$this->title = 'Create / update Association' ;
    				$this->template = '../_gui/assoc' ;
                    $id = $this->glob->breakdown->id ;
                    $this->glob->stack->add ( 'xs_content', array ( 'js_assoc' => 'function run_auto(){'.
                        tm_helper_tm2js_assocs (
                            tm_get_assoc_by_id ( $id )
                        )
                    .'}' ) ) ;

                    $this->glob->stack->add ( 'xs_assocs_type', tm_helper_tm2xml ( tm_get_assoc_by_type ( $id ) ) ) ;
                    $this->glob->stack->add ( 'xs_assocs_role_type', tm_helper_tm2xml ( tm_get_assoc_by_role_type ( $id ) ) ) ;
                    $this->glob->stack->add ( 'xs_assocs_assoc_type', tm_helper_tm2xml ( tm_get_assoc_by_type ( $id ) ) ) ;


                    // if ( $this->glob->breakdown->special == 'x' )
                    //     print_r ( $this->glob->request->getFields() ) ;

                    break ;

            }

            // $this->glob->request->__set ( 'output', $this->glob->request->__get ( 'output', 'xhtml' ) ) ;
            
        }
        
        function POST ( $args = null ) {

            $fields = $this->glob->request->getFields() ;

            switch ( $this->glob->breakdown->id ) {

                case 'topic' :

                    $id = $fields['topic_id'] ;
                    $topic = "<request><topic id='$id'>" ;

                    $res = array() ;
                    $keys = array_keys($fields) ;
                    foreach ( $keys as $val ) {
                        $t = explode ( ':', $val ) ;
                        if ( isset ( $t[1] ) )
                            $res[$t[0]][$t[1]] = $fields[$val] ;
                    }
                    // $topic .= print_r ( $res, true ) ;

                    if ( isset ( $res['topic_name'] ) )
                        foreach ( $res['topic_name'] as $idx => $val )
                            $topic .= "<name type='".$res['topic_name_type'][$idx]."'>".$val."</name>" ;

                    if ( isset ( $res['topic_occ_value'] ) )
                        foreach ( $res['topic_occ_value'] as $idx => $val )
                            $topic .= "<occurrence type='".$res['topic_occ_type'][$idx]."'>".$val."</occurrence>" ;

                    if ( isset ( $res['topic_type'] ) )
                        foreach ( $res['topic_type'] as $idx => $val )
                            $topic .= "<type>".$val."</type>" ;


                    $topic .= "</topic></request>" ;


                    // Set up request

                    $url = $this->glob->env->config['host.uri'].$this->glob->env->config['application.uri'].'/topics' ;
                    // echo '['.print_r($this->glob->env->config,true).']' ;
// echo "!" ;
                    $client = new Zend_Http_Client( $url ) ;
                    $client->setParameterPost('xml', urlencode($topic) );
                    // $client->setRawData($topic, 'text/xml');

                    // Send HTTP request to self
                    $response = $client->request('POST');

                    $this->report ( $client, $response ) ;

                    break ;

                case 'assoc' :

                    $id = $fields['assoc_id'] ;
                    $topic = "<request><association" ;
                    if ( trim ( $id ) != '' )
                        $topic .= " id='$id'" ;
                    $topic .= ">" ;

                    if ( isset ( $fields['assoc_type'] ) )
                         $topic .= "<type>".$fields['assoc_type']."</type>" ;

                    $res = array() ;
                    $keys = array_keys($fields) ;
                    foreach ( $keys as $val ) {
                        $t = explode ( ':', $val ) ;
                        if ( isset ( $t[1] ) )
                            $res[$t[0]][$t[1]] = $fields[$val] ;
                    }
                    // print_r ( $fields ) ;
                    // print_r ( $res ) ; die() ;

                    if ( isset ( $res['assoc_ref'] ) )
                        foreach ( $res['assoc_ref'] as $idx => $val )
                            $topic .= "<member ref='$val' role='".$res['assoc_role'][$idx]."' />" ;

                    $topic .= "</association></request>" ;


                    // Set up request

                    // Preshan : the know-guy


                    $url = $this->glob->env->config['host.uri'].$this->glob->env->config['application.uri'].'/associations' ;
                    // echo '['.print_r($this->glob->env->config,true).']' ;

                    $client = new Zend_Http_Client( $url ) ;
                    $client->setParameterPost('xml', urlencode($topic) );
                    // $client->setRawData($topic, 'text/xml');

                    // Send HTTP request to self
                    $response = $client->request('POST');

                    $this->report ( $client, $response ) ;

                    break ;
            }
            // $topic .= "\n\n".print_r ( $client, true ) ;
            // $topic .= "\n\n".print_r ( $response, true ) ;

            // If successful, report

            // If error, report that, too
/*
            echo '<div style="margin:10px;padding:10px;border:solid 14px green;">' ;
            echo '<pre>'.htmlentities($topic).'</pre>' ;
            echo '</div>' ;


            echo '<div style="margin:10px;padding:10px;border:solid 14px blue;">' ;

            echo "Server reply was: " . $response->getStatus() .
            " " . $response->getMessage() . "\n\n";

            echo "Body: " . '<pre>'.htmlentities($response->getBody()).'</pre>' ;

            echo '</div>' ;

            die() ;
 *
 *
  */


            $this->GET () ;
        }

        function DELETE () {

            switch ( $this->glob->breakdown->id ) {
                case 'topic' :
                    $client = new Zend_Http_Client(
                        $this->glob->env->config['host.uri'].
                        $this->glob->env->config['application.uri'].
                        '/topics/'.$this->glob->breakdown->selected
                    ) ;
                    $response = $client->request('DELETE');
                    $this->report ( $client, $response ) ;
                    $this->glob->breakdown->force ( 'selected', '' ) ;
                    break ;
                case 'assoc' :
                    $client = new Zend_Http_Client(
                        $this->glob->env->config['host.uri'].
                        $this->glob->env->config['application.uri'].
                        '/associations/'.$this->glob->breakdown->selected
                    ) ;
                    $response = $client->request('DELETE');
                    $this->report ( $client, $response ) ;
                    $this->glob->breakdown->force ( 'selected', '' ) ;
                    break ;
                default: break ;
            }

            $this->GET () ;
        }

        function report ( $client, $response ) {
            $this->glob->stack->add ( 'xs_content', array (
                'client' => array (
                        'uri' => $client->getUri(true),
                        'cookie_jar' => $client->getCookieJar(),
                ),
                'response' => array (
                        'status' => $response->getStatus(),
                        'message' => $response->getMessage(),
                        'body' => htmlspecialchars( (string) $response->getBody() ),
                )
            ) ) ;

        }
	}