<?php

   // This module provides an API for dealing with dates, times, and date ranges.
   // It creates lists, averages, and so on, useful for archives, news, blogs, etc.

    class xs_module_datetime extends \xs\Events\Module {

        public $meta = array (
            'name' => 'DateTime module',
            'description' => 'Deal with time immortal',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
        ) ;

        function ___init () {

            // Create a global date object. Do we have a time-zone setting?
            if ( isset ( $this->glob->config['website']['time-zone'] ) )
                $this->glob->date = new DateTime ( null, new DateTimeZone ( $this->glob->config['website']['time-zone'] ) ) ;
            else // No? Just use the server's time-zone
                $this->glob->date = new DateTime ( null ) ;

            $this->_register_resource ( XS_MODULE, '_api/modules/datetime' ) ;
            
        }

        function _get () {
            return "GET" ;
        }

    }
