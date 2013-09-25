<?php

class xs_EventStack extends xs_Core {

    private $plugins = array () ;
    private $modules = array () ;
    private $actions = array () ;
    public $resources = array () ;
    private $instances = array () ;
    private $files = array () ;
    private $dynamic_files = array () ;
    private $events = array () ;
    private $events_end = array () ;
    private $event_owner = array () ;
    private $properties = array () ;
    private $type = array () ;
    private $type_alias = array () ;

    // a list of functionality attached to this resource, in which the user may 
    // or may not be allowed to perform
    public $functionality = array () ;
    public $functionality_default = array () ;
        
    public  $event_stack = array () ;

    public $current_event = null ;

    private $counter = 0 ;
    
    public $debug = false ;
    
    function get_plugins () {
        $ret = array () ;
        $c = 0 ;
        
        // debug_r ( $this->plugins[0] ) ;
        
        foreach ( $this->plugins as $idx => $plugin ) {
            if ( $c++ < 2000 ) {
                // echo "[{$idx}] " ;
            }
            if ( isset ( $plugin->_meta ) )
                $ret[$c]['_'] = $plugin->_meta ;
            if ( isset ( $plugin->meta ) )
                $ret[$c]['m'] = $plugin->meta ;
            
        }
        return $ret ;
    }
    function get_modules () {
        return $this->modules ;
    }
    function get_resources () {
        return $this->resources ;
    }
    function get_events () {
        return $this->events ;
    }
    function get_event_owners () {
        return $this->event_owner ;
    }
    function get_files () {
        return $this->files ;
    }
    function get_dynamic_files () {
        return $this->dynamic_files ;
    }
    function add_dynamic_file ( $file ) {
        $this->dynamic_files[] = $file ;
    }

    function __construct () {

        parent::__construct() ;

        // Make our stack an array object w/delicious iterator
        $this->event_stack = array () ;
        $this->event_owner = array () ;

        // $this->event_stack_iterator = $this->event_stack->getIterator () ;

        // Log progress
        $this->glob->log->add ( 'EventStack : __construct' ) ;

        // $this->debug = true ;
    }

    public function create_event_framework ( $events = null, $recurse = false ) {

        foreach ( $events as $event => $next ) {

            // Just a handy shorthand for the GUI events
            $pre = substr ( $event, 0, 6 ) ;

            if ( $pre != 'XS_GUI' ) {
                $this->_register_stack ( $event, '_PRE', $next ) ;
            }

            $this->_register_stack ( $event, '', $next ) ;

            if ( $pre != 'XS_GUI' ) {
                $this->_register_stack ( $event, '_INIT', $next ) ;
                $this->_register_stack ( $event, '_CONFIG', $next ) ;
                $this->_register_stack ( $event, '_ACTION', $next ) ;
            }
            if ( is_array ( $next ) )
                $this->create_event_framework ( $next, true ) ;

            if ( $pre != 'XS_GUI' )
                $this->_register_stack ( $event, '_END', $next ) ;
        }
        
        if ( !$recurse )
            $this->glob->log->add ( 'EventStack : create_event_framework() done.' ) ;
    }

    private function _register_stack ( $event, $postfix = '', $part = true ) {
        if ( ! defined ( $event.$postfix ) ) {
            define ( $event.$postfix, $event.$postfix ) ;
            if ( $part == true || is_array ( $part ) ) {
                $this->event_stack[$event.$postfix] = $event.$postfix ;
            }
        }
    }

    public function add_event ( $idx = null, $event = null ) {
        if ( isset ( $this->event_stack[$idx] ) ) {
            // echo "[$idx] " ;
            if ( ! defined ( $event ) ) define ( $event, $event ) ;

            $ev = '' ;
            $e = $event ;
            if ( ! is_array ( $e ) )
                $e = array ( $event => $event ) ;

            foreach ( $e as $eventid )
                $ev .= $eventid.' | ' ;

            $final = array () ;

            foreach ( $e as $eve ) {
                $final[$eve.'_PRE'] = $eve.'_PRE' ;
                $final[$eve] = $eve ;
                $final[$eve.'_INIT'] = $eve.'_INIT' ;
                $final[$eve.'_CONFIG'] = $eve.'_CONFIG' ;
                $final[$eve.'_ACTION'] = $eve.'_ACTION' ;
                $final[$eve.'_END'] = $eve.'_END' ;
            }

            foreach ( $final as $eve )
                if ( ! defined ( $eve ) )
                    define ( $eve, $eve ) ;


            array_insert ( $this->event_stack, $idx, $final ) ;
            
            // echo "<div style='background-color:green;color:white;'> evnt=[$event] to=[$idx] </div>" ;
            $this->glob->log->add ( 'EventStack : addEvent '.$ev.' to '.$idx ) ;

        } else {
            
           return null ;
        }
    }
    
