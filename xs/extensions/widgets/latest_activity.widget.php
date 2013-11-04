<?php

class xs_widget_latest_activity extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'Latest activity widget',
        'description' => 'Latest activity widget for the front page',
        'version' => '1.1',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'content',
    );

    // Widget settings (default values, might be manually overridden)
    public $settings = array (
        'title' => 'Latest activity',
        'style' => 'min-height:300px;',
        'color' => 'color-blue',
    ) ;

    // Widget properties (default values, might be manually overridden)
    public $properties = array (
        'items'  => 20
    ) ;
    
    private $items = null ;
    

    function ___register_queries () {

        $this->glob->data->register_query (

           // use the default xs (xSiteable) datasource
           'xs',

           // identifier for our query
           'latest-activity-20',

           // the query in question (passing in an array sends the query to
           // the Topic Maps engine (that builds its own SQL) rather than
           // a generic SQL

           array (
            'select'      => 'id,name,type1,label,m_c_date,m_p_date,m_u_date,m_d_date,m_c_who,m_p_who,m_u_who,m_d_who,parent',
            'type'        => array ( $this->_type->_comment, $this->_type->_news_item, $this->_type->_page ),
            'limit'       => 40,
            'sort_by'     => 'm_c_date DESC, m_p_date DESC, m_u_date DESC, m_d_date DESC',
            'lookup_name' => 'type1,m_c_who,m_p_who,m_u_who,m_d_who,parent',
           ),

           // the timespan of caching the result
           isset ( $this->glob->config['cache']['widget_recent_activity'] ) ? $this->glob->config['cache']['widget_recent_activity'] : '+2 minutes'
        ) ;
    }

    function ___this_controller () {
        
        // the generic 'news-top-20' is defined in the news_control.module
        $this->items = $this->glob->data->get ( 'latest-activity-20' ) ;
 
    }

    function ___this_instance ( $id = null ) {

        // get our widget instance
        $inst = $this->get_instance ( $id ) ;
        // debug('news_inst:'.$id,'inst');
        
        // have we got one?
        if ( $inst ) {
            
            // pick out some saved proerty from it
            $max = $inst->_properties->items ;

            // pick out the top X for display
            $count = 0 ;
            $final = array () ;

            if ( is_array ( $this->items ) ) {
                foreach ( $this->items as $idx => $item ) {
                    if ( $count++ >= $max )
                        unset ( $this->items[$idx] ) ;
                    else {

                        // we have activity!

                        $type = isset ( $item['type1'] ) ? (int) $item['type1'] : 0 ;
                        $parent = isset ( $item['parent_type1'] ) ? (int) $item['parent_type1'] : 0 ;

                        switch ( $type ) {
                            case $this->_type->_comment : 
                                switch ( $parent ) {
                                    case $this->_type->_forum_item : 
                                        $this->items[$idx]['where'] = 'forum' ;
                                        break ;
                                    case $this->_type->_news_item :
                                        $this->items[$idx]['where'] = 'news' ;
                                        break ;
                                    case $this->_type->_document :
                                        $this->items[$idx]['where'] = 'documents' ;
                                        break ;
                                    case $this->_type->_page :
                                        $this->items[$idx]['where'] = '_page' ;
                                        $this->items[$idx]['where_uri'] = str_replace ( '|', '/', substr ( $this->items[$idx]['name'], 22 ) ) ;
                                        break ;
                                    default :
                                        $this->items[$idx]['where'] = strtolower($this->items[$idx]['label']) ;
                                        break ;
                                }
                                break ;
                            case $this->_type->_page :
                                $this->items[$idx]['where'] = '_page' ;
                                $this->items[$idx]['where_uri'] = str_replace ( '|', '/', substr ( $this->items[$idx]['name'], 22 ) ) ;
                                if ( $this->items[$idx]['where_uri'] == XS_ROOT_ID )
                                    $this->items[$idx]['where'] = 'homepage' ;
                                break ;
                            case $this->_type->_document :
                                $this->items[$idx]['where'] = 'documents' ;
                                break ;
                            case $this->_type->_news_item :
                                $this->items[$idx]['where'] = 'news' ;
                                break ;
                            default :
                                $this->items[$idx]['where'] = $this->items[$idx]['name'] ;
                                break ;
                        }

                        if ( isset ( $item['parent_id']) )
                            $this->items[$idx]['parent_id'] = substr ( $this->items[$idx]['parent_name'], 9 ) ;
                        // } else
                        //     $items[$idx]['where'] = '..' ;
                    }
                }
                foreach ( $this->items as $idx => $item ) {

                    $when = '0' ;
                    $who = '?' ;
                    $op = 'n/a' ;
                    $l = '.' ;
                    if ( (int) $this->items[$idx]['m_c_date'] != 0 ) { $when = $this->items[$idx]['m_c_date'] ; $who = $this->items[$idx]['m_c_who'] ; $l = 'c' ; $op = 'created' ; }
                    if ( (int) $this->items[$idx]['m_p_date'] != 0 ) { $when = $this->items[$idx]['m_p_date'] ; $who = $this->items[$idx]['m_p_who'] ; $l = 'p' ; $op = 'published' ; }
                    if ( (int) $this->items[$idx]['m_u_date'] != 0 ) { $when = $this->items[$idx]['m_u_date'] ; $who = $this->items[$idx]['m_u_who'] ; $l = 'u' ; $op = 'updated' ; }
                    if ( (int) $this->items[$idx]['m_d_date'] != 0 ) { $when = $this->items[$idx]['m_d_date'] ; $who = $this->items[$idx]['m_d_who'] ; $l = 'd' ; $op = 'deleted' ; }



                    $c = substr($when, 0, 10) ;
                    $cd = timed($c) ;

                    $this->items[$idx]['when'] = $when ;
                    $this->items[$idx]['who'] = $who ;
                    $this->items[$idx]['who_lut'] = $l ;
                    $this->items[$idx]['op'] = $op ;

                    $final[$cd][$idx] = $this->items[$idx] ;
                }
            }

            // get the result from the query, and pop it on the outgoing stack
            // $this->glob->stack->add ( 'xs_latest_activity', $final ) ;

            foreach ( $final as $title => $section ) {

                echo "<h1 style='margin-top:15px;'>$title</h1>" ;

                foreach ( $section as $idx => $item ) {

                    // debug_r ( $item ) ;
                    
                    echo "<div class='newsitem' style='margin:0;padding:2px 3px;'>" ;

                    $type = '..' ;
                    if ( isset ( $item['type1_name'] ) ) 
                        $type = $item['type1_name'] ;

                    $who = safe($item,'who_lut') ;
                    
                    echo "<p class='pub' style='padding-bottom:4px;'>
                            <b>".$type.": " ;

                    if ( $type == 'document' )
                        echo '<a href="'.$this->glob->dir->home.'/show/'.safe($item,'parent_id').'?f:_item=view#comment-'.$item['id'].'">'.safe($item,'parent_label').'</a>' ;
                    elseif ( $type == 'page' )
                        echo '<a href="'.$this->glob->dir->home.'/'.safe($item,'where_uri').'">'.$item['where_uri'].'</a>' ;
                    elseif ( $type == 'homepage' )
                        echo '<a href="'.$this->glob->dir->home.'">Homepage</a>' ;
                    else {
                        if ( isset ( $item['parent_label'] ) )
                            echo '<a href="'.$this->glob->dir->home.'/'.safe($item,'where').'/'.safe($item,'parent').'#comment-'.safe($item,'id').'">'.safe($item,'parent_label').'</a>' ;
                        else
                            echo '<a href="'.$this->glob->dir->home.'/'.safe($item,'where').'/'.safe($item,'id').'">'.safe($item,'label').'</a>' ;
                    }

                    echo '</b> where ' ;
                    echo '<a href="'.$this->glob->dir->home.'/profile/'.safe($item,'who').'">'.
                            safe( $item, 'm_'.$who.'_who_label' ).'</a>' ;

                    if ( $type == 'comment' )
                        echo ' made a comment '.timed(safe($item,'m_'.$who.'_date')).'</p>' ;
                    else
                        echo ' '.$item['op'].' it '.timed ( safe ( $item, 'm_'.$who.'_date')).'</p>' ;

                    // debug_r($item,$idx);

                    // echo "<p class='pubshort' style='margin:0;padding:0;'></p>" ;
                    
                    echo "</div>" ;
                }

            }
        }
        
    }

    // Let's hook up to an event that only exists if this widget is active
    // and about to be displayed

    function GET () {

        $this->___this_controller () ;
        
        $name = $this->glob->request->name ;
        
        if ( $name != '' ) {
            $this->load_instance ( $name ) ;
            $this->___this_instance ( $name ) ;
        }
        
    }

    function GET_content ( $args = null, $name = null ) {
        
        // $html = new html_helper () ;

        $menu = array ( 'index' => 'Update' ) ;

        return $this->prepare ( 
           $this->glob->html_helper->create_simple_widget ( 'latest_activity',  $menu, $name ) 
        ) ;
    }

    

}


function safe ( $item, $idx ) {
    if ( isset ( $item[$idx] ) )
        return $item[$idx] ;
    return null ;
}