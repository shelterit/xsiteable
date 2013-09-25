<?php

    if ( ! file_exists ( '.installed') ) {
        
        // Include the install and diag page, if needed
        require_once ( '_install.php' ) ; die () ;
    }
    
    // Include the basic xSiteable framework
    require_once ( '_include.php' ) ;
    xs_Core::$glob->log->add ( 'Included framework' ) ;

    // Set the version number for our app
    xs_Core::$app_version = '1.0' ;

    // Simple, crude but efficient logging class
    require_once ( 'application/classes/KLogger.php' ) ;

    // Sugar and spice
    require_once ( 'application/classes/human_dates.php' ) ;
    xs_Core::$glob->log->add ( 'Included local classes' ) ;

    // We also need SimplePie
    require_once ( 'lib/simplepie/autoloader.php' ) ;
    
    /*
     *
     * The core of the framework begins here; create the stack, init
     * the stack (ie. find plugins, widgets, modules, and initialize
     * them), and finally, run through the stack, triggering all plugins
     * as we go along
     *
     */

    // Create the application stack
    $xs_stack = new xs_EventStack () ;

    // Fill it with the default stack events
    $xs_stack->create_event_framework (
        $xs_stack->get_standard_framework()
    ) ;
    
    xs_Core::$glob->log->add ( 'Event stack primed and ready!' ) ;

    // Find and initiate all plugins and modules
    $xs_stack->init () ;
    xs_Core::$glob->log->add ( 'Event stack has initiated all plugins and modules' ) ;

    // debug_r( $xs_stack);
    // Start the fun!
    $xs_stack->action () ;

 