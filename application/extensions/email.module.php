<?php

/*
 */

    class xs_module_email extends \xs\Events\Plugin {

        public $meta = array (
            'name' => 'Email module',
            'description' => 'Deals with emails (notifications), composing, handling and sending.',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // Shortcut for our API
        private $resource_base = '_api/module/email' ;

        function _get () {

            // handle incoming GET requests; read an email

        }

        function _post () {

            // handle incoming POST requests; create an email
        }

    }
