<?php

    class  xs_WidgetInstance extends \xs\Store\Properties {

        public $id = null ;
        public $s = null ;
        public $_s = null ;
        public $p = null ;
        public $_p = null ;
        public $o = null ;
        public $_o = null ;
        
        public $_topic = null ;

        public $view = null ;

        function __construct ( $id ) {
            parent::__construct() ;
            $this->id = $id ;
            $this->s  = new \xs\Store\Properties () ;
            $this->p  = new \xs\Store\Properties () ;
            $this->o  = new \xs\Store\Properties () ;
            $this->_s  = new \xs\Store\Properties () ;
            $this->_p  = new \xs\Store\Properties () ;
            $this->_o  = new \xs\Store\Properties () ;
        }

    }

