<?php

class _test extends _admin {

    function __construct ( $conf = array () ) {
        parent::__construct ( $conf ) ;
    }

    function action ( $what = array () ) {

        $this->load () ;

        echo "<pre>" ;
        
        foreach ( $this->docs as $uid => $file ) {
        
            echo "*** $uid *** \n" ;
            print_r ( $file ) ;
        }
        
        echo "</pre>" ;
        
        die() ;
    }

}
