<?php

class xs_widget_document_control_owner extends xs_Action_Widget_Controller {

    // Generic metadata
    public $meta = array(
        'name' => 'Document Control :: Owner',
        'description' => 'Document Control widget for ownership',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'admin',
    );

    // Widget settings (default values, might be manually overridden)
    public $settings = array (
        'title' => 'Document Control :: Owner',
        'style' => 'min-height:300px;',
        'color' => 'color-orange',
    ) ;

    // Widget properties (default values, might be manually overridden)
    public $properties = array (
        'page_items'  => 30
    ) ;
    
    private $data = array () ;
    

    function ___init () {

        // We could use some help around here
        $this->html = new html_helper () ;
    }

    // Let's hook up to an event that only exists if this widget is active
    // and about to be displayed

    function ___this () {
        
        $id = $this->glob->user->id ;

        $oa = $this->glob->tm->query_assoc ( array ( 
            'type' => $this->_type->has_owner, 
            'member_id' => $id 
        ) ) ;

        $o = new xs_TopicMaps_Assoc ( $oa ) ;

        $o->inject ( array ( 'type' => $this->_type->has_owner ) ) ;

        $o->member_resolve () ;
        // echo '<pre>' ; print_r ( $o ) ; echo '</pre>' ;
        $docs = $this->glob->tm->query ( array ( 'id' => $o->get_members ( $this->_type->doc ) ) ) ;
         
        $res = array () ;
        foreach ( $docs as $topic_id => $doc ) 
            $res[$topic_id] = array ( 
                'uid' => substr ( $doc['name'], 9 ),
                'label' => $doc['label'],
                'extension' => $doc['extension'],
                'state' => isset ( $doc['state'] ) ? $doc['state'] : 'approved',
                'next_review_date' => $doc['next_review_date'],
            ) ;
        
        $this->data = $res ;
        // echo '<pre>' ; print_r ( $docs ) ; echo '</pre>' ;
        

        // $this->glob->stack->add ( 'xs_assoc_owner', $o->__get_array () ) ;

    }

    function GET_content () {

        $menu = array ( 'index' => 'My documents' ) ;

        return $this->prepare ( $this->html->create_simple_widget ( 'document_control_owner', $menu ) ) ;
    }

    function GET () {

        $this->___this () ;
        
        // check if we're to output the data in some other format
        $output = $this->glob->request->_output ;

        // what does the user want?
        $what = $this->glob->request->document_control_view ;

        // what id has the user chosen?
        $id = $this->glob->request->document_control_id ;

        // renderer
        $render = html_helper::RENDER_HTML ;

        // all modes deals with places, so just fetch it
        // $all_documents = $this->glob->data->get ( 'document-control-all' ) ;

        switch ( $what ) {
            default:
            case 'all':
                
                // print_r ( $this->data ) ;
                
                $config = array (
                    'uid' => false,
                    'label' => $this->html->create_link ( 'documents', '[uid]'  ),
                ) ;

                $data = $this->html->_render_table ( $this->data, $config, $render ) ;

               echo $data ;
               echo " <script>oTable = $('#".$this->html->id."').dataTable({'sScrollY': '400px','bPaginate': false,'bJQueryUI': true,'sPaginationType': 'two_button' });</script>" ;
                break ;
            case 'document':
               echo "document [$id]" ;
                break ;
        }

    }


}

