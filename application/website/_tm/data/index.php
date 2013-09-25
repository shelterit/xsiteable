<?php
	
class xs_action_instance extends xs_Action_Webpage {

    public $meta = array (
        'title' => 'Topic Maps'
    ) ;

    private $_data = null ; // array ( 'page' => 2, 'total' => 2, 'records' => 13 ) ;

    //// '{"page":"2","total":2,"records":"13","rows":[{"id":"3","cell":["3","2007-10-02","Client 2","300.00","60.00","360.00","note invoice 3 & and amp test"]},{"id":"2","cell":["2","2007-10-03","Client 1","200.00","40.00","240.00","note 2"]},{"id":"1","cell":["1","2007-10-01","Client 1","100.00","20.00","120.00","note 1"]}],"userdata":{"amount":600,"tax":120,"total":720,"name":"Totals:"}}' ;

    function __construct () {
        parent::__construct() ;
    }

    function _prepare_output () {

    }
    function _init_output () {

    }

    function _render_output ( ) {

        header ( 'Content-type: application/json' ) ;
        echo $this->_data ;
    }


    function ___action () {

        $this->_data = '{"page":"2","total":2,"records":"13","rows":[{"id":"3","cell":["3","2007-10-02","Client 2","300.00","60.00","360.00","note invoice 3 & and amp test"]},{"id":"2","cell":["2","2007-10-03","Client 1","200.00","40.00","240.00","note 2"]},{"id":"1","cell":["1","2007-10-01","Client 1","100.00","20.00","120.00","note 1"]}],"userdata":{"amount":600,"tax":120,"total":720,"name":"Totals:"}}' ;

        $tm = $this->glob->tm ;
        $br = $this->glob->breakdown ;

        return ; // "<xml><one /></xml>" ;

        switch ( $br->section ) {

                case ''       :
                case 'index'  :
                default       :

                    $this->glob->stack->add ( 'xs_topics', $this->glob->tm->query ( array (
                        'sort_by'   => 'm_p_date DESC',
                    ) ) ) ;
                    // $this->glob->stack->add ( 'xs_assocs', $tm->helper_tm2xml ( $tm->get_assocs () ) ) ;

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
 
}