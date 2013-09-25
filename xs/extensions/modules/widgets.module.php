<?php

/*
 *
 * Rules for widget handling, overriding data along the way ;
 *
 *  - load widget controller properties data from data source
 *  - load widget properties from data source
 *  - load widget instance properties from data source
 *
 * xs_EventStack_Module
 */

    class xs_module_widgets extends xs_Action {

        public $metadata = array (
            'name' => 'Widgets module',
            'description' => 'Module to handle widgets',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;
        
        // if types are to be defined and used
        protected $___register_types = array (
            '_widget' => 'A widget',
            '_widget_controller' => 'A widget controller',
            '_widget_instance' => 'A widget instance',
            '_widget_index' => 'A widget index',
        ) ;    

        // Local reference to the global stack event manager
        public $events = null ;


        // The base (and length of that string) for registering the API
        private $api_base = '_api/widgets/control' ;

        // The variable to store our various widget controller classes
        private $widgets_index = null ;
        
        // a LUT table for widget instances usage
        private $widgets_lut = array () ;

        // the variable used to store all active instances found
        // format: $widget_instances[$instance_id] = (string) $widget_controller_id ;

        // private $widget_instances = array () ;


        function ___application () {

            global $xs_stack ;

            // Register the resource of the basic API (meaning, getting all
            // HTTP requests, like GET and POST and so on)
            $this->_register_resource ( XS_MODULE, $this->api_base ) ;

            // Register the resource for rendering a single widget
            $this->_register_resource ( XS_MODULE, $this->api_base.'/render' ) ;

            // Make a reference to the global stack event manager
            $this->events = $xs_stack ;

        }
        
        function ___register_functionality_end () {
            $this->_register_functionality( 'Widgets', 'widget:*' ) ;
            $this->_register_functionality( 'Collapse widgets', 'widget:collapse' ) ;
            $this->_register_functionality( 'Configure widget', 'widget:config' ) ;
            $this->_register_functionality( 'Edit widgets', 'widget:edit' ) ;
            $this->_register_functionality( 'Manage widgets', 'widget:manage' ) ;
            $this->_register_functionality ( 'Edit content', 'widget:content:edit', 'role:editor' ) ;
        }
        
        function inject_to_dummy ( $result ) {
            
            $properties = $settings = $technical = array () ;

            foreach ( $result as $idx => $value ) {
                // debug_r ( $value, $idx ) ;
                $x = explode ( '__', $idx ) ;
                if ( isset ( $x[1] ) ) {
                    $what = $x[0] ;
                    $key = $x[1] ;
                    if ( $what == 'p' ) {
                        $properties[$key] = $value ;
                    } else if ( $what == 's' ) {
                        $settings[$key] = $value ;
                    } else if ( $what == 't' ) {
                        $technical[$key] = $value ;
                    }
                }
            }
            return array ( 'properties' => $properties, 'settings' => $settings, 'technical' => $technical ) ;
        }
        
        function _widget_controller_db () {
            
            $this->_db_sync ( array (
                'name' => 'xs_widget_manager_module',
                'label' => 'Widget Manager Module',
                'type' => $this->_type->_module
            ) ) ;
            
            // if we've got a LUT, unserialize it for use
            if ( isset ( $this->_topic['widgets_lut'] ) )
                $this->widgets_lut = @unserialize ( $this->_topic['widgets_lut'] ) ;
            
        }
        
        function _unserialize_all_widgets () {
            
            $all_widget_instances = array () ;
             
            // First, loop through the mess and pick out just the widgets
            if ( is_array ( $this->widgets_index ) && count ( $this->widgets_index ) > 0 ) {
                foreach ( $this->widgets_index as $section ) {
                    if ( is_array ( $section ) ) {
                        foreach ( $section as $widget ) {
                            $all_widget_instances[$widget] = $widget ;
                        }
                    }
                }
            }
            return $all_widget_instances ;
        }
        
        

        function ___widgets () {
            
            // make sure the controller is synced against the db
            $this->_widget_controller_db () ;

            // any incoming widget index?
            if ( ! isset ( $this->glob->page->values['_widget_index'] ) )
                
                // no index; return with a blank stare
                return ;
            
            // Otherwise, there is an index; unpack and set up
            // load to see if there's a database entry for this URI and its widgets
            $this->widgets_index = @unserialize( $this->glob->page->values['_widget_index'] ) ;
            
            // debug_r ( $this->widgets_index ) ;
            
            // then, make a list of all the widgets that we are to display
            $all_widget_instances = $this->_unserialize_all_widgets () ;
            
            // debug_r ( $this->widgets_index ) ;
            
            // if no widgets are found, just exit
            if ( count ( $all_widget_instances ) > 0 ) 
                $this->_widgets_setup ( $all_widget_instances ) ;
                        
        }
        
        function _widgets_setup ( $all_widget_instances = null ) {

            // fetch all widgets that are to be displayed from the Topic Map
            $widget_instances = $this->glob->tm->query ( array ( 
                'name' => $all_widget_instances, 
                'type' => $this->_type->_widget_instance
            ) ) ;
            
            $not_found = array () ;
            
            // alter the structure so that 'name' field becomes the index for the array instead of field 'id'
            if ( is_array ( $widget_instances ) ) {
                foreach ( $widget_instances as $id => $widget ) {
                    if ( isset ( $widget['name'] ) ) {
                        $widget_instances[$widget['name']] = $widget ;
                        unset ( $widget_instances[$id] ) ;
                    } else {
                        $not_found[$id] = true ;
                    }
                }
            }

            // spit out some useful info if we're debugging
            if ( $this->glob->request->__get( '_debug', 'false' ) == 'true' ) {
                $this->widgets_index['_DEBUG'][] = 'widget-data_manager-' . rand ( 1000, 9999 ) ;
            }
            
            // $instances = $this->_db_sync_widget_instances ( $widget_instances ) ;
            // die () ;

            $events = array () ;

            // if it's an array, then yes!
            if ( is_array ( $this->widgets_index ) && count ( $this->widgets_index ) > 0 ) {

                // loop through all widgets in the index
                foreach ( $this->widgets_index as $section => $widgets ) {
                    
                    // loop through each section
                    foreach ( $widgets as $position => $widget_instance_id ) {

                        // break apart the identifier to pick out the controllers name
                        $p = explode ( '-', $widget_instance_id ) ;

                        // debug_r ( $p ) ;
                        if ( isset ( $p[1] ) ) {

                            // here's one!
                            $widget_controller = $p[1] ;
                            // $instance_id = $p[1] . '-' . $p[2] ;

                            // find the widget controller
                            $find_widget_controllers = $this->events->get_widget_controller ( $widget_controller ) ;
                            
                            // found any controller(s)?
                            if ( is_array ( $find_widget_controllers ) ) {
                                
                                foreach ( $find_widget_controllers as $found_widget_controller ) {

                                    $pp = $ss = $tt = $topic = null ;

                                    // has the widget a stored version of itself in the database?
                                    if ( isset ( $widget_instances[$widget_instance_id] ) ) {

                                        $_topic = $widget_instances[$widget_instance_id] ;

                                        $res = $this->inject_to_dummy ( $_topic ) ;

                                        // debug_r ( $res ) ;

                                        $pp = new xs_Properties ( $res['properties'] ) ;
                                        $ss = new xs_Properties ( $res['settings'] ) ;
                                        $tt = new xs_Properties ( $res['technical'] ) ;

                                        $topic = $_topic ;

                                    } else {

                                        // no widget; make one!

                                        $topic = array ( 
                                            'name' => $widget_instance_id,
                                            'label' => $found_widget_controller->settings['title'],
                                            'type1' => $this->_type->_widget_instance
                                        ) ;
                                        $topic['id'] = $this->glob->tm->create ( $topic ) ;

                                    }
                                    
                                    // echo "<div style='padding:5px;margin:5px;border:solid 1px red;'> " ;

                                    // add the instance id to the widget controller (for its own reference)
                                    $found_widget_controller->_add_instance ( $widget_instance_id, array (
                                        '_p' => $pp, '_s' => $ss, '_t' => $tt, '_topic' => $topic
                                    ) ) ;

                                    // echo "</div> " ;

                                    // echo "[$widget_instance_id]" ;
                                    // debug_r($found_widget_controller);
                                    $inst = $found_widget_controller->get_instance ( $widget_instance_id ) ;
                                    
                                    // Setup the widgets to run in their GUI events (sections)
                                    $c = 'XS_GUI_SECTION'.$section ;
                                    
                                    // register the instance's 'gui_setup' to run at the given event
                                    $inst->_register_plugin ( 
                                        XS_WIDGET, constant($c), 'gui_setup'
                                    ) ;
                                    
                                    // add to event stack
                                    $events[$widget_controller][$widget_instance_id] = strtoupper ( $widget_instance_id ) . '_ACTIVE' ;
                                    
                                    $this->widgets_lut[$widget_instance_id] = $this->glob->request->q ;
                                }
                            }
                        }

                    }
                }

                // Add all events that deals with active widgets
                $this->_add_widgets_to_events ( $events ) ;
            }
        }
        
        function _add_widgets_to_events ( $events ) {
            foreach ( $events as $controller_id => $controller ) {
                $d = 'XS_WIDGET_' . strtoupper ( $controller_id ) . '_ACTIVE' ;
                $this->events->add_event ( constant('XS_WIDGETS_ACTION'), $d ) ;
                foreach ( $controller as $instance_id => $event ) {
                    $this->events->add_event ( constant('XS_WIDGETS_ACTION'), $event ) ;
                }
            }
        }

        //  4239.35

        function ___widgets_end () {

            // At the end of dealing with all the widgets (so, active widget
            // controllers, *not* widget instances), compile a list of
            // them, and place them on the stack

            // Get all widgets
            $controllers = $this->events->get_widget_controllers () ;

            // debug_r($controllers);
            $ret = array () ;
            $cat = array () ;
            $col = array ( 'orange', 'yellow', 'red', 'blue', 'green', 'grey', 'burgundy', 'cyan', 'pink' ) ;
            $cc = 0 ;
            
            foreach ( $controllers as $count => $widget ) {
                $name = $widget->controller_name ;
                if ( trim ( $name ) != '' ) {
                    $c = $widget->_meta->category ;
                    if ( trim ( $c ) == '' )
                        $c = 'misc.' ;
                    $cat[$c] = $c ;
                }
            }
            $c = 0 ;
            foreach ( $cat as $idx => $v ) {
                $cat[$idx] = $col[$c] ;
                $c++ ;
                if ( $c >= count ( $col ) )
                    $c = 0 ;
            }
            
            // debug_r($cat);
            
            foreach ( $controllers as $count => $widget ) {
                
                $name = $widget->controller_name ;
                
                if ( trim ( $name ) != '' ) {
                   $c = $widget->_meta->category ;
                    if ( trim ( $c ) == '' )
                        $c = 'misc.' ;
                   $ret[$name] = array (
                       'name'         => $widget->_meta->name,
                       'description'  => $widget->_meta->description,
                       'version'      => $widget->_meta->version,
                       'type'         => $widget->_meta->type,
                       'category'     => $c,
                       'color'        => $cat[$c],
                   ) ;
                }
            }
            natsort2d ( $ret, 'category' ) ;
            
            $fin = array () ;
            foreach ( $ret as $idx => $widget )
                $fin[$widget['category']][$idx] = $widget ;
            // debug_r ( $ret ) ;
            // debug_r ( $fin ) ;
            
            $this->glob->stack->add ( 'xs_widgets', $fin ) ;

            $this->_db_save () ;
        }
        /*
        function _http_action () {
            echo '_http_action' ;
        }

        function action () {
            echo 'action' ;
        }
        */
        public function GET () {

            // render a widget
            // if id is set, a specific widget instance, 
            // otherwise a generic one from the widget controller
            // echo 'render' ;
            $q    = $this->glob->request->q ;
            $only_content = $this->glob->request->only_content ;
            
            $controller_name = $this->glob->request->__fetch ( 'controller_name', '' ) ;
            $instance_id   = $this->glob->request->__fetch ( 'instance_id', '' ) ;
            
            if ( $q == '_api/widgets/control/render' ) {
                
                $inst = null ;

                // a specific instance id given?
                if ( $instance_id != '' ) {
                    
                    // if so, look it up in the database
                    $result = $this->glob->tm->query ( array ( 
                        'name' => $instance_id, 
                        'type' => $this->_type->_widget_instance 
                    ) ) ;
                    
                    if ( is_array ( $result ) && count ( $result ) > 0 ) {
                        
                        // yes, in the database
                        $inst = reset ( $result ) ;
                        
                    }
                }
                
                // a specific controller name given?
                if ( $controller_name != '' ) {
                    
                    // Render a widget
                    $r = $this->events->get_widget_controller ( $controller_name ) ;

                    if ( is_array ( $r ) && isset ( $r[$controller_name] ) ) {

                        $widget_controller = $r[$controller_name] ;

                        // were we an instance?
                        if ( $inst ) {

                            // debug_r($inst);
                            $res = $this->inject_to_dummy ( $inst ) ;

                            // debug_r($res);
                            $pp = new xs_Properties ( @$res['properties'] ) ;
                            $ss = new xs_Properties ( @$res['settings'] ) ;
                            $tt = new xs_Properties ( @$res['technical'] ) ;

                            $widget_controller->_add_instance ( $controller_name, array (
                                '_p' => $pp, '_s' => $ss, '_t' => $tt
                            ) ) ;

                            $domElement = $widget_controller->GET_content ( null, $controller_name ) ;
                            // return $domElement ;

                            $xml = $domElement->ownerDocument->saveXML($domElement);
                            echo '<span>' . $xml . '</span>' ;

                            die () ;
                        }
                        
                        // not an instance, just controller

                        // debug_r ( $this ) ;

                        // $this->set
                        // $this->glob->widget_output->_set_as_action ( true ) ;

                        $d = 'XS_WIDGET_' . strtoupper ( $controller_name ) . '_ACTIVE' ;
                        $c = 'XS_ACTION' ;
                        if ( ! defined ( $c ) )
                            define ( $c, $c ) ;

                        // debug($c,$d);
                        $this->events->add_event ( constant ( $c ), $d ) ;

                        // debug_r($this);
                        // $w = $widget->gui_setup() ;
                        
                        $this->glob->request->_set ( '_output', 'content-widget' ) ;
                        $this->glob->request->_set ( '_controller_name', $controller_name ) ;
                        $this->glob->request->_set ( '_instance_id', $instance_id ) ;

                        $this->glob->widget_output = new xs_Action_Webpage () ;
                        $this->glob->widget_output->_do_output () ;

                        // echo $this->glob->widget_output->get () ;
                        
                        // echo "!!!!!!!!!!!!!!" ;
                
                        // return $widget->GET() ;

                        // $this->glob->widget_output->_register_event ( XS_MODULE, XS_OUTPUT, '_do_output' ) ;

                        // echo "<pre style='padding:5px;margin:5px;border:dotted 2px gray;'>" ; print_r ( $items ) ; echo "</pre>" ;
                        // $w->_do_output () ;
                        // die() ;

                    }
                    
                } else {

                    echo "[$name] not found." ;

                }
            } else {
                // Control
            }
        }
        
        public function POST ( $arg = null ) {

            $function = $this->glob->request->functional ;
            $layout   = $this->glob->request->layout ;
            $uri      = $this->glob->request->uri ;
            $id       = $this->glob->request->instance_id ;
            $name     = $this->glob->request->controller_name ;
            
            debug_r($this->glob->request );
            
            if ( trim ( $uri == '' ) )
                $uri = XS_ROOT_ID ;

            $res = array () ;

            if ( $function == 'widget' ) {

                echo "<h1>WIDGET</h1>" ;

                $find = $this->glob->tm->query ( array ( 'name' => $id ) ) ;
                $topic = reset ( $find ) ;
                
                debug_r ( $topic, 'original topic' ) ;
                // die() ;
                
                $fields = $this->glob->request->__get_fields () ;
                debug_r ( $fields, 'incoming fields' ) ;

                foreach ( $fields as $key => $field_value )
                    $topic[$key] = $field_value ;
                
                // $fields['name']  = $id ;
                // $fields['type1'] = $this->_type->_widget_instance ;
                $fields['who'] = $this->glob->user->id ;

                if ( isset ( $fields['s__title'] ) )
                    $fields['label'] = $fields['s__title'] ;

                foreach ( $fields as $key => $field_value )
                    $topic[$key] = $field_value ;
                
                debug_r ( $topic, 'merged set' ) ;
                
                $z = $this->glob->tm->update ( $topic, true ) ;

                // var_dump ( $fields ) ;
                // var_dump ( $z ) ;

                $z = $this->glob->tm->query ( array ( 'name' => $id ) ) ;
                debug_r ( $z, 'updated topic' ) ;

                /*
                foreach ( $fields as $idx => $value ) {
                    $x = explode ( '__', $idx ) ;
                    if ( isset ( $x[1] ) ) {
                        $what = $x[0] ;
                        $key = $x[1] ;
                        if ( $what == 'p' ) {
                            $properties[$key] = $value ;
                        } else if ( $what == 's' ) {
                            $settings[$key] = $value ;
                        }
                    }
                }

                echo "<pre style='background-color:yellow'> " ; print_r ( $this->glob->request->__get_fields () ) ; echo "</pre>" ;
                echo "<pre style='background-color:yellow'> " ; print_r ( $settings ) ; echo "</pre>" ;
                echo "<pre style='background-color:yellow'> " ; print_r ( $properties ) ; echo "</pre>" ;
                */



            } elseif ( $function == 'positions' ) {
                
                echo "<h1>POSITIONS</h1>" ;

                $columns = explode ( '|', $layout ) ;

                foreach ( $columns as $column ) {

                    $part = explode ( ':', $column ) ;

                    if ( isset ( $part[1] ) ) {
                        $column_id = $part[0] ;
                        $widgets = explode ( ',', $part[1] ) ;

                        foreach ( $widgets as $position => $widget )
                            $res[$column_id][$position] = $widget ;
                    }
                }
                
                
                debug_r ( $res ) ;
                $ser = serialize ( $res ) ;

                // $this->widgets_index = $res ;
                // $this->glob->page->values['_widget_index']
                
                // Create a generic identifier for this resource
                $id = $this->glob->data->create_id ( 
                    XS_PAGE_DB_IDENTIFIER, 
                    array ( 'uri' => $uri ) 
                ) ;
                
                
                
                debug_r ( $id ) ;
                
                $z = $this->glob->tm->query ( array ( 'name' => $id ) ) ;
                $a = array_keys ( $z ) ;
                if ( isset ( $a[0] ) )
                    if ( isset ( $z[$a[0]] ) ) {
                        
                        $t = $z[$a[0]] ;
                        // $t = new xs_TopicMap_Topic ( $z ) ;
                        $t['_widget_index'] = $ser ;
                        $t['who'] = $this->glob->user->id ;
                        debug_r ( $t ) ;
                        $this->glob->tm->update ( $t ) ;

                        $z = $this->glob->tm->query ( array ( 'name' => $id ) ) ;

                        debug_r ( $z ) ;

                        // remember to clear the cache
                        $this->glob->data->reset ( $id ) ;
                    }

                // $this->save_widgets ( $uri ) ;

            }

        }

    }
