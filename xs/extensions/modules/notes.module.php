<?php

    class xs_module_notes extends \xs\Events\Module {

        public $meta = array (
            'name' => 'Notes module',
            'description' => 'Attach notes to things',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // if types are to be defined and used
        protected $___register_types = array ( 
            '_note' => 'A note',
            'has_note' => 'Has a note',
        ) ;
        
        function ___modules () {
            
            // notes can be attached to;
            // $this->_register_
        }
        
        function _http_action ( $in = null ) {
            $method = $this->glob->request->get_method () ;
            $this->$method () ;
        }
        
    }
