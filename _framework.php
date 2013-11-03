<?php
    /*
     * xSiteable RESTful Topic Maps PHP framework for happy application
     * development, sporting a funky xSLT templating framework, and using
     * the spiffy HTML5 Boilerplate (http://html5boilerplate.com/) templates.
     *
     */

     use \xs\Basic\Properties ;
     use \xs\Stats\Profiler ;

    // quick defines

    define ( 'XS_ROOT_ID', '---' ) ;
    define ( 'XS_PAGE_DB_IDENTIFIER', 'core-content-page' ) ;
    define ( 'XS_DATE', "Y-m-d H:i:s" ) ;
    define ('I', DIRECTORY_SEPARATOR ) ;
    
    // error reporting set to full; set to none in production environments
    ini_set("display_errors", 1);
    
     // Yes, we use sessions. So sue me.
     @session_start() ;

     // Start timing of, well, everything!
     $xs_profiling_start = microtime ( true ) ;
     
     // Set up the static part of our core class
     require_once ( __DIR__.I.'xs'.I.'Core.class.php' ) ;
     
     // register our autoloader
     require_once ( __DIR__.I.'xs'.I.'Autoloader.class.php' ) ;
     // spl_autoload_register ( array ( new \xs\Autoloader(), 'autoload' ) ) ;
     spl_autoload_register ( array ( new \xs\SplClassLoader(), 'loadClass' ) ) ;
    
    

     // Include other basic functions not done better in classes yet
     require_once ( __DIR__.I.'xs'.I.'core.functions.php' ) ;

     
     // Initialize file paths and the like
     \xs\Core::static_setup ( __DIR__ ) ;

     // Create a logger for performance and stuff
     \xs\Core::$glob->log = new \xs\Stats\Profiler ( $xs_profiling_start ) ;

     \xs\Core::$glob->log->add ( 'Start including all framework files' ) ;

     \xs\Core::$glob->log->add ( 'Includes done' ) ;

