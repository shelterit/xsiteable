<?php

    class xs_module_scheduler extends xs_Action {

        public $meta = array (
            'name' => 'Scheduler module',
            'description' => 'Schedule any task, at any interval',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // Shortcut for our API
        private $resource_base = '_api/module/scheduler' ;

        function ___register_events () {
            
            // this class owns and deals with these basic events
            $this->_register_event ( XS_MODULE, 'on_scheduler_new' ) ;
            
        }
        
        function ___on_scheduler_new ( $param ) {
            // print_r ( $param ) ;
        }

        
        function ___register_queries () {

            $this->glob->data->register_query (

            // use the default xs (xSiteable) datasource
            'xs',

            // identifier for our query
            'scheduler-all-items',

            // the query in question (passing in an array sends the query to
            // the Topic Maps engine (that builds its own SQL) rather than
            // a generic SQL

            array (
                'select'      => 'id,type1,label,m_p_date,m_p_who,m_u_date,m_u_who,parent',
                'type'        => array ( 
                    // $this->_type->_event 
                    ),
                'sort_by'     => 'm_c_date DESC',
                'lookup_name' => 'm_p_date,m_p_who',
                // 'count'       => array ( 'what' => 'sub_topics', 'type' => $this->_type->_comment )
                'return'      => 'topics'
            ),

            // the timespan of caching the result
            '+1 minute'
            ) ;
            
        }
        
        function ___output () {
            
        // function ___post_scheduler () {

            // Get scheduler lock with last used time
            
            $lock = $this->get_lock () ;
            
            if ( ! $lock ) {
                
                // echo '[nolock]' ;

                $this->make_lock () ;

                // no lock file ; make one, and do stuff

                $obj = $this->glob->data->get_query_object ( 'scheduler-all-items' ) ;
                $events = $this->glob->data->get ( 'scheduler-all-items' ) ;
                $cache =  $obj['cache'] ;

                $age = $cache->cache_timestamp ;

                // echo "[age of data: " . $cache->status () . "]" ;

                // var_dump ( $events ) ;


                // 1. Get all events from database

                // 2. Parse all date ranges

                // 3. Match those ranges against right now

                // 4. Do the things that matches now

                // 5. If event is recurring, calculate next event

                // 6. Update database with new event dates
                
                $this->delete_lock () ;
            } // else { echo '[LOCKED]' ; } 
            
        }
        
        function get_lock () {
            // echo "[get_lock]" ;
            return @file_get_contents ( xs_Core::$dir_app . '/datastore/scheduler.lock' ) ;
        }
        
        function make_lock () {
            // echo "[make_lock]" ;
            @file_put_contents ( xs_Core::$dir_app . '/datastore/scheduler.lock', serialize ( time ( 'now' ) ) ) ;
        }
        
        function delete_lock () {
            // echo "[delete_lock]" ;
            @unlink ( xs_Core::$dir_app . '/datastore/scheduler.lock' ) ;
        }

        function _get () {

            // handle incoming GET requests (mostly, meaning finding out about scheduled stuff

        }

        function _post () {

            // handle incoming POST requests (mostly, meaning creating scheduled stuff)
        }

    }
