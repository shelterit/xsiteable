<?php

class xs_widget_hello_world extends \xs\Action\WidgetController {

    public $meta = array(
        'name' => 'Hello world! widget',
        'description' => 'Prints a nice friendly welcome to the screen',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'examples',
    );

    // Widget settings (default values, might be manually overridden)
    public $settings = array (
        'title' => 'Greetings!',
        'style' => 'min-height:300px;',
        'color' => 'color-red',
    ) ;
    
    public $properties = array (
        'message' => 'Hello, world!'
    ) ;

    // private $widget_uri = '_api/widgets/bookmark' ;

    function __construct () {
        // Always use parent constructor to set things up
        parent::__construct();

        // Register a resource for this widget to respond to
        // $this->_register_resource ( XS_WIDGET, $this->widget_uri ) ;
    }

    function GET_content ( $args = null, $name = null ) {
        
        // return only the content (no wrappers, no widget frame, no nothin'

        // first, let's use a static message if no widget instance is found
        $message = "Hello, static world!" ;
        
        // get our widget instance
        $inst = $this->get_instance ( $name ) ;
        
        // have we got one?
        if ( $inst ) {
            
            // pick out some saved proerty from it
            $message = $inst->_properties->message ;

        }
        
        // return the message, wrapped in XML for good measure
        return $this->prepare ( 
           $message
        ) ;
    }
    
    
}