    function include_these ( $files ) {
        
        // found stuff?
        if ( isset ( $files ) && count ( $files ) > 0 ) {

            // debug_r ( $files ) ;
            
            // go through all the files found and known
            foreach ( $files as $name => $file ) {

                // echo "[$file] " ;

                if ( file_exists ( $file ) ) {

                    require_once ( $file ) ;

                    $f = basename ( $file, '.php' ) ;
                    $e = substr ( $f, stripos ( $f, '.' ) + 1 ) ;

                    $class = "xs_{$e}_{$name}" ;

                    // echo "<span style='color:green'>[$class]</span> " ;
                    if ( class_exists ( $class ) ) {
                        // echo "!$name!" ;
                        $this->plugins[$name] = new $class () ;
                        $this->properties[$name] = array () ;
                    } else {
                        echo "xs_eventStack failed to find class ($class) that should be in file [$file]<br/> " ;
                    }
                } else {
                    echo "xs_eventStack failed to find file [$file]<br/> " ;
                }
            }
        }
    }

    function init () {

        // Log progress
        $this->glob->log->add ( 'EventStack : Init : Start' ) ;

        $xs = self::$dir_xs .'/extensions' ;

        // create a list over known classes that is part of the core
        // xSiteable distribution that needs to be invoked first

        $first = array (
            'topic_maps'      => "{$xs}/topic_maps.module.php",
        ) ;

        // find the rest of the plugins that are part of the xSiteable distribution
        $core = $this->registerDirectories ( $xs ) ;
        // debug_r($core);
        // Find the user plugins (in the application)
        $found = $this->registerDirectories ( './application/extensions' ) ;
        // debug_r($found);

        // included in seperate chunks to avoid duplicates
        $this->include_these ( array_merge ( $first, $core ) ) ;
        $this->include_these ( $found ) ;
        
        // merge for reference (but may contain duplicates, ie. some files missing from overrides)
        $this->files = array_merge ( $first, $core, $found ) ;

        // Log progress
        $this->glob->log->add ( 'EventStack : Init : Done' ) ;
    }

    function setup () {

        foreach ( $this->plugins as $name => $instance ) {
            $instance->_setup( $name ) ;
            $this->properties[$name] = $instance->_find_persisted_vars () ;
        }

    }

    function get ( $name ) {
        if ( $name && isset ( $this->plugins[$name] ) )
            return $this->plugins[$name] ;
        else
            return null ;
    }

    function get_plugin ( $name ) {
        return $this->get ( $name ) ;
    }
    
    function register_instance ( $id, $inst ) {
        $this->instances[$id] = $inst ;
    }

    function get_instance ( $id ) {
        if ( isset ( $this->instances[$id] ) )
            return $this->instances[$id] ;
        return null ;
    }

    function register_plugin ( $priority, $event, $instance, $method, $param = null ) {
        $this->actions[$event][] = array (
                'instance' => $instance,
                'method' => $method,
                'priority' => $priority,
                'param' => $param,
                ) ;
    }

    function register_plugin_to_other ( $priority, $event, $instance, $method, $param, $other ) {
        $find = $this->get_plugin ( $other ) ;
        if ( $find ) {
            // debug_r($find);
            $this->actions[$event][] = array (
                    'instance' => $find,
                    'method' => $method,
                    'priority' => $priority,
                    'param' => $param,
                    ) ;
        } else {
            // echo "!!!! [$other] !!!!!!!!!!" ;
        }
    }

    function register_module ( $name, $instance ) {
        $name = trim ( $name ) ;
        // echo "[register:$name] " ;
        $this->modules[$name] = $instance ;
    }

    function register_functionality ($label, $func, $instance, $default = 'allow' ) {
        $this->functionality[$func][$label] = $instance ;
        $this->functionality_default[$func] = $default ;
    }

    function get_functionality () {
        return $this->functionality ;
    }
    
    function get_functionality_defaults () {
        return $this->functionality_default ;
    }
    
