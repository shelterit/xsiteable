<?php

class xs_widget_document_control extends xs_Action_Widget_Controller {

    // Generic metadata
    public $meta = array(
        'name' => 'Document Control widget',
        'description' => 'Document Control widget for the front page',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'admin',
    );

    // Widget settings (default values, might be manually overridden)
    public $settings = array (
        'title' => 'Document Control',
        'style' => 'min-height:300px;',
        'color' => 'color-orange',
    ) ;

    // Widget properties (default values, might be manually overridden)
    public $properties = array (
        'page_items'  => 30
    ) ;

    function ___init () {

        // We could use some help around here
        $this->html = new html_helper () ;
    }

    function ___modules () {

        $this->glob->data->register_query (

           // use the default xs (xSiteable) datasource
           'xs',

           // identifier for our query
           'document-control-all',

           // the query in question (passing in an array sends the query to
           // the Topic Maps engine (that builds its own SQL) rather than
           // a generic SQL

           array (
            'select'      => 'id,type1,label,m_p_date,m_p_who,m_u_date,m_u_who,parent',
            'type'        => array ( $this->_type->_document ),
            'sort_by'     => 'm_c_date DESC',
            'lookup_name' => 'm_p_date,m_p_who',
            'return'      => 'topics'
           ),

           // the timespan of caching the result
           '+1 hour'
        ) ;
    }

    // Let's hook up to an event that only exists if this widget is active
    // and about to be displayed

    function ___this () {

        // the generic 'news-top-20' is defined in the news_control.module
        $all_documents = $this->glob->data->get ( 'document-control-all' ) ;

        // pick out the top X for display
        $count = 0 ;
        foreach ( $all_documents as $idx => $item )
            if ( $count++ >= $this->_properties->page_items )
                 unset ( $all_documents[$idx] ) ;

        // get the result from the query, and pop it on the outgoing stack
        $this->glob->stack->add ( 'xs_documents', $all_documents ) ;

    }

    function GET_content () {

        $menu = array ( 'index' => 'Documents', 'document' => 'Document' ) ;

        return $this->prepare ( $this->html->create_simple_widget ( 'document_control', $menu ) ) ;
    }

    function GET () {

        // check if we're to output the data in some other format
        $output = $this->glob->request->_output ;

        // what does the user want?
        $what = $this->glob->request->document_control_view ;

        // what id has the user chosen?
        $id = $this->glob->request->document_control_id ;

        // renderer
        $render = html_helper::RENDER_HTML ;
        
        // all modes deals with places, so just fetch it
        $all_documents = $this->glob->data->get ( 'document-control-all' ) ;

        switch ( $what ) {
            default:
            case 'all':
                $config = array (
                    '_export' => array ( 'default', $what, $this->glob->dir->api ),
                    'id'  => false,
                    'type1'  => false,
                    'm_u_who' => false,
                    'm_p_who' => false,
                    'parent' => false,
                    'original_path' => false,
                    'keywords' => false,
                    'content' => false,
                    'serialized' => false,
                    'words_count' => false,
                    'words_pruned' => false,
                    'label' => $this->html->create_ajax_link ( 'document_control', 'document', '', '[id]'  ),
                ) ;

                $data = $this->html->_render_table ( $all_documents, $config, $render ) ;

               echo $data ;
               echo " <script>oTable = $('#".$this->html->id."').dataTable({'sScrollY': '1200px','bPaginate': false,'bJQueryUI': true,'sPaginationType': 'two_button' });</script>" ;
                break ;
            case 'document':
               echo "document [$id]" ;
                break ;
        }

    }


}

