<?php
   
class xs_widget_news extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'News widget',
        'description' => 'News widget for the front page',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'content',
    );

    // Widget settings (default values, might be manually overridden)
    public $settings = array (
        'title' => 'News and announcements',
        'style' => 'min-height:300px;',
        'color' => 'color-blue',
    ) ;

    // Widget properties (default values, might be manually overridden)
    public $properties = array (
        'news_items'  => 20
    ) ;
    
    private $news = null ;
    
    
    function ___modules () {

        $data_id = 'news-top-20' ;
        
        // echo "!!! [{$this->_type->_news_item}] " ;

        $this->glob->data->register_query (

            // use the default xs (xSiteable) datasource
            'xs',

            // identifier for our query
            $data_id,

            // the query in question (passing in an array sends the query to
            // the Topic Maps engine (that builds its own SQL) rather than
            // a generic SQL

            array (
            'select'      => 'id,type1,label,m_p_date,m_p_who,m_u_date,m_u_who',
            'type'        => $this->_type->_news_item,
            'sort_by'     => 'm_c_date DESC',
            'limit'       => 20,
            'lookup_name' => 'm_p_who,m_u_who',
            'count'       => array ( 'what' => 'sub_topics', 'type' => $this->_type->_comment )
            ),

            // the default timespan of caching the result (configuration.ini might override)
            '+2 minutes'
        ) ;
        
        // echo "[{$this->_type->_news_item}] " ;
    }


    // Let's hook up to an event that only exists if this widget is active
    // and about to be displayed

    function ___this_controller () {

        // the generic 'news-top-20' is defined in the news_control.module
        if ( ! $this->news )
            $this->news = $this->glob->data->get ( 'news-top-20' ) ;

    }

    function GET_content ( $args = null, $name = null ) {

        $this->___this_controller () ;
        
        $instance = $this->get_instance ( $name ) ;
        
        // pick out the top X for display
        $count = 0 ;
        $news = array () ;
        $max = 10 ;
        if ( (int) $instance->_properties->news_items > 0 )
            $max = $instance->_properties->news_items ;
        
        foreach ( $this->news as $idx => $item ) {
            if ( $count++ < $max )
                 $news[$idx] = $item ;
        }
        
        $html = '<ul class="generic-list headline">' ;
        
        $max = count ( $news ) - 1 ;
        $counter = 0 ;
        
        foreach ( $news as $idx => $item ) {
            
            $count = isset ( $item['count'] ) ? $item['count'] : 0 ;
            $html .= "
                <li>
                    <h1>
                        <img src='".$this->glob->dir->images."/icons/24x24/actions/agt_announcements.png' height='18' style='vertical-align:middle;margin-right:4px;' /> 
                        <a href='{$this->glob->dir->home}/news/{$item['id']}'>".htmlentities($item['label'])."</a>
                    </h1>
                    <p class='pub'>
                    published ".timed($item['m_p_date'])."
                    by <a href='{$this->glob->dir->home}/profile/{$item['m_p_who']}'>{$item['m_p_who_label']}</a>
                    ". ( ( $count > 0 ) ? " and has <b>{$count} comments</b>" : '' ) .".</p>
                    <div>
                        {$item['pub_short']}
                    </div>
                    <div class='seemore'><a href='{$this->glob->dir->home}/news/{$item['id']}'>More</a></div>
                </li>
                    " ;
                    
            if ( $counter < $max ) 
                $html .= ' <hr /> ' ;
            $counter++ ;

        }
        $html .= '</ul>' ;
            // return $html ;
        return $this->prepare ( $html ) ;
        
    }
    
}