    function register_type ( $idx, $desc, $from ) {
        // echo '/' ;
        $this->type[$from][$idx] = $desc ;
    }
    
    function register_type_alias ( $old, $new, $from ) {
        // echo '.' ;
        $this->type_alias[$from][$old] = $new ;
    }

    function get_type () {
        return $this->type ;
    }
    
    function get_type_alias () {
        return $this->type_alias ;
    }
    
    function get_module ( $name ) {
        $name = trim ( $name ) ;
        // echo "[lut:$name] " ;
        // echo "<pre>".print_r($this->modules, true)."</pre>" ;
         if ( isset ( $this->modules[$name] ) ) {
             // echo "!!!!!!!!!!! " ;
             return $this->modules[$name] ;
         }
         // echo "@@ " ;
         return null ;
    }

    function register_resource ( $priority, $resource, $instance ) {
        // echo "[register $resource]<br> " ;
        $this->resources[$resource] = array (
                'instance' => $instance,
                'resource' => $resource,
                'priority' => $priority,
                ) ;
    }

    function register_event_listener ( $priority, $event, $instance, $method = null, $param = null ) {
        
        if ( $method == null )
            $method = 'callback___' . $event ;

        if ( substr ( $event, 0, 3 ) == 'on_' ) $event = 'XS_'.strtoupper ( $event ) ;

        // $debug = false ;
       // echo "<hr> !!!!!!!!!!!!!! register_event :: <b>$event</b>: [" . get_class($instance) . "->$method] \n<hr>\n " ;

        $this->events[$event][] = array (
                'instance' => $instance,
                'method' => $method,
                'priority' => $priority,
                'param' => $param,
             ) ;
    }

    function register_event ( $priority, $event, $instance, $method = null, $param = null ) {

        if ( !isset ( $this->events[$event] ) )
           $this->events[$event] = array () ;

        $this->event_owner[$event] = array (
                'instance' => $instance,
                'method' => $method,
                'priority' => $priority,
             ) ;
    }
    
    function end_event ( $event ) {
        
        if ( isset ( $this->events[$event] ) ) {
            
            // echo "[end:$event]" ;
            $this->events_end[$event] = true ;
            
        }
        
    }

    function fire_event ( $event, $param = null ) {
        $debug = $this->debug ;

        $orig = $event ;

        if ( substr ( $event, 0, 3 ) == 'on_' ) $event = 'XS_'.strtoupper ( $event ) ;

        $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] fire_event ( $event )"  ) ;

