<?php

    class xs_Type extends xs_EventStack_Plugin {

        public $handler = array () ;
        
        function register_handler ( $idx, $who ) {
            $this->handler[$idx] = $who ;
        }
    }
