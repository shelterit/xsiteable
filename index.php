<?php

    if ( ! file_exists ( '.installed') ) {
        
        // Include the install and diag page, if needed
        require_once ( '_install.php' ) ; 
        
        // and die!
        die () ;
    }
    
    // We need SimplePie, so get its autoloader first
    require_once ( 'lib/simplepie/autoloader.php' ) ;
    
    // Include the basic xSiteable framework
    require_once ( '_framework.php' ) ;
    \xs\Core::$glob->log->add ( 'Included framework' ) ;

    // Set the version number for our app
    \xs\Core::$app_version = '1.0' ;

    
    
    /*
     *
     * The core of the framework begins here; create the stack, init
     * the stack (ie. find plugins, widgets, modules, and initialize
     * them), and finally, run through the stack, triggering all plugins
     * as we go along
     *
     */

    // Create the application stack
    $xs_stack = new \xs\Events\EventStack () ;

    // Fill it with the default stack events
    $xs_stack->create_event_framework (
        $xs_stack->get_standard_framework()
    ) ;
    
    \xs\Core::$glob->log->add ( 'Event stack primed and ready!' ) ;

    // Find and initiate all plugins and modules
    $xs_stack->init () ;
    \xs\Core::$glob->log->add ( 'Event stack has initiated all plugins and modules' ) ;

    // Start the fun!
    $xs_stack->action () ;
 