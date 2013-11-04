<?php
	
class xs_action_instance extends \xs\Action\Generic {

    public $metadata = array (
        'title' => 'Topic Maps',
        'template' => '/_gui/index'
    ) ;

    function GET () {

        $js = "" ;

        $tm = $this->glob->tm ;
        $br = $this->glob->breakdown ;

        switch ( $br->section ) {

                case ''       :
                case 'index'  :
                default       :

                    $this->glob->stack->add ( 'xs_topics', $tm->helper_tm2xml ( $tm->get_topics () ) ) ;
                    $this->glob->stack->add ( 'xs_assocs', $tm->helper_tm2xml ( $tm->get_assocs () ) ) ;

                    break ;

            case 'topic' :

                $this->metadata['template'] = '/_gui/topic' ;

                $id = $br->id ;

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

            $this->glob->request->__set ( 'output', $this->glob->request->__get ( 'output', 'xhtml' ) ) ;
            
        }
        
        function POST () {

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