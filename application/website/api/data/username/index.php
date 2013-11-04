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
        
        if ( $debug ) debug ( $this->_type->_user ) ;
        
        $query = array ( 
            'type' => array ( $this->_type->_user, $this->_type->_user_group ),
            'label:like' => $search,
            'limit' => 10
        ) ;
        
        $users = $this->glob->tm->query ( $query ) ;
        
        $r = array () ;
        
        foreach ( $users as $user )
            $r[] = array ( 'value' => $user['id'], 'name' => $user['label'] ) ;
        
        echo @json_encode ( $r ) ;

        die();

    }
}
