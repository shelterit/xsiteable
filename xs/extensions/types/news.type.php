<?php

    class xs_type_news extends \xs\Semantics\Type {
        
        public $meta = array (
            'name' => 'News item',
            'description' => 'Definitions of a news item',
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
                
                'label' => 'text',
                'type' => 'type',
                
                'pub_short' => 'text|clean',
                'pub_long' => 'html|wiki'
                
            ) ;
            
            
        }


    }
