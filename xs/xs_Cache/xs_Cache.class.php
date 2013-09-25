<?php

    class xs_Cache {

        // Configuration, with defaults

        private $config = array (
                'id' => 'unknown',
                'time' => '+30 seconds',
                'cache_dir' => 'cache',
                'service' => 'unknown'
        ) ;

        private $status = '' ;

        private $calculated = false ;

        public $file = null ;
        private $filename = null ;
        public  $start_timestamp = 0 ;
        public  $now_timestamp = 0 ;
        public  $file_timestamp = 0 ;
        public  $cache_timestamp = 0 ;
        private $file_exist = false ;

        private $glob ;

        private $return_cached_object = false ;
        
        private $unserialized = null ;
        private $serialized = null ;
        

        function __construct ( $id, $config = array (
            'id' => 'unknown',
            'time' => '+30 seconds',
            'cache_dir' => 'cache',
            'service' => 'unknown'
        ), $glob = null ) {

            $this->glob = $glob ;

            foreach ( $config as $idx=>$val )
                    $this->config[$idx] = $val ;

            if ( isset ( $this->config['website']['time-zone'] ) )
                date_default_timezone_set ( $this->config['website']['time-zone'] ) ;

            $this->config['id'] = $id ;

            $this->filename = $this->config['cache_dir'].'/'.$this->config['id'].'.tmp' ;

            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] cachette->construct [{$this->filename}]"  ) ;
            
            $this->calculate () ;
        }

        function reset () {
            $this->config['time'] = $this->now_timestamp - 1000 ;
            @unlink ( $this->filename ) ;
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] cachette->reset : Force recalculation!"  ) ;
            $this->calculate () ;
        }
        
        function has_expired () {
            if ( $this->now_timestamp > $this->cache_timestamp )
                return true ;
            return false ;
        }

        function calculate () {

            $this->now_timestamp = time() ;

            if ( $this->config['time'] == null )
                $this->config['time'] = $this->now_timestamp - 1000 ;
            
            $this->start_timestamp = $this->now_timestamp ;
            $this->file_timestamp = 0 ;
            $this->cache_timestamp = strtotime ( $this->config['time'], $this->now_timestamp ) ;

            $this->status .= '| service ['.$this->config['service'].'] ' ;

            // Does the file currently exist?
            $this->file_exist = file_exists ( $this->filename ) ;

            if ( $this->file_exist ) {

                    $this->status .= '| file exists ' ;
                    $this->file = 'true' ;
                    $this->file_timestamp = @filemtime ( $this->filename ) ;
                    $this->start_timestamp = $this->file_timestamp ;

                    $this->cache_timestamp = strtotime ( $this->config['time'], $this->start_timestamp ) ;

            } else {

                    $this->file = 'false' ;
                    $this->status .= '| no file ' ;
                    $this->cache_timestamp = 0 ;

            }

            $this->calculated = true ;

            $this->return_cached_object = $this->cached() ;

            $this->status .= "{ init, \nfilename(".$this->filename.
                    ") filestamp=(".$this->disp($this->file_timestamp).
                    ") \n timestamp=(".$this->disp($this->now_timestamp).
                    ") cachestamp(".$this->disp($this->cache_timestamp).")\n return_cached=".$this->return_cached_object.")\n} \n" ;

            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] cachette->calculate [{$this->status}]"  ) ;

        }

        function cached () {

            if ( $this->now_timestamp > $this->cache_timestamp ) {

                $this->status .= '| not cached ' ;
                return false ;

            } else {

                $this->status .= '| cached! ' ;
                return true ;

            }

        }

        function put ( $stuff ) {

            $this->status .= '| writing cached file ('.$this->filename.') ' ;
            // echo ' ('.$this->filename.') ' ;
            Filesystem::fileWrite ( $this->filename, serialize ($stuff) ) ;
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] cachette->put [{$this->filename}]"  ) ;

            $this->calculate () ;
            // echo "#" ;
            return $stuff ;
        }

        function get () {

            if ( $this->calculated != true )
               $this->calculate () ;

            if ( $this->return_cached_object ) {
                $this->status .= '| reading cached file ('.$this->filename.') ' ;
                $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] cachette->get [{$this->filename}] file READ"  ) ;
                
                $this->unserialized = Filesystem::fileRead ( $this->filename ) ;
                $this->serialized = unserialize ( $this->unserialized ) ;
                
                return $this->serialized ;
            }

            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] cachette->get [{$this->filename}] == NULL (file not read)"  ) ;
            return false ;
        }

        function status () {
                return $this->status ;
        }

        function getID () {
                return $this->config['id'] ;
        }

        function getFilename () {
                return $this->filename ;
        }

        function getTimestamp () {
                return @filemtime ( $this->filename ) ;
        }

        function disp ( $timestamp ) {
                $date_format = "d.m.Y-H:i:s" ;
                return date ( $date_format, $timestamp ) ;
        }

    }