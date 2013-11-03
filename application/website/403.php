<?php

    class xs_action_instance_error extends \xs\Action\Webpage {

        public $page = array (
            'title' => "Error : Forbidden (403)",
            'template' => '/layouts/403'
        ) ;
        
        public $id = 'widget-content-403' ;
        
        function ___action () {
            
            $this->glob->page->values['_widget_index'] = 
                    serialize ( array ( '0' => array ( '0' => $this->id ) ) ) ;
            $w = $this->_get_module ( 'widgets' ) ;
            $w->___widgets () ;
        }

    }
