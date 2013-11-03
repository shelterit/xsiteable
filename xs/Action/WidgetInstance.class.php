<?php

    /*
     * This is the action class for widgets. Note, however, that it functions as a
     * widget *controller*, and needs to support dealing with multiple instances
     * of that type of widget, possibly with different views and so forth. There's
     * an array in the foyer of the instances associated with this controller.
     */

    namespace xs\Action ;
     
    class WidgetInstance extends \xs\Action\Widget {

        public $class_type = 'instance' ;
        
        function __construct ( $name = null, $id = null ) {

            $this->controller_name = $name ;
            $this->instance_id = $id ;
            
            // Go to parents constructor first, making this a xs_Action class
            parent::__construct() ;
            
            $this->_technical->instance_id = $id ;
            $this->_technical->controller_name = $name ;
            $this->_technical->class = $this->_meta->class ;
            $this->_technical->uuid = $this->_meta->uuid ;
            
            // debug_r ( $this ) ;
            
            // echo "<div><b>inst=[$id]($name)</b></div> ";
        }
        
        function ___this_instance () {
        }
        
        function GET_content () {
            // echo "!!!!" ;
        }

        function GET() {
            // echo "@@@@" ;
        }

    }
	