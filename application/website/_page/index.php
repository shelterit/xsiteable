<?php

class xs_action_instance extends xs_Action_Webpage {

    function ___action () {
        
        $f = $this->glob->breakdown->section ;
     
        $tt = $this->glob->tm->query ( array ( 'id' => $f ) ) ;
        $t  = end ( $tt ) ;
        
        if ( isset ( $t['id'] ) && $t['id'] == $f ) {
            
            // bingo!
            $u = str_replace ( '|', '/', substr ( $t['name'], 22 ) ) ;
            
            $r = $this->glob->dir->home . '/'.$u ;
            // debug($r);
            // redirect to the right place
            $this->glob->request->_redirect = $r ;
            
        } else {
            echo "No topic; no redirect!" ;
            die () ;
        }
            
    }
}
