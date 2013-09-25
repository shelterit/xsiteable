<?php


class xs_widget_document_manager extends xs_Action_Widget_Controller {

    public $meta = array (
        'name' => 'Document Register',
        'description' => 'Admin widget for dealing with documents',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'DMS'
    ) ;
    
    public $settings = array (
        'title' => 'Document Register',
        'style' => 'min-height:500px;',
        'color' => 'color-red',
    ) ;

    private $html = null ;
    private $docs = array () ;
    private $docs_controlled = array () ;

    function ___this () {

        // We could use some help around here
        $this->html = new html_helper () ;

        // This query is all the users
        $this->docs = $this->glob->data->register_query (

           // identifier for what data connection to use
           'xs',

           // identifier for our query
           'documents-all',

            array (
            'select'      => 'id,name,label',
            'type'        => $this->_type->_document,
            'sort_by'     => 'label DESC',
            // 'lookup_name' => 'm_p_who,m_u_who',
            ),

           // the timespan of caching the result
           '+10 seconds'
        ) ;

    }
    
    // GET (this function) responds to individual AJAX calls to the Widget's API

    function GET () {

        // make sure we use all the stuff initialized when the widget is active,
        // also when we are getting stuff directly from the resource
        
        $this->___this () ;
        
        // what does the user want?
        $what = $this->glob->request->document_manager_view ;

        // any further selection?
        $id   = $this->glob->request->document_manager_id ;
    
        // check if we're to output the data in some other format
        $output = $this->glob->request->_output ;

        // renderer
        $render = html_helper::RENDER_HTML ;
        
        // table configuration options
        $conf = array ( 
            '_export' => array ( 'document_manager', '', $this->glob->dir->api ),
            'id'  => $this->html->create_link ( $this->glob->dir->home.'/documents', '[id]'  ),
            'label' => $this->html->create_link ( $this->glob->dir->home.'/documents', '[name:9]'  ),
            // 'owned_by' => true, // $this->html->create_link ( $this->glob->dir->home.'/profile', '[owned_by]'  ),
            'name' => false,
            'relative_path' => false,
            'final_path' => false,
            'original_path' => false,
            'home_directory' => false,
            'filename' => false,
            'keywords' => false,
            'serialized' => false,
            'controlled' => false,
            'words_count' => false,
            'words_pruned' => false,
            'words_important' => false,
            // 'extension' => '',
            'timestamp' => false,
        ) ;
        
        $tm = $this->_get_module ( 'topic_maps' ) ;

        $data = $this->glob->data->get ( 'documents-all' ) ;
        
        foreach ( $data as $idx => $d ) {
            // debug_r($d); die () ;
            // if ( ! isset ( $d['controlled'] ) ) $data[$idx]['controlled'] = 'false' ;
            if ( ! isset ( $d['next_review_date'] ) ) $data[$idx]['next_review_date'] = '' ;
            if ( ! isset ( $d['owned_by'] ) ) $data[$idx]['owned_by'] = '' ;
            if ( ! isset ( $d['extension'] ) ) $data[$idx]['extension'] = '' ;
            // if ( ! isset ( $d['keywords'] ) ) $data[$idx]['keywords'] = '' ;
            // if ( ! isset ( $d['serialized'] ) ) $data[$idx]['serialized'] = '' ;
        }
        
        // debug_r ( count ( $data ) ) ;
        
        // debug('in');
        // debug('out');
        
        // debug_r ( $lut ) ;
        
        // debug_r ( count ( $data ) ) ;
        
        if ( $what == 'index' ) {
        
            // echo 'not indexc' ;
            $orig = $data ;
            $data = array () ;
            
            foreach ( $orig as $idx => $doc ) {
                if ( isset ( $doc['controlled'] ) && $doc['controlled'] == 'true' )
                    $data[$idx] = $doc ;
            }
        }
            
        // debug_r ( count ( $data ) ) ;
        
        
        foreach ( $data as $idx => $item ) {
            if ( ! isset ( $data[$idx]['controlled'] ) )
                $data[$idx]['controlled'] = 'no record' ;
                
            // $data[$idx]['owned_by'] = 1012 ;
            // $data[$idx]['last_review_date'] = rndDate ( '2009-04-01', '2013-1-1' ) ;
        }
        
        $docs = array () ;
        $counter = 0 ;
        
        foreach ( $data as $idx => $item ) {
            // if ( $counter++ > 160 || $counter < 60 ) { unset ( $data[$idx]) ; continue ; }
            $docs[$item['id']] = $item['id'] ;
        }
        
        $lut = $this->glob->tm->query_assoc ( array (
            'member_id' => $docs,
            'type' => $this->_type->has_owner,
        ) ) ;
        
        $f = new xs_TopicMaps_Assocs ( $lut ) ;
        
        // debug_r ( $lut ) ;
        /*
        $f = new xs_TopicMaps_Assocs ( $lut ) ;
        $o = $f->get_other_members_of_member_id ( ) ;
        debug_r ( $f ) ;
        $docs = $f->get_members_of_type ( $this->_type->_document ) ;
        $owners = $f->get_members_of_type ( $this->_type->_user ) ;
        debug_r ( $docs ) ;
        */
        $fin = $rol = array () ;
        foreach ( $lut as $a_idx => $a ) {
            foreach ( $a['members'] as $ms ) {
                $role = $ms['role'] ;
                $topic = $ms['topic'] ;
                if ( $role == $this->_type->_document )
                    $fin[$topic] = $a_idx ;
                else
                    $rol[$topic] = $topic ;
            }
        }
        $ww = $this->glob->tm->lookup_topics ( $rol ) ;
        
        // debug_r ( $ww ) ;
        
        foreach ( $data as $idx => $item ) {
            if ( isset ( $fin[$item['id']] ) ) {
                $html = '' ;
                foreach ( $f->get_members_of_type ( $this->_type->_user, $fin[$item['id']] ) as $i => $p )
                   $html .= '<a href="'.$this->glob->dir->home.'/profile/'.$i.'">' . $ww[$i]['label'] . '</a> ' ;
                $data[$idx]['owned_by'] = $html ;
            }
        }
        
        // debug_r ( $fin ) ;
        
        // echo "<pre>" ; print_r ( $data[1011] ) ; echo "</pre>" ;
        
        $data_result = $this->html->_render_table ( $data, $conf, $render ) ;
        
        echo $data_result ;
        echo " <script>oTable = $('#".$this->html->id."').dataTable({'sScrollY': '400px','bPaginate': false,'bJQueryUI': true,'sPaginationType': 'two_button' });</script>" ;
        
    }

    // Get the widget initial content
    function GET_content () {
        
        if ( $this->html == null )
            $this->___this () ;
        
        $menu = array ( 'index' => 'Controlled documents', 'all' => 'All documents' ) ;

        return $this->prepare ( 
           $this->html->create_simple_widget ( 'document_manager',  $menu ) 
        ) ;

    }

}

function rndDate($startDate,$endDate){    
    return date("Y-m-d",strtotime("$startDate + ".rand(0,round((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24)))." days"));
}