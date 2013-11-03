<?php

class xs_widget_forum extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'Forum widget',
        'description' => 'Forum widget for the front page',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'content',
    );

    // Widget settings (default values, might be manually overridden)
    public $settings = array (
        'title' => 'Forum discussions',
        'style' => 'min-height:300px;',
        'color' => 'color-green',
    ) ;

    // Widget properties (default values, might be manually overridden)
    public $properties = array (
        'forum_items'  => 10
    ) ;
    
    private $forum = null ;
    

    function ___modules () {

        // debug_r($this->_type->_forum_item);
        $this->glob->data->register_query (

            // use the default xs (xSiteable) datasource
            'xs',

            // identifier for our query
            'forum-top-20',

            // the query in question (passing in an array sends the query to
            // the Topic Maps engine (that builds its own SQL) rather than
            // a generic SQL

            array (
            'select'      => 'id,type1,label,m_p_date,m_p_who,m_u_date,m_u_who',
            'type'        => $this->_type->_forum_item,
            'sort_by'     => 'm_c_date DESC',
            'limit'       => 20,
            'lookup_name' => 'm_p_who,m_u_who',
            'count'       => array ( 'what' => 'sub_topics', 'type' => $this->_type->_comment )
            ),

            // the timespan of caching the result
            '+1 hour'
        ) ;

        $this->glob->data->register_query (

            // use the default xs (xSiteable) datasource
            'xs',

            // identifier for our query
            'forum-top-comments-20',

            // the query in question (passing in an array sends the query to
            // the Topic Maps engine (that builds its own SQL) rather than
            // a generic SQL

            array (
            'select'      => 'id,type1,label,m_p_date,m_p_who,m_u_date,m_u_who',
            'type'        => $this->_type->_comment,
            'sort_by'     => 'm_c_date DESC',
            'limit'       => 20,
            'lookup_name' => 'm_p_who,m_u_who',
            ),

            // the timespan of caching the result
            '+5 minutes'
        ) ;

    }

    // Let's hook up to an event that only exists if this widget controller
    // is active and about to have any of its instances displayed

    function ___this () {

        // the generic 'news-top-20' is defined in the news_control.module
        if ( ! $this->forum )
            $this->forum = $this->glob->data->get ( 'forum-top-20' ) ;

    }

    function GET_content ( $args = null, $instance_id = null ) {

        // make sure some prelimenary data is loaded
        $this->___this () ;

        // get the instance in question
        $instance = $this->get_instance ( $instance_id ) ;
        
        // pick out the top X for display
        $count = 0 ;
        $forum = array () ;
        
        foreach ( $this->forum as $idx => $item )
            if ( $count++ < $instance->_properties->forum_items )
                 $forum[$idx] = $item ;

        $html = '<ul class="generic-list headline">' ;
        
        $max = count ( $forum ) - 1 ;
        $counter = 0 ;
        
        foreach ( $forum as $idx => $item ) {
            
            $count = isset ( $item['count'] ) ? $item['count'] : 0 ;
            $html .= "
                <li>
                    <h1><img src='".$this->glob->dir->images."/icons/24x24/actions/agt_internet.png' height='18' style='vertical-align:middle;margin-right:4px;' /> <a href='{$this->glob->dir->home}/forum/{$item['id']}'>{$item['label']}</a></h1>
                    <p class='pub'>". ( ( $count > 0 ) ? "There are <b>{$count} comments</b>." : '' ) ."</p>
                    <div>
                        {$item['pub_short']}
                    </div>
                    <div class='seemore'><a href='{$this->glob->dir->home}/forum/{$item['id']}'>More</a></div>
                </li>
                    " ;
                    
            if ( $counter < $max ) 
                $html .= ' <hr /> ' ;
            $counter++ ;
   
        }
        $html .= '</ul>' ;
            
        return $this->prepare ( $html ) ;
    }

}

