<?php
	namespace xs\Store ;
        
	class Properties extends \xs\Core {
		
            public $values = array() ;
            public $false_values = array() ;
		
            function __construct ( $values = null ) {

                // Make sure we're basic (and have a global registry)
                parent::__construct() ;

                // If there's an array coming in with values, fill them in
                if ( $values != null )
                    $this->__inject ( $values ) ;

            }

            function  __call($name, $arguments = false ) {
                // debug_r ( $name ) ;
                // Yes, there's a property of that name
                if ( isset ( $this->values[$name] ) )
                    return $this->values[$name] ;

                if ( isset ( $arguments[0] ) )
                    return $arguments[0] ;

                return $arguments ;
            }

	    public function __get ( $idx ) {
	        if ( $idx == 'glob' )
	            return parent::$glob ;
	        if ( isset ( $this->values[$idx] ) )
	            return $this->values[$idx] ;
                
                $this->false_values[$idx] = true ;
                // debug_r ( $idx ) ;
                
                return null ;
	    }

	    public function __fetch ( $idx, $default = '' ) {
	        if ( isset ( $this->values[$idx] ) )
	            return $this->values[$idx] ;
                return $default ;
	    }

	    public function __set ( $idx, $value ) {
                $this->values[$idx] = $value ;
	    }

	    public function _set ( $idx, $value ) {
                $this->values[$idx] = $value ;
	    }

	    public function _remove ( $idx ) {
                if ( isset ( $this->values[$idx] ) )
                    unset ( $this->values[$idx] ) ;
	    }

            function __getArray () {
                return $this->values ;
            }

            function __get_array () {
                return $this->values ;
            }

            function __inject ( $values = array () ) {
                if ( is_array ( $values ) )
                    foreach ( $values as $idx => $value )
                        $this->values[$idx] = $value ;
            }
		
	}
	