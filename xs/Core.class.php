<?php
    namespace xs ;
    
    class Core {

        public static $request_random_number = null ;
        public static $xs_version = '0.7.8' ;
        public static $app_version = null ; // will be set by the app

        public static $glob = null ;
        public static $glob_type = null ;

        public static $dir = null ;
        public static $dir_xs = null ;
        public static $dir_lib = null ;
        public static $dir_app = null ;
        public static $dir_cache = null ;
        public static $dir_datastore = null ;
        public static $dir_log = null ;
        public static $dir_static = null ;

        function __construct () {
        }
        
        public function __get ( $idx ) {
            
            if ( $idx == 'glob' ) {
                return self::$glob ;
            } elseif ( $idx == 'glob_type' ) {
                return self::$glob_type ;
            } else {
                if ( isset ( $this->$idx ) )
                    return $this->$idx ;

                $trace = debug_backtrace(false);
                // print_r ( $trace ) ; die() ;
                $caller = $trace[2];
                $place = '';
                $parCaller = $trace[1];
                if (array_key_exists('class', $caller)) {
                    $place = $caller['class']."::".$caller['function'];
                } else {
                    $place = $caller['function'];
                }
                if ( isset ( $parCaller['line'] ) )
                    $place .= ' (line: '.$parCaller['line'].") ";

                echo "<div style='padding:4px;margin:4px;border:solid 1px #999;'>Oops! <span style='color:blue'>".  get_class( $this ) . "</span>-&gt;<span style='color:red'>$idx</span> not found. Called through ".$place." (xs_Core->__get)</div>" ;

                // throw new Exception('error', 2);

            }
        }

        public function __if_set ( $var, $default ) {
            if ( isset ( $var ) )
                return $var ;
            return $default ;
        }

        public static function static_setup ( $root_dir = '.' ) {

            // Create a random number for the request
            self::$request_random_number = rand() ;

            $root = dirname ( $_SERVER['SCRIPT_FILENAME'] ) ;
            
            // echo '[[';print_r ( dirname ( __FILE__ ) ) ;echo ']] ';
            // echo '[[';print_r (  __FILE__ ) ;echo ']] ';
            
            // Inject directory paths for various bits of our system
            self::$dir     = $root ;
            self::$dir_xs  = dirname ( __FILE__ ) ;
            self::$dir_lib = $root . '/lib' ;
            self::$dir_app = $root . '/application' ;
            self::$dir_cache = $root . '/cache' ;
            self::$dir_log = $root . '/log' ;
            self::$dir_datastore = $root . '/application/datastore' ;
            self::$dir_static = $root . '/static' ;

            // Create a global registry / property object
            self::$glob = new \xs\Store\Properties () ;
            self::$glob_type = new \xs\Store\Properties () ;

            // Make some handy constants out of them, and a few other
            self::static_make_constants() ;

        }

        public static function static_make_constants () {

            // First, a batch of constants we'll use for all sorts of things
            define ( 'NONE', NULL ) ;

            define ( 'XS_PAGE_AUTO', 'system' ) ;
            define ( 'XS_PAGE_STATIC', 'static' ) ;
            define ( 'XS_PAGE_DYNAMIC', 'dynamic' ) ;
            define ( 'XS_PAGE_RESOURCE', 'resource' ) ;

            define ( 'XS_NAMESPACE_NUT' , 'xmlns:nut="http://schema.shelter.nu/nut"' ) ;

            define ( 'XS_CONTEXT_USER', 'XS_CONTEXT_USER' ) ;
            define ( 'XS_CONTEXT_USER_GROUP', 'XS_CONTEXT_USER_GROUP' ) ;

            define ( 'XS_CONTEXT_CLASS', 'XS_CONTEXT_CLASS' ) ;
            define ( 'XS_CONTEXT_INSTANCE', 'XS_CONTEXT_INSTANCE' ) ;

            define ( 'XS_CONTEXT_PAGE', 'XS_CONTEXT_PAGE' ) ;
            define ( 'XS_CONTEXT_SECTION', 'XS_CONTEXT_SECTION' ) ;


            // Next, define some priorities
            define ( 'XS_SYSTEM',   -10 ) ;
            define ( 'XS_MODULE',    -8 ) ;
            define ( 'XS_PLUGIN',    -6 ) ;
            define ( 'XS_WIDGET',    -4 ) ;
            define ( 'XS_RESOURCE',  -2 ) ;

            // Finally, define some shortcuts for often-used paths
            define ( 'XS_DIR_XS', self::$dir_xs ) ;
            define ( 'XS_DIR_LIB', self::$dir_lib ) ;
            define ( 'XS_DIR_APP', self::$dir_app ) ;
            define ( 'XS_DIR_CACHE', self::$dir_cache ) ;
            define ( 'XS_DIR_LOG', self::$dir_log ) ;
            define ( 'XS_DIR_DATASTORE', self::$dir_datastore ) ;
            define ( 'XS_DIR_STATIC', self::$dir_static ) ;
        }
    }


