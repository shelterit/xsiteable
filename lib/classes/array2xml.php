<?php

 class array2xml {

        public $data;
        public $dom_tree;
        
        private $counter = 0 ;
        private $calls = 0 ;

        /**
         * basic constructor
         *
         * @param array $array
         */
        public  function __construct ( $array = array () ) {
            
            // echo '1. ' ;
            
            /*
            if(!is_array($array)){
                throw new Exception('array2xml requires an array', 1);
                unset($this);
            }
            if(!count($array)){
                throw new Exception('array is empty', 2);
                unset($this);
            }
             */
            // var_dump ( $array ) ;
        
            $x = (string) print_r ( $array, true ) ;
            
            // echo '2. ' ;
            $this->data = new DOMDocument('1.0');
    
            $this->dom_tree = $this->data->createElement('result');
  
            $this->recurse_node ( $array, $this->dom_tree ) ;

            $this->dom_tree->setAttribute ( 'iterations', $this->counter ) ;
            $this->dom_tree->setAttribute ( 'calls', $this->calls ) ;
            $this->dom_tree->setAttribute ( 'count_array', count ( $array ) ) ;
            
            $this->data->appendChild ( $this->dom_tree ) ;

        }
        
        /**
         * recurse a nested array and return dom back
         *
         * @param array $data
         * @param dom element $obj
         */
        private function recurse_node ( $data, $parent_obj, $level = 0 ) {
            
            $attrs = $nodes = $me = array () ;
            
            $this->counter++ ;
            
            if ( is_array ( $data ) && count ( $data ) > 0 )
                foreach ( $data as $key => $value )

                    if ( isset ( $key[0] ) && $key[0] == '@' ) {
                        $key = substr($key,1) ;
                        $attrs[$key] = $value ;
                    } else {
                        $nodes[$key] = $value ;
                    }
                
            if ( count ( $attrs ) > 0 ) 
                foreach ( $attrs as $key => $value )
                    $parent_obj->setAttribute ( $key, $value ) ;
            
            if ( count ( $nodes ) > 0 ) 
                foreach ( $nodes as $key => $value ) {
                
                    $this->calls++ ;

                    // create the element for the current node    
                    $me[$this->counter] = $this->data->createElement('item');

                    // set the default static attributes
                    $me[$this->counter]->setAttribute ( 'name', $key ) ;
                    $me[$this->counter]->setAttribute ( 'level', $level ) ;

                    // Attach new item to the main chain
                    $parent_obj->appendChild ( $me[$this->counter] ) ;

                    $this->recurse_node ( $value, $me[$this->counter], $level + 1 ) ;

                }
        }
        

        /**
         * get the finished xml as string
         *
         * @return string
         */
        public function saveXML(){
            return $this->data->saveXML();
        }
        
        /**
         * get the finished xml as DOM object
         *
         * @return DOMDocument
         */
        public function get () {
            return $this->data ;
        }

    }
