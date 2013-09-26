<?php

    // Include the basic xSiteable framework
    require_once ( '_framework.php' ) ;
    
    // Create the application stack
    $xs_stack = new xs_EventStack () ;

    // Fill it with the default stack events
    $xs_stack->create_event_framework (
        $xs_stack->get_standard_framework()
    ) ;

    // Find and initiate all plugins and modules
    $xs_stack->init () ;

    $ext = isset ( $_REQUEST['type'] ) ? $_REQUEST['type'] : '' ;

    switch ( $ext ) {
        case 'js' : header ( 'Content-type: text/javascript' ) ; break ;
        case 'css': header ( 'Content-type: text/css' ) ; break ;
    }

    // Start the fun!
    
    foreach ( $xs_stack->get_widget_controllers () as $widget ) {
        
        $name = substr ( $widget->_meta->class, 10 ) ;
        $path = $widget->_meta->file ;
        $file = $path . "/$name.$ext" ;
        if (file_exists ( $file ) ) {
            echo "\n /* --- $file --- */ \n" ;
            echo @file_get_contents ( $file ) ;
        }
    }
    
    foreach ( $xs_stack->get_plugins_include_js () as $idx => $file ) {
        if (file_exists ( $file ) ) {
            echo "\n /* --- [JS] $file --- */ \n" ;
            echo @file_get_contents ( $file ) ;
        }
    }

    // echo "<pre>".htmlentities(xs_Core::$glob->log->reportXML())."</pre>" ;

 