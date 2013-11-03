<?php

class xs_action_instance extends \xs\Action\Webpage {

    function ___action () {
        
        // Parse the request URI a bit differently, looking for facets (f[n])
        $this->glob->breakdown->_parse ( '{concept}/{f1}/{f2}/{f3}/{f4}/{f5}/{f6}/{f7}/{f8}/{f9}' ) ;
        
        $f = $this->glob->breakdown ;
        
        $current_facets = array () ;
        $last = $path = '' ;

        
        // Pull out all facets (well, first 9) from the request URI
        for ( $n=1; $n < 10; $n++) {
            $x = trim ( $f->__fetch ( "f{$n}", '' ) ) ;
            if ( $x != '' ) {
                $current_facets[urlsafe($x)] = $x ;
                $last = urlsafe($x) ;
                $path .= $x . '/' ;
            }
        }
        
        // just fix the path by removing trailing slash
        $path = substr ( $path, 0, -1 ) ;
        
        if ( trim ( $path ) != '' )
            $path = '/'.$path ;
        
        // redirect to the right place
        $this->glob->request->_redirect = $this->glob->dir->home . '/documents'.$path ;
        
        
    }
}

