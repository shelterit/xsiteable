<?php

    /*
     * This is the action class for widgets. Note, however, that it functions as a
     * widget *controller*, and needs to support dealing with multiple instances
     * of that type of widget, possibly with different views and so forth. There's
     * an array in the foyer of the instances associated with this controller.
     */

    class xs_Action_Widget_Controller extends xs_Action_Widget {

        public $class_type = 'controller' ;
        
        // the default view of the widget. If only one view, ignore it
        public $view = null ;

        // every widget instance (each widget class is effectively a controller for
        // a number of possible widget instances)
        public $instances = array () ;

        function __construct () {

            $this->controller_name = substr ( get_class ( $this ) , 10 ) ;
            
            // Go to parents constructor first, making this a xs_Action class
            parent::__construct() ;

            // Set the widgets name (not unique id) in the _meta
            $this->_meta->controller_name = $this->controller_name ;

            // Every widget gets an API resource matching the class name
            $this->_register_resource ( XS_WIDGET, '_api/widgets/' . $this->controller_name ) ;

            // They also get a handy data feed
            $this->_register_resource ( XS_WIDGET, '_api/widgets/' . $this->controller_name . '/feed' ) ;
            
            // echo "ctrl=[{$this->controller_name}] ";
        }

        function ___this_pre () {
            // just wrap a possible 'widget_view' request variable into a local one
            $this->view = $this->glob->request->__fetch ( 'widget_view', 'index' ) ;
        }
        
        function GET_content () {
            // echo "!!!!" ;
        }

        function GET() {
            // echo "@@@@" ;
        }
        
        function ___this_instance () { }
        function ___this_controller () { }


        // a way for the widget controller (primarily) to inject the instances
        // for this controller to deal with
        function _add_instance ( $instance_id = null, $data = array () ) {

            if ( $instance_id != null ) {
                
                // echo "<div style='background-color:red;color:white;'> ctrl=[$this->controller_name] inst=[$instance_id] </div>" ;
                
                // add the instance with its controller reference (our reference)
                $this->instances[$instance_id] = new xs_Action_Widget_Instance ( $this->controller_name, $instance_id ) ;

                // first, fill it with default values
                $this->instances[$instance_id]->_properties->__inject ( $this->_properties->__get_array () ) ;
                $this->instances[$instance_id]->_settings->__inject ( $this->_settings->__get_array () ) ;
                $this->instances[$instance_id]->_technical->__inject ( $this->_technical->__get_array () ) ;
                
                // add in the topic representation
                if ( isset ( $data['_topic'] ) )
                    $this->instances[$instance_id]->_topic = $data['_topic'] ;
                
                if ( isset ( $this->instances[$instance_id]->_settings->title ) &&
                        trim ( $this->instances[$instance_id]->_settings->title ) != '' )
                    $this->instances[$instance_id]->_topic['label'] = $this->instances[$instance_id]->_settings->title ;
                
                // register the instance for quick access
                $this->_register_instance ( $instance_id, $this->instances[$instance_id] ) ;

                // initiate all properties and settings
                if ( isset ( $data['_p'] ) )
                    $this->instances[$instance_id]->_properties->__inject ( $data['_p']->__get_array () ) ;
                
                if ( isset ( $data['_s'] ) )
                    $this->instances[$instance_id]->_settings->__inject ( $data['_s']->__get_array () ) ;
                
                if ( isset ( $data['_t'] ) )
                    $this->instances[$instance_id]->_technical->__inject ( $data['_t']->__get_array () ) ;
                
                $this->instances[$instance_id]->_technical->topic_id = @$this->instances[$instance_id]->_topic['id'] ;
                $this->instances[$instance_id]->_technical->render_uri = $this->glob->dir->home.'/_api/widgets/control/render?controller_name='.$this->controller_name.'&amp;instance_id='.$instance_id ;
                
                
            }
        }

        function get_instance ( $instance_id = null ) {

            if ( isset ( $this->instances[$instance_id] ) )
                return $this->instances[$instance_id] ;
            
            // debug_r ( $this->instances, $this->_meta->class ) ;
            
            $i = new xs_Action_Widget_Instance ( $instance_id ) ;
            $i->_properties->__inject ( $this->_properties->__get_array () ) ;
            $i->_settings->__inject ( $this->_settings->__get_array () ) ;
            $i->_technical->__inject ( $this->_technical->__get_array () ) ;
            
            return $i ;
        }
        
        function load_instance ( $id ) {
            
            $inst = null ;

            $result = $this->glob->tm->query ( array ( 'name' => $id, 'type' => $this->_type->_widget_instance ) ) ;
            if ( is_array ( $result ) && count ( $result ) > 0 ) {
                $inst = reset ( $result ) ;
            }
            
            if ( $inst ) {
                
                $w = $this->_get_module ( 'widgets' ) ;

                $res = $w->inject_to_dummy ( $inst ) ;

                $pp = new xs_Properties ( @$res['properties'] ) ;
                $ss = new xs_Properties ( @$res['settings'] ) ;
                $tt = new xs_Properties ( @$res['technical'] ) ;

                $this->_add_instance ( $id, array ( '_p' => $pp, '_s' => $ss, '_t' => $tt ) ) ;
            }

        }

        function gui_setup ( $param = null ) {
            // echo "controller passing control to generic widget class";
            return parent::gui_setup () ;
        }

    }
	