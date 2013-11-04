<?php

class xs_widget_feed extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'Feed widget',
        'description' => 'Feed widget for generic feed consumption',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'content',
    );

    // Widget settings (default values, might be manually overridden)
    public $settings = array (
        'title' => 'Feeeed me!',
        'color' => 'color-red',
    ) ;

    // Widget properties (default values, might be manually overridden)
    public $properties = array (
        'feed_name' => 'my_feed',
        // 'feed_URI'  => 'http://feeds.feedburner.com/Shelterit-thinktank'
        'feed_URI'  => 'http://myfeed.com'
    ) ;
    
    // token for the feed content
    private $token_feed = null ;
    
    function GET () {

        $name = $this->glob->request->name ;
        
        if ( $name != '' ) {
            $this->load_instance ( $name ) ;
            echo $this->action ( $name ) ;
        }
        
    }

    function GET_content ( $args = null, $name = null ) {
        
        $html = new \xs\Gui\Html () ;

        $menu = array ( 'index' => 'Update feed' ) ;

        return $this->prepare ( 
           $html->create_simple_widget ( 'feed',  $menu, $name ) 
        ) ;
    }

    function action ( $id = null ) {

        // get our widget instance
        $inst = $this->get_instance ( $id ) ;
        
        // have we got one?
        if ( $inst ) {
            
            $feed_uri = $inst->get_property ( 'feed_URI' ) ;
            
            $proxy = ( isset ( $this->glob->config['framework']['use_proxy'] ) && $this->glob->config['framework']['use_proxy'] ) ?
                $this->glob->config['framework']['use_proxy'] : false ;

            $aContext = null ;

            if ( $proxy ) 
                $aContext = array(
                    'http' => array(
                        'proxy' => $proxy,
                        'request_fulluri' => true
                    )
                );

            $feed = 'feed-'.$this->properties['feed_name'] ;

            $this->token_feed = $this->glob->data->register_query (

                // identifier for what data connection to use (using the above)
                'feed',

                // identifier for our query
                $feed,

                // the feed in question
                array ( $feed_uri, $aContext ),

                // the timespan of caching the result
                '+1 second'
            ) ;

            $data = $this->glob->data->get ( $feed ) ;

            // echo "file [".$this->properties['feed_URI']."]" ; print_r ( $txt ) ; echo "Done." ;

            $feed = new SimplePie () ;

            // $feed->set_cache_location ( xs_Core::$dir_cache ) ;

            // $feed->set_feed_url ( $this->properties['feed_URI'] ) ;

            $feed->set_raw_data ( $data ) ;

            // echo "<pre> $file </pre>" ;

            $feed->init () ;
            // $feed->handle_content_type () ;

            $items = $feed->get_items() ;
            // $items = array() ;
            // echo "<pre>" ; print_r ( $data ) ; print_r ( $items ) ; echo "</pre>" ;

            $html = '<ul class="xs_feed">' ;
            foreach ( $items as $item ) {
                $html .= '<li>' ;
                $html .= '<h1><a target="_blank" href="'.$item->get_link().'" style="color:blue;">'.$item->get_title().'</a></h1>' ;
                // $html .= '<div>'.$item->get_description().'</div>' ;
                $t = $item->get_date(DATE_ATOM) ;
                $html .= "<div>".timed( $t )."</div>" ;
                $html .= '</li>' ;
                // debug_r ( $item ) ;
            }
            if ( count ( $items ) < 1 ) {
                $html .= "<li>Nothing in the feed?</li>" ;
            }
            $html .= '</ul>' ;

            return $html ;
        }
    }
    
}

