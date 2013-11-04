<?php

    class xs_action_instance extends \xs\Action\Webpage {

        public $page = array (
            'title' => '',
            'template' => '/layouts/col-3'
        ) ;
        
        private $resource_id = null ;

        function POST ( $arg = null ) {
            // echo "POST!" ;
            $fields = $this->glob->request->__get_fields () ;
            // print_r ( $fields ) ;
            // die () ;
        }
        
        function ___action () {

            $this->set_template ( '/layouts/col-3-breadcrumbs' ) ;

        }

    }
