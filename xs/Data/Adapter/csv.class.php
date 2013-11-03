<?php

    namespace xs\Data\Adapter ;

    class csv extends \xs\Data\Adapter {

        function __construct ( $config = array () ) {
            parent::__construct ( $config ) ;
        }

        function query ( $query ) {
            // echo "!!!!" ;
		return array ( array (
                    array ( 'test' => 'My test' ),
                    array ( 'test' => 'My other test' ) ),
                ) ;
        }

        function fetch_all ( $query ) {
            // echo "@@@@@@" ;
		return array ( array (
                    array ( 'test' => 'My test' ),
                    array ( 'test' => 'My other test' ) ),
                ) ;
        }

    }
