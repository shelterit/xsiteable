<?php

    /*
     * This class gives inherited classes the ability to work against all the
     * goodness of the xs_EventStack, like registering, looking up and define
     * themselves as different kinds of plugins.
     *
     */

    class xs_EventStack_Plugin extends xs_Core {

        // A global type LUT
        public $_type = null ;
        
        // is there a topic associated with this plugin?
        public $_topic = null ;
        
        // The meta data of any plugin
        public $_meta = null ;

        // The page specific properties of any plugin, mostly used by plugins and
        // action classes that deal with HTML output (pages), such as xs_Action_Webpage
        public $_page = null ;

        // This UID is the unique identifier for whatever class we're instantiating
        // If a class sets the value of this explicitly, we'll look it up in the
        // XS database and fill its data with that. Otherwise, it's a blank class
        // that need to fill herself up, if needed.
        public $_UID = null ;
        
        public $_include_js = false ;
        public $_include_css = false ;
        
        private $debug = false ;

        function __construct ( $lifeless = false ) {

            // if ( $this->debug ) echo '{ ['.$lifeless.'] ' ;
            // Go to parents constructor first, making this a xs_Core class
            parent::__construct() ;
            // if ( $this->debug ) echo '}' ;
            
            // make sure all _type is referencing a global registry of types
            $this->_type = $this->glob_type ;
            
            // creating properties instances
            $this->_meta       = new xs_Properties () ;
            $this->_page       = new xs_Properties () ;
            $this->_properties = new xs_Properties () ;
            $this->_settings   = new xs_Properties () ;
            $this->_technical  = new xs_Properties () ;

            // All plugins should know themselves
            $this->_meta->reflection = new ReflectionClass ( $this ) ;

            $this->_meta->file_path = __FILE__ ;
            $this->_meta->file_dir = __DIR__ ;

            // And, just in case, the file name to the class itself
            $this->_meta->file = dirname ( $this->_meta->reflection->getFileName() ) ;

            // Make a shortcut to the current URI
            $this->_meta->uri = '' ;
            if ( isset ( $_REQUEST['q'] ) )
                $this->_meta->uri = $_REQUEST['q'] ;

            // Get $this class' methods
            // $methods = get_class_methods ( $this ) ;

            // Also keep a local copy
            // $this->_meta->methods = $methods ;

            if ( $this->_UID != null )
                // someone wants a specific ID. Let's give it to them!
                $this->_meta->uuid = $this->_UID ;
            else
                // Generate a random instance number
                $this->_meta->uuid = uuidSecure()  ;
            

            // First, get the class name
            $this->_meta->class = get_class ( $this ) ;
            $this->_meta->instance = __CLASS__ ;
            
            $name = explode ( '_', $this->_meta->class ) ;
            unset ( $name[0] ) ;
            unset ( $name[1] ) ;

            $this->_meta->id = implode ( '_', $name ) ;
            $this->_meta->name = implode ( '/', $name ) ;
            
            if ( property_exists ( $this, '___register_types' ) ) {
                
                foreach ( $this->___register_types as $idx => $description )
                    $this->_register_type ( $idx, $description, $this->_meta->id ) ;

            }
                
            if ( property_exists ( $this, '___register_types_alias' ) ) {

                foreach ( $this->___register_types_alias as $old => $new )
                    $this->_register_type_alias ( $old, $new, $this->_meta->id ) ;

            }
                
            // Then, find and set what kind of type this action class is
            $this->_meta->type = $this->_get_class_type () ;

            if ( ! $lifeless )
                $this->_setup_methods () ;
        }
        
        function _setup_methods () {
            
            // If any of these methods start with '___', it's a call to register
            // them to specifics of the stack. Make it so!

            foreach ( get_class_methods ( $this ) as $method ) {

                // Does the method start with our magic marker?
                if ( substr($method,0,3) == '___') {

                    // What is the event sought?
                    $d = 'XS_'.strtoupper(substr($method,3 )) ;

                    if ( substr( $method, 3) == 'this_instance') {

                        if ( isset ( $this->class_type ) )
                            if ( $this->class_type == 'instance' ) {

                                // debug_r ( $this ) ;
            
                                // $d = 'XS_WIDGET_'.strtoupper($this->instance_id).'_ACTIVE' ;
                                // $d = 'XS_WIDGET_'.strtoupper($this->instance_id).'_ACTIVE' ;
                                $d = strtoupper ( $this->instance_id ) .'_ACTIVE' ;
                                if ( ! defined ( $d ) ) define ( $d, $d ) ;
                                // if ( $this->debug ) 
                                //     echo "<div style='background-color:green;color:yellow;'>t=[{$this->_meta->type}] inst=[$d] m=[$method] id=[{$this->instance_id}]</div>" ;
                                $this->_register_plugin_to_other ( $this->_meta->type, constant( $d ), $method, $this->instance_id, $this->controller_name ) ;
                                // debug_r ( parent ) ;
                            } else { continue ; }
                                // debug_r($this,'define this_instance : plugin');
                    }
                    
                    if ( substr( $method, 3) == 'this_controller') {
                        if ( isset ( $this->class_type ) )
                            if ( $this->class_type == 'controller' ) {
                                $d = 'XS_WIDGET_'.strtoupper($this->controller_name).'_ACTIVE' ;
                                // if ( $this->debug ) echo "<div style='background-color:green;color:white;'> ctrl=[$d] </div>" ;
                            } // else { continue ; }
                                // debug_r($this,'define this_controller : plugin');
                    }
                    
                    // If not defined, someone is attaching an event that isn't part
                    // of the stack (yet), so better create it, just in case
                    if ( ! defined ( $d ) ) define ( $d, $d ) ;

                    // Is it a triggered event? ( 'on_*' events)
                    if ( substr($method,3,3) == 'on_') {

                        // Attach the plugin to that event
                        $this->_register_event_listener ( $this->_meta->type, constant( $d ), $method ) ;
                        if ( $this->debug )
                            echo "<div style='background-color:#cfc;color:#555;border-bottom:solid 1px #aac;'> - ON - [$d :: $method] </div>" ;

                    // Or is it a stack event? ('___*' events)
                    } else {

                        // Attach the plugin to that event
                        $this->_register_plugin ( $this->_meta->type, constant( $d ), $method ) ;
                        if ( $this->debug )
                            echo "<div style='background-color:#ccf;color:#555;border-bottom:solid 1px #aac;'> - * - [$d :: $method] </div>" ;
                    }
                }
            }

        }
        
        function _register_instance ( $id, $inst ) {
            global $xs_stack ;
            $xs_stack->register_instance ( $id, $inst ) ;
        }
        
        function _get_instance ( $id ) {
            global $xs_stack ;
            return $xs_stack->get_instance ( $id ) ;
        }
        
        
        function _register_type ( $idx, $description, $name ) {
            global $xs_stack ;
            $xs_stack->register_type ( $idx, $description, $name ) ;
        }
        
        function _register_type_alias ( $old, $new, $name ) {
            global $xs_stack ;
            $xs_stack->register_type_alias ( $old, $new, $name ) ;
        }
        
        function _get_type () {
            global $xs_stack ;
            return $xs_stack->get_type () ;
        }
        
        function _get_type_alias () {
            global $xs_stack ;
            return $xs_stack->get_type_alias () ;
        }
        
        function _register_functionality ( $label, $func, $default = 'allow' ) {
            global $xs_stack ;
            $xs_stack->register_functionality ( $label, $func, $this, $default ) ;
        }
        
        function _get_functionality_default () {
            global $xs_stack ;
            return $xs_stack->get_functionality_default () ;
        }
        
        function _get_functionality () {
            global $xs_stack ;
            $res = array () ;
            foreach ( $xs_stack->get_functionality () as $func => $labels )
                foreach ( $labels as $label => $instance )
                    $res[$func] = $label ;
            return $res ;
        }

        function _register_plugin ( $priority, $event, $method = null, $param = null ) {
            global $xs_stack ;
            $xs_stack->register_plugin ( $priority, $event, $this, $method, $param ) ;
        }

        function _register_plugin_to_other ( $priority, $event, $method = null, $param = null, $other = null ) {
            global $xs_stack ;
            $xs_stack->register_plugin_to_other ( $priority, $event, $this, $method, $param, $other ) ;
        }

        function _register_module () {
            global $xs_stack ;
            $xs_stack->register_module ( trim ( $this->_meta->id ), $this ) ;
            // echo "[{$this->_meta->id}]" ;
        }

        function _get_module ( $module ) {
            global $xs_stack ;
            return $xs_stack->get_module ( $module ) ;
        }

        function _register_resource ( $priority, $resource ) {
            global $xs_stack ;
            $xs_stack->register_resource ( $priority, $resource, $this ) ;
        }
        

        function _get_resource ( $resource = null ) {
            global $xs_stack ;
            $r = $xs_stack->get_resource ( $resource ) ;
            if ( $r ) {
                $r->resource = $resource ;
                return $r ;
            }
            return null ;
        }
        
        // Alias method that push $this->$method() to the global $stack->method()
        function _register_event_listener ( $priority, $event, $method = null, $param = null ) {
            global $xs_stack ;
            $xs_stack->register_event_listener ( $priority, $event, $this, $method, $param ) ;
        }

        // Alias method that push $this->$method() to the global $stack->method()
        function _register_event ( $priority, $event, $method = null, $param = null ) {
            global $xs_stack ;
            $xs_stack->register_event ( $priority, $event, $this, $method, $param ) ;
        }

        // Alias method that push $this->$method() to the global $stack->method()
        function _fire_event ( $event, $param = array () ) {
            global $xs_stack ;
            $xs_stack->fire_event ( $event, $param ) ;
        }

        function _end_event ( $event ) {
            global $xs_stack ;
            $xs_stack->end_event ( $event ) ;
        }

        // Alias method that push $this->$method() to the global $stack->method()
        function _add_event ( $find_event, $add_event ) {
            global $xs_stack ;
            $xs_stack->add_event ( $find_event, $add_event ) ;
        }

        // push an alert to the user
        function alert ( $type, $headline, $message ) {

            $p = $this->glob->alerts ;

            if ( !isset ( $p[$type] ) )
                $p[$type] = array () ;

            $p[$type][] = array ( $headline, $message ) ;

            $this->glob->alerts = $p ;

            // var_dump ( $this->glob->alerts ) ;
        }


        // If a plugin needs to enforce that it's an action class (but without inheritance)
        function _set_as_action ( $output = false ) {

            // debug ( $output ) ;
            
            if ( $output == true ) {

                // The default output function, usually not overwritten unless you specifically
                // don't want to output through the normal channels
                $this->_register_plugin ( XS_PLUGIN, XS_OUTPUT_INIT, '_prepare_output' ) ;
                $this->_register_plugin ( XS_PLUGIN, XS_OUTPUT_ACTION, '_init_output' ) ;
                $this->_register_plugin ( XS_PLUGIN, XS_OUTPUT_END, '_render_output' ) ;

            } /* else {

                // The default action method, at the default action event!
                $this->_register_plugin ( XS_PLUGIN, XS_ACTION_ACTION, '_action' ) ;

                // The default action method, at the default action event!
                $this->_register_plugin ( XS_PLUGIN, XS_ACTION_END, '_end_action' ) ;

            } */

            // echo "[EventStackPlugin set_as_action: ".$this->_meta->class."] <br>" ;

            $this->glob->log->add ( "EventStackPlugin set_as_action: ".$this->_meta->class ) ;

        }

        // Short-cut method for the above, and setting the title
        function _register_as_action ( $title ) {
           $this->set_as_action () ;
           $this->set_title ( $title ) ;
        }

        // Look at the class name, and figure out what type of plugin class we are
        // Not a fail-safe method, but sufficent for most purposes

        function _get_class_type () {
            $action_type = XS_RESOURCE ;
            $class = $this->_meta->class ;
            if (strstr ( $class, '_widget_' ) )
                $action_type = XS_WIDGET ;
            elseif (strstr ( $class, '_plugin_' ) )
                $action_type = XS_PLUGIN ;
            elseif (strstr ( $class, '_module_' ) ) {
                $action_type = XS_MODULE ;
                $this->_register_module () ;
            }
            return $action_type ;
        }

        // Setup how this plugin will look for and handle its dynamic properties
        // (meaning; if state is to be stored in a database or in a file)
        function _setup_state ( $setup = array () ) {
            foreach ( $setup as $idx => $value ) {
                $to = 'file' ;
                $item = $idx ;
                if ( is_int ( $idx ) )
                    $item = $value ;
                else
                    $to = $value ;
                
                // echo "[$item - $to] " ;
            }
        }

        function _config ( $section, $variable ) {

            $type = 1 ;
            $ret = array () ;

            // print_r ( $this->glob->config[$section] ) ;

            if ( ! is_array ( $variable ) ) {
                $variable = array ( $variable ) ;
                $type = 0 ;
            }

            foreach ( $variable as $var )
               if ( isset ( $this->glob->config[$section][$var] ) )
                  $ret[$var] = $this->glob->config[$section][$var] ;

            // print_r ( $ret ) ;

            if ( $type == 0 )
                return @$ret[$var] ;

            return $ret ;
        }

        function _db_sync ( $in, $force = false ) {
            
            if ( ( ! $force ) || ( !isset ( $in['name'] ) || $this->_topic !== null ) )
                return ;
            
            // Fetch hither the widget module's topic
            $items = $this->glob->tm->query ( array ( 'name' => $in['name']  ) ) ;
            $item = null ;
            
            if ( is_array ( $items ) && count ($items ) > 0 )
                
                // make first topic result this instance's topic representation
                $this->_topic = reset ( $items ) ;
            
            else {
                
                // create a manager topic
                $new_item = array (
                    'name' => $in['name'],
                    'label' => $in['label'],
                    'type1' => $in['type']
                ) ;
                
                $id = $this->glob->tm->create ( $new_item ) ;
                
                if ( (int) $id != 0 )
                    $this->_topic = $new_item ;
                
            }

        }

        function _db_save ( ) {
            $this->glob->tm->update ( $this->_topic ) ;
        }

    }