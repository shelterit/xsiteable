<?php

class xs_DocumentManager_LUT {

    private $_lut = array () ;
    
    function add ( $counter, $item, $idxs = array () ) {
        if ( is_object ( $item ) ) {
            foreach ( $idxs as $i ) {
                $this->_lut[$i][$item->$i] = $counter ;
            }
        }
    }
    
    function find ( $what, $in ) {
        if ( isset ( $this->_lut[$in][$what] ) )
            return $this->_lut[$in][$what] ;
        return null ;
    }
}
