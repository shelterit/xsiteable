<?php

class xs_action_instance extends \xs\Action\Generic {

    function ___action () {

        $debug = false ;
        
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        if ( ! $debug ) header('Content-type: application/json');

        $search = $this->glob->request->__fetch ( 'search', '' ) ;
        
        if ( strlen ( $search ) < 1 ) {
            echo @json_encode ( array ( array ( 'value' => '', 'name' => 'Type a bit more ...' ) ) ) ; 
            die () ;
        }
        
        $in = $this->glob->breakdown->selector ;
        
        $e = explode ( '|', $in ) ;
        $res = array () ;
        $error = array () ;
        $errors = false ;
        
        foreach ( $e as $p ) {
            $test = $this->_type->$p ;
            if ( (int) $test == 0 ) { 
                $p = '_' . $p ;
                $test = $this->_type->$p ;
            }
            if ( (int) $test == 0 ) {
                $error[$p] = true ;
                $errors = true ;
            } else
                $res[$p] = $test ;
        }
        
        if ( $errors ) {
            echo @json_encode ( array ( array ( 'value' => '', 'name' => 'Trying to find a non-existing type ['.print_r($error,true).'] ...' ) ) ) ; 
            die () ;
        }
        
        $query = array ( 
            'type' => $res,
            'label:like' => $search,
            'limit' => 10
        ) ;
        
        $items = $this->glob->tm->query ( $query ) ;
        
        $r = array () ;
        
        foreach ( $items as $item )
            $r[] = array ( 'value' => $item['id'], 'name' => $item['label'] ) ;
        
        echo @json_encode ( $r ) ;

        die();

    }
}
