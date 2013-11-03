<?php

    namespace xs\Semantics ;
    
    class Type extends \xs\Events\Plugin {

        public $handler = array () ;
        
        function register_handler ( $idx, $who ) {
            $this->handler[$idx] = $who ;
        }
    }
