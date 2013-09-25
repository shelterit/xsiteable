<?php

    class xs_type_tag extends xs_Type {
        
        public $meta = array (
            'name' => 'Tags',
            'description' => 'Definitions of tags',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;
        
        public $schema = array () ;
        
        function __construct () {
            
            // Hi, mum!
            parent::__construct();
            
            // $this->_register_as_type ( )
            $this->schema = array ( 
                
            ) ;
            
            
        }


    }
