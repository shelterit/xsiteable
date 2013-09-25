<?php

    class Keywords {

        private $glob =  null ;

        public $data = null ;
        public static $_data = null ;
        private static $loaded = false ;
        public $id = null ;

        function  __construct ( $glob = null ) {
            $this->glob = $glob ;
            // $id = substr ( $this->glob->request->q, 7 ) ;
            self::init() ;
            $this->data = self::$_data ;
        }
        
        static function init () {
            if ( ! self::$loaded ) {
                self::$_data = @unserialize (
                    @file_get_contents ( 'application/datastore/cmd_repo.arr' )
                );
                self::$loaded = true ;
            }
        }

        function by_uid ( $uid ) {
            if ( isset ( $this->data[$uid] ) )
                return $this->data[$uid] ;
            return null ;
        }

        function count ( $what ) {
            return $this->extractor (  array ( 'what' => $what, 'count' => true, 'reverse' => true ) ) ;
        }

        function extract ( $what, $pos = null, $grab = false ) {
            return $this->extractor ( array ( 'what' => $what, 'pos' => $pos, 'grab' => $grab ) ) ;
        }

        function get_facets ( $facets = array () ) {
            $ret = array () ;
            // $last = '' ;

            $path = implode ( '/', $facets ) ;
            
            // echo "[$facet] " ;
            foreach ( $this->data as $idx => $doc ) {

                // if ( $counter++ > 3 ) break ;

                $test = substr ( $doc['small'], 0, strrpos ( $doc['small'], '/' ) ) ;
                
                if ( trim ( $test ) == trim ( $path ) )
                   $ret[$doc['uid']] = $doc ;

                /*
                echo "[$test]\n[$t]\n " ;
                print_r ( $doc ) ;
                if ( isset ( $doc['facets'] ) ) {
                    $id = $doc['uid'] ;
                    $c = count ( $doc['facets'] ) ;
                    $bf = (int) $c - 1 ;
                    $cc = 0 ;
                    foreach ( $doc['facets'] as $f ) {
                        $b = urlsafe($f) ;
                        if ( $cc == $bf ) {
                            // echo "(".print_r ( $doc['facets'], true).") <br>" ;
                            // echo "_ [$b]<br>  _ [$last]<br>  _ [$facet] <br>" ;
                            if ( $last == $facet ) {
                                $ret[$id] = $f ;
                                // echo '*************************' ;
                            }
                        }
                        $cc++ ;
                        $last = $b ;
                    }
                }
                 *
                 */
            }
            // print_r ( $t ) ; die () ;
            return $ret ;
        }

        function extractor ( $arg = array () ) {

            $what  = $this->i (  'what', $arg, 'facets' ) ;
            $pos   = $this->i (   'pos', $arg, null ) ;
            $count = $this->i ( 'count', $arg, false ) ;
            $grab  = $this->i (  'grab', $arg, false ) ;

            $ee = $this->glob->request->section ;
            
            $x = array() ;
            foreach ( $this->data as $idx => $doc )
                if ( isset ( $doc[$what] ) ) {
                    $f = $doc[$what] ;
                    if ( $pos === null ) {
                        foreach ( $f as $idx => $fac ) {
                            if ( $count ) {
                                if ( !isset ( $x[$idx] ) ) $x[$idx] = 0 ;
                                $x[$idx]++ ;
                            } else {
                                $x = $this->rumble ( $x, $fac ) ;
                            }
                        }
                    } else {
                      if ( isset ( $f[$pos] ) ) {
                        if ( $grab ) {
                            $w = $this->glob->breakdown->section ;
                            if ( $f[$pos] == $w ) {
                                $x[$doc['uid']] = $doc['fileinfo']['filename'] ;
                            }
                        } else {
                            $x = $this->rumble ( $x, $f[$pos] ) ;
                        }
                      }
                    }
                }
            return $x ;
        }
 
        function i ( $idx = '', $var = null, $default = null ) {
            if ( isset ( $var[$idx] ) )
                return $var[$idx] ;
            return $default ;
        }

        function rumble ( $arr, $idx ) {
            $u = urlencode ( $idx ) ;
            $arr[$u] = $idx ;
            return $arr ;
        }

    }