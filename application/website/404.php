<?php

    class xs_action_instance_error extends xs_Action_Webpage {

        public $page = array (
            'title' => "Error : Page not found (404)",
            'template' => '/layouts/404'
        ) ;
        
        public $id = 'widget-content-404' ;
        
        function ___action () {
            
            $this->glob->page->values['_widget_index'] = 
                    serialize ( array ( '0' => array ( '0' => $this->id ) ) ) ;
            $w = $this->_get_module ( 'widgets' ) ;
            $w->___widgets () ;
        }

    }
