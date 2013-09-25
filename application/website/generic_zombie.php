<?php

    class xs_action_instance extends xs_Action_Webpage {

        public $page = array (
            'title' => 'not set',
            'template' => '/layouts/col-1'
        ) ;

        private $running = false ;
        
        public function __call($name, $arguments) {
            $this->parent->$name ( $arguments ) ;
        }        

        function _http_action ( $in = null ) {
            $this->running = true ;
            $method = $this->glob->request->get_method () ;
            if ( $this->parent !== null )
                $this->parent->$method () ;
            else
                echo "Error: Zombie can't do much by himself." ;
        }
        
        function ___gui_section0 () {
            return $this->parent->_gui_section0 () ;
        }
        function ___gui_section1 () {
            return $this->parent->_gui_section1 () ;
        }
        function ___gui_section2 () {
            return $this->parent->_gui_section2 () ;
        }
        function ___gui_section3 () {
            return $this->parent->_gui_section3 () ;
        }

    }
