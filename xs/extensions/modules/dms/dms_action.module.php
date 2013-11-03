<?php

class xs_module_dms_action extends \xs\Action\Generic {
    
    // the unique identifier for the xSiteable Document Control Manager
    private $uid = 'xs_module_dms_action' ;
    
    // are we running in safe mode? (safe=no reaction)
    private $safe_mode = false ;
    
    // Shortcut for our API
    private $resource_base = '_api/module/docs' ;
    
    // shortcut for the DMS module
    private $dms = null ;
    
    function ___modules () {
        
        // a URI to capture
        $this->_register_resource ( XS_MODULE, $this->resource_base . '/action' ) ;
        
    }
    
    function ___modules_end () {
        $this->dms = $this->_get_module ( 'dms' ) ;
    }
    
    function GET () {
        // echo "GET: Hello, world!" ;
        $action = $this->glob->request->action ;
        $id     = $this->glob->request->id ;
        // echo "[".$id."] " ;
        
        $topics = $this->glob->tm->query ( array ( 'id' => $id ) ) ;
        $topic = reset ( $topics ) ;
        
        if ( ! $topic ) {
            echo "Couldn't find topic [$id]. Exiting." ;
        }
        
        echo "<html>\n<head>\n   <script src='{$this->glob->dir->js}/jquery-1.8.2.js' type='text/javascript'></script>\n</head>\n<body>" ;

        
        // debug_r ( $action ) ;
        // debug_r ( $topic ) ;
        
        switch ( $action ) {
            case 'touch-original' : 
                $this->dms->_action_touch_original ( $topics ) ;
                break ;
            case 'touch-dest' : 
                $this->dms->_action_touch_dest ( $topics ) ;
                break ;
            case 'touch-dest-html' : 
                $this->dms->_action_touch_dest_html ( $topics ) ;
                break ;
            case 'text' : 
                echo 'text' ;
                break ;
            case 'index' : 
                echo 'index' ;
                break ;
            default:
                echo "Huh?" ;
        }
    }
    function POST ( $arg = null ) {
        echo "Hello, world!" ;
        debug_r ( $arg ) ;
        die () ;
    }
    
}