        if ( isset ( $this->events[$event] ) && is_array ( $this->events[$event] ) && count ( $this->events[$event]) != 0 ) {

            $this->glob->seclog->logInfo ( 'EventStack : Firing event ['.$event.'], count ['.count ( $this->events[$event]).']' ) ;

            foreach ( $this->events[$event] as $n ) {
                
                if ( ! isset ( $this->events_end[$orig] ) ) {
                    // echo "[not:$orig]" ;
                    
                    $instance = $n['instance'] ;
                    $method = $n['method'] ;
                    // $priority = $n['priority'] ;
                    // $param = $param ;
                    if ($debug) echo "<hr>FIRE event :: <b>$event</b>: [" . get_class($instance) . "->$method] \n<hr>\n " ;
                    // $this->glob->seclog->logInfo ( 'EventStack : Firing event ['.$event.'], method ['.$method.'] on ['.get_class ( $instance ).']' ) ;
                    // print_r ( 'EventStack : Firing event ['.$event.'], method ['.$method.'] on ['.get_class ( $instance ).']' ) ;

                    // if ( is_callable ( array ( $instance, $method ) ) ) {
                    //     $this->glob->seclog->logInfo ( 'EventStack : method ['.$method.'] callable.' ) ;
                    // }
                    $ret = call_user_func ( array ( $instance, $method ), $param ) ;

                    if ( isset ( $this->event_owner[$orig] ) && $this->event_owner[$orig] ) {
                        $m = 'callback___' . $orig ;
                        $i = $this->event_owner[$orig]['instance'] ;
                        if ($debug) echo "<hr>FIRE event CALLBACK :: <b>$orig</b>: [" . get_class($i) . "->$m] \n<hr>\n " ;
                        $this->glob->seclog->logInfo ( 'EventStack : method ['.$method.'] call-back.' ) ;

                        if ( is_callable ( array ( $i, $m ) ) ) {
                            $this->glob->seclog->logInfo ( 'EventStack : orig ['.$orig.'] calling back.' ) ;
                            // echo "[Callback!!] <br><br> " ;
                            $i->$m ( $ret ) ;
                        }



                    } else {
                        // echo "ERROR!" ;
                    }
                }
            }
            
            // do a final callback if such a method exists

            if ( isset ( $this->event_owner[$orig] ) && $this->event_owner[$orig] ) {
                $m = 'callback_final___' . $orig ;
                $i = $this->event_owner[$orig]['instance'] ;
                if ( is_callable ( array ( $i, $m ) ) ) {
                    $this->glob->seclog->logInfo ( 'EventStack : orig ['.$orig.'] calling back FINAL.' ) ;
                    // echo "FINAL [$m]!" ;
                    $i->$m () ;
                }
            }            

        }
        $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] fire_event ( $event ) : event OVER / END."  ) ;

    }

    function get_resource ( $resource = null ) {
        $r = $this->find_resource ( $resource ) ;
        if ( isset ( $r['instance'] ) ) return $r['instance'] ;
        return null ;
    }

    function find_resource ( $resource ) {
        foreach ( $this->resources as $res => $val )
            if ( $resource == $res ) return $val ;
        return null ;
    }
    
    function get_plugins_include_js () {
        $ret = array () ;
        foreach ( $this->plugins as $idx => $plugin ) {
            if ( $plugin->_include_js ) {
                $t = explode ( '_', $plugin->_meta->class ) ;
                if ( isset ( $t[1] ) )
                    $ret[$idx] = 
                       $plugin->_meta->file . "/{$plugin->_meta->name}.{$t[1]}.js" ;
            }
        }
        return $ret ;
    }
    
    function get_widget_controller ( $widget_controller = null ) {

        if ( isset ( $this->plugins[$widget_controller] ) )
            return array ( $widget_controller => $this->plugins[$widget_controller]) ;
        
        return null ;
    }

    function get_widget_controllers ( ) {
        $ret = array () ;
        foreach ( $this->plugins as $res => $val )
            if ( $val->_meta->type == XS_WIDGET )
               $ret[$res] = $val ;
        return $ret ;
    }

    // TODO: Refactor the 'XS_GUI_JS' and 'XS_GUI_CSS' cases to a generic
    //       helper method

    // TODO: Refactor the 'XS_GUI_ADMIN_CONFIG_EDIT' case and the default
    //       case into a sane helper method

    function gui_action ( $event = 'XS_EVENT_GUI' ) {

        // Special version of action() that works for run-up event handling
        // when dealing with rendering the GUI

        $ret = array () ;

        $debug = $this->debug ;

        $start = false ;

        foreach ( $this->event_stack as $id => $val ) {

            if ( $start != false) {
                // echo "<div style='border:dotted 2px red;padding:8px;margin:8px;'>At [$id] , finding [$event]</div>" ;
            }

            if ( $id == $this->current_event ) { $start = $id ;
                if ( $debug) echo "<div style='padding:8px;margin:8px;'>START hunt for [$event]</div>" ;
            }

            if ( $id == $event ) {
                if ( $debug) echo "<div style='border:dotted 1px blue;padding:8px;margin:8px;'>FOUND $id, exiting with current_event=$id</div>" ;
                $r = $this->run_action ( $id, $debug ) ;
                $ret[] = $r ;
                $this->current_event = $id ;
                break ;
            }
        }

        return $ret ;
    }

    function action ( $event = 'XS_LIFESPAN', $gui = false ) {

        $debug = $this->debug ;

        // The array of results to return
        $ret = array () ;

        $this->counter++ ;

        // If there is no current event, just make it this first one
        if ( $this->current_event == null ) {
            $this->current_event = $event ;
        }

        // Log progress
        $this->glob->log->add ( "EventStack : Action [$event]" ) ;

        // From where do we start running actions?
        $start = false ;

        // This is the main loop, where lead is turned to gold!

        $counter = 0 ;
        
        // $debug = true ;

        if ( $debug) 
            echo "<div style='border:dotted 1px red;padding:18px;margin:18px;'>Action=[$event] ($gui) count=".count($this->event_stack)." [".key($this->event_stack)."]</div>" ;


        while ( list ( $key, $val ) = each ( $this->event_stack ) ) {

            // Just a handy shorthand for the GUI events
            $pre = substr ( $key, 0, 6 ) ;

            if ( $gui && $pre != 'XS_GUI' )
                break ;

            $counter++ ;

            if ( $debug) echo "<div style='border:dotted 1px #999;padding:18px;margin:18px;'><h5 style='color:#951'>$key : $this->current_event ($this->counter) ($counter)</h5> " ;
            if ( $debug) echo $gui ? 'GUI' : 'not GUI' ;

            // Current event? Start running actions!
            if ( !$gui && $key == $this->current_event) {
                $start = true ;
                if ( $debug) echo "<h4 style='background-color:red;'> key == current_event </h4><br>\n";
            }

            if ( $gui ) {

                if ( $debug) echo "<span style='color:yellow;background-color:green;'> ($counter)[GUI $key :: $event] \n" ;
                if ( $event == $key ) {
                    if ( $debug) echo "<span style='color:white;background-color:red;'>";
                    $r = $this->run_action ( $key, $debug ) ;
                    $ret[] = $r ;
                    $this->current_event = $key ;
                    if ( $debug) echo "[RUN ".count($r)."] \n" ;
                if ( $debug) echo " </span><br>\n";
                }
                if ( $debug) echo " </span><br>\n";

            }


            if ( $start ) {

                if ( $gui ) {

                    if ( $debug) echo "<span style='color:green'>[GUI $key]</span><br>\n";
                    
                } else {

                    // Not a GUI event, run normally
                    if ( $debug) 
                        echo " <span style='color:white;background-color:orange;'>RUNNING [$key] NORMAL</span> <br>\n";
                    $ret[] = $this->run_action ( $key, $debug ) ;
                    $this->current_event = $key ;
                }

            } else {

                    if ( $debug) 
                        echo "<span style='color:gray'>[nan $key]</span><br>\n";
            }

            if ( $debug) echo "</div> " ;
            
            if ( $gui && $key == $event ) {
                if ( $debug) echo "<h4 style='background-color:blue;color:white'> GUI && key == event, events run up to this one ($key) </h4><br>\n";
                // $start = false ;
                break ;
            }


        }

/*
         $count = -1 ;
        echo "[from $start] \n" ;
        
        foreach ( $this->event_stack as $idx => $ev ) {
            $count++ ;
            if ( $count >= $start ) {
                if ( $ev != $event ) {
                    if ( $pre != 'XS_GUI' ) {
                        // echo "[ev $ev] \n" ;
                        $this->run_action ( $ev ) ;
                    } else {
                        // echo "[not ev $ev] not run \n" ;
                    }
                } else {
                    echo "!!! [$ev] \n" ;
                    // $timer->add ( 'Event Controller : Running '.$ev.', and halting' ) ;
                    $ret[] = $this->run_action ( $ev ) ;
                    $this->current_event = $count + 1 ;
                    break ;
                }
            }
        }
         */

        // Log progress
        $this->glob->log->add ( 'EventStack : Action : End' ) ;

        return $ret ;
    }

    function run_action ( $event = null, $debug = false ) {

        global $timer ;
        $ret = array() ;

        // If the event comes from the XSLT rendering, the constants will
        // be text and needs to be converted back into constants
        if ( substr ( $event, 0, 6 ) == 'XS_GUI' )
            $event = constant ( $event ) ;


        // Right, so what event is it?

        switch ( $event ) {

            default:
            
                if ( substr ( $event, 0, 6 ) == 'XS_GUI' )
                    $ret[] = "<!-- GUI event : [$event]  --> " ;

                $this->glob->log->add ( 'EventStack : checking '.$event ) ;

                if ( isset ( $this->actions[$event] ) ) {
                    if ( count ( $this->actions[$event] ) > 0 ) {

                        // $this->glob->log->add ( 'EventManager : found '.count ( $this->actions[$event] ).' actions on '.$event ) ;

                        foreach ( $this->actions[$event] as $n ) {
                            $instance = $n['instance'] ;
                            $method = $n['method'] ;
                            $priority = $n['priority'] ;
                            $param = $n['param'] ;
                            // $debug = true ;
                            // echo "<hr><b>$event</b>: " . get_class($instance) . "->$method \n" ;
                            // $this->glob->log->add ( 'Plugin Controller : ' .get_class($instance)." - $method - $priority" ) ;
                            // $this->glob->log->add ( '___ EventManager : run_Action : ['.$method.']' ) ;
                            // $id = $event.'--'.$instance->metadata['name'].'.'.$method ;
                            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] EventStack : calling '.$method.' on '.get_class ( $instance )  ) ;
                            if ($this->debug) 
                                debug ( 'EventStack : calling '.$method.' on '.get_class ( $instance ), ' ' ) ;
                            $ret[] = call_user_func ( array ( $instance, $method ), $param ) ;
                        }
                    }
                }

                break ;
        }
        // $timer->add ( 'Plugin Controller : Action Done.' ) ;
        // echo "<pre>".print_r ( $ret,true ) ."</pre>" ;
        // $this->glob->log->add ( 'EventManager : run_Action : '.$event.' : Finished' ) ;
        return $ret ;
    }


    // Fill an array with classnames and pathnames (used for autoloading)

    function registerDirectories ( $path, &$arr = array () ) {

        try {

            
            $Directory = new RecursiveDirectoryIterator ( $path ) ;
            $Iterator = new RecursiveIteratorIterator($Directory ,
                            RecursiveIteratorIterator::SELF_FIRST );
            $Regex    = new RegexIterator($Iterator, '/^.+\.php$/i',
                            RecursiveRegexIterator::GET_MATCH ) ;

            /*
            $objects = new RecursiveIteratorIterator 
               ( new RecursiveDirectoryIterator ( $path ),
                     RecursiveIteratorIterator::SELF_FIRST ) ;
             */

            foreach($Regex as $entry => $object ) {
                $t = strpos ( $entry, 'disabled' ) ;
                if ( !is_dir ( $entry ) && !$t ) {
                    $fn = basename ( $entry ) ;
                    $idx = substr ( $fn, 0, strpos ( $fn, '.' ) ) ;
                    if ( $fn[0] !== '_' )
                        $arr[trim($idx)] = (string) $entry ;
                }
            }

        } catch ( exception $ex) {
            echo "<pre>{".$path."} \n\n" ;
            print_r ( $ex ) ;
            echo "</pre>\n\n" ;
        }

        return $arr ;
    }

    function get_standard_framework () {

       return array (

          // This is the lifespan of the whole event stack
          'XS_LIFESPAN' => array (

             // Anything we need to do before anything?
             'XS_PRE_HANDLING' => array (
                 'XS_PRE_MESSAGES'  => 'Pre message handling',
                 'XS_PRE_FRAMEWORK' => 'Pre-framework handling',
             ),

             // Setting up the environment and the xSiteable framework
             'XS_FRAMEWORK' => array (
                 'XS_INIT'        => 'Reading and parsing init and config files',
                 'XS_GLOBALS'     => 'Setting up the global framework objects',
                 'XS_SETTINGS'    => 'Dealing with PHP settings',
                 'XS_ENV'         => 'Dealing with PHP environment',
                 'XS_HTTP'        => 'Dealing with HTTP specifics',
                 'XS_REQUEST'     => 'Dealing with the request (GET/POST) variables',
                 'XS_REST'        => 'Dealing with the REST parts (if any)',
             ),

             // Middleware; that mysterious section between the raw environment
             // and the application you're making

             'XS_MIDDLEWARE' => array (
                 'XS_DATASTORE'        => 'Dealing with database connections and the manager',
                 'XS_TOPICMAPS'        => 'Setting up the Topicmaps stuff, including types and caching of those',
                 'XS_TOPICMAPS_CACHE'  => 'Specificly for caching Topic Maps topics, types and assocs for faster reuse',
                 'XS_REGISTER_QUERIES' => 'Set up queries',
                 'XS_MODULES'          => 'Dealing with modules',
                 'XS_PLUGINS'          => 'Dealing with plugins',
                 'XS_REGISTER_EVENTS'  => 'Setting up ownership and definitions of events',
                 'XS_AUTH'             => 'Dealing with users and authentication',
                 'XS_USERS'            => 'Setting up users',
                 'XS_USER'             => 'Dealing with the user object (should be final after this event)',
                 'XS_SECURITY'         => 'Setting up various security things',
                 'XS_DIRECTORIES'      => 'Prepare and set up directories (web and file)',
             ),

             // The application itself
             'XS_APPLICATION' => array (

                // Events for setting up our context
                'XS_PRECONDITION'  => 'Set up pre-conditions for the controller',
                'XS_CONTROL'       => 'Simple logical control before we dispatch',
                'XS_CONTEXT'       => 'What is our context so far? (it will make a premature attempt to discover page type, as well)',
                'XS_DISPATCHER'    => 'Dispatch (to Action Controller) so it can set up the action classes',

                // Note that XS_DISPATCHER will create a few various events based on the content-type
                // so XS_PAGE, XS_WIDGETS and XS_MENUS will be created when needed

                // Events that deal specifically with action classes (action class should
                // be dispatched by this stage)
                 
                'XS_ACTION' => array (
                   'XS_REGISTER_FUNCTIONALITY' => 'Register a controllers functionality',
                   'XS_MODEL'      => 'Action model',
                   'XS_VIEW'       => 'Action view',
                   'XS_CONTROLLER' => 'Action Controller (probably not needed in our model)',
                   'XS_LOGIC'      => 'Further logic as needed',
                ),

                // All is done, and you'd think all is over. And, mostly it is,
                // but let's recap and update a few bits and bobs

                'XS_POST_ACTION' => 'Finalize afew bits and bobs (the layout module hooks here)'
             ),

             // Output decision-time!
             'XS_OUTPUT' => 'Prepare for output!',

             // And for the GUI
             'XS_EVENT_GUI' => array (

                'XS_GUI_HEAD' => array (
                   'XS_GUI_META' => 'Event: GUI: META data',
                   'XS_GUI_LINK' => 'Event: GUI: LINK files',
                   'XS_GUI_CSS'  => 'Event: GUI: CSS files',
                   'XS_GUI_JS'   => 'Event: GUI: JavaScript files',
                ),

                'XS_GUI_BODY' => array (

                   'XS_GUI_UTILITY_NAVIGATION' => 'Event: GUI: Nav',

                   'XS_GUI_HEADER' => array (
                      'XS_GUI_HEADER_LOGO'  => 'Event: GUI: Header Logo',
                      'XS_GUI_HEADER_SPACE' => 'Event: GUI: Header space',
                   ),

                   'XS_GUI_BAR' => 'Event: GUI: Bar',
                   'XS_GUI_BREADCRUMB' => 'Event: GUI: Breadcrumb',

                   'XS_GUI_PRIMARY_NAVIGATION'   => 'Event: GUI: Nav',
                   'XS_GUI_SECONDARY_NAVIGATION' => 'Event: GUI: Nav',

                   'XS_GUI_SECTIONS' => array (
                      'XS_GUI_SECTION0' => array (
                         'XS_GUI_ADMIN_CONFIG_LIST' => false,
                      ),
                      'XS_GUI_SECTION1' => array (
                         'XS_GUI_ADMIN_CONFIG_EDIT' => false,
                      ),
                      'XS_GUI_SECTION2'  => 'Event: GUI: Section2',
                      'XS_GUI_SECTION3'  => 'Event: GUI: Section3',
                      'XS_GUI_SECTION4'  => 'Event: GUI: Section4',
                      'XS_GUI_SECTION5'  => 'Event: GUI: Section5',
                      'XS_GUI_SECTION6'  => 'Event: GUI: Section6',
                      'XS_GUI_SECTION7'  => 'Event: GUI: Section7',
                      'XS_GUI_SECTION8'  => 'Event: GUI: Section8',
                      'XS_GUI_SECTION9'  => 'Event: GUI: Section9',
                      'XS_GUI_SECTION10' => 'Event: GUI: Section10',
                      'XS_GUI_SECTION11' => 'Event: GUI: Section11',
                      'XS_GUI_SECTION12' => 'Event: GUI: Section12',

                      'XS_GUI_SECTION_PAGE_FUNCTIONALITY' => 'Event: GUI: Section for page functionality',
                       
                      'XS_GUI_SECTION_DEBUG'  => 'Event: GUI: Section DEBUG',
                   ),

                   'XS_GUI_FOOTER'            => 'Event: GUI',
                   'XS_GUI_BOTTOM_NAVIGATION' => 'Event: GUI: Nav',
                ),

             ),
              
            // Let's deal with post handling, cleanup, further processing
            'XS_POST_HANDLING' => array (

                'XS_POST_SCHEDULER' => 'Post scheduler handling',
                'XS_POST_MESSAGES'  => 'Post message handling',
                'XS_POST_FILES'     => 'Post file handling',
                'XS_POST_FRAMEWORK' => 'Post-framework events',
                'XS_POST_QUEUE'     => 'The misc. category',
                'XS_POST_CLEANUP'   => 'Various clean-up processes',
            ),
              
            'XS_TESTING' => 'Testing.'
          ),
       ) ;
    }
}
