<?php

    class xs_module_topic_maps extends xs_Action {

        public $meta = array (
            'name' => 'Pages module',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;
        
        // if types are to be defined and used
        protected $___register_types = array ( 

            // topic
            '_topic' => 'A topic',
            '_topic_id' => 'A topics id',
            '_topic_name' => 'A topics name',
            '_topic_type' => 'A topics type',
            '_topic_label' => 'A topics label',
            '_topic_scheme' => 'A topics security scheme',
            
            // associations
            '_assoc' => 'An association',
            '_assoc_id' => 'An associations id',
            '_assoc_type' => 'An associations type',
            '_assoc_member' => 'An associations member',
            '_assoc_member_topicref' => 'An associations members topic reference',
            '_assoc_member_roleref' => 'An associations members role reference',
            
            
        ) ;
        private $registry = array () ;
        
        public $cache = null ;
        public $cache_alias = null ;
        
        private $resolve = null ;
        
        function resolve_topic ( $what, $type ) {
            if ( ! $this->resolve ) {
                $this->resolve = $this->glob->config->parse_section ( 'resolve' ) ;
            }
            $res = $this->resolve ;
            $n = $this->glob->tm->lookup_topics ( array ( $type => $type ) ) ;
            // debug_r($n,$type);
            if ( isset ( $n[$type] ) ) {
                // yes, found the type
                $t = $n[$type]['name'] ;
                if ( isset ( $res[$t] ) ) {
                    return $res[$t][0]['@label'] ;
                }
            }
            // debug_r ( $this->resolve ) ;
        }
        
        function get_assoc ( $input = array () ) {
            // debug_r($input,'input');
            $id = null ;
            if ( isset ( $input['lookup'] ) ) {
                $id = $input['lookup'] ;
                if ( is_array ( $id ) )
                    $id = array_keyify ( $id ) ;
            }
            
            $type = null ;
            if ( isset ( $input['type'] ) ) 
                $type = $input['type'] ;
            
            $filter = null ;
            if ( isset ( $input['filter'] ) ) 
                $filter = $input['filter'] ;
            
            $t = new xs_TopicMaps_Assoc ( 
                $this->glob->tm->query_assoc ( array ( 
                    'type' => $type, 
                    'member_id' => $id 
            ) ) ) ;
            $t->inject ( array ( 'type' => $type ) ) ;
            
            // debug_r($t,'tags '.$filter);
            
            if ( isset ( $input['filter'] ) ) 
                $t->remove_members_not_of_type ( $input['filter'] ) ;
            
            if ( isset ( $input['filter_in'] ) ) 
                $t->remove_members_of_type ( $input['filter_in'] ) ;
            
            $t->member_resolve () ;
            
            return $t->__get_array () ;
            // $this->glob->stack->add ( 'xs_assoc_tags', $t->__get_array () ) ;
            // debug_r($t->__get_array (),'tags');
        }
        
        
        function ___topicmaps_cache () {
            
            // echo "[topicmaps_cache] " ;
            // debug ( $this->_get_type () ) ;
            
            $alias = false ;
            if ( isset ( $this->glob->config['framework']['type_alias'] ) 
               && $this->glob->config['framework']['type_alias'] == true )
                $alias = true ;
            
            $all_types = $this->_get_type () ;
            if ( $alias ) $all_types_alias = $this->_get_type_alias () ;
            $final_types = $final_alias = array () ;
            
            foreach ( $all_types as $where => $types )
                foreach ( $types as $type => $desc )
                $final_types[$type] = $desc ;
            
            if ( $alias ) 
                foreach ( $all_types_alias as $where => $olds )
                    foreach ( $olds as $old => $new )
                        $final_alias[$old] = $new ;
            
            
            // debug ( $all_types ) ;
            
            $keys = $therest = array_keyify ( array_keys ( $final_types ) ) ;
            if ( $alias ) $keys_alias = $therest_alias = array_keyify ( array_keys ( $final_alias ) ) ;
            $string = $string_alias = '' ;
            
            // pick out the first and third character of the name, and concatenate
            // a whole bunch of them to create a hash we can test against
            
            foreach ( $keys as $idx )
                $string .= 'c'.count($keys).'-'.$idx[0].$idx[2] ;
            
            $hash = md5 ( $string ) ;
            
            if ( $alias ) {
                foreach ( $keys_alias as $idx )
                    $string .= 'c'.count($keys_alias).'-'.$idx[0].$idx[2] ;

                $hash_alias = md5 ( $string_alias ) ;
            }
            
            // debug_r ( $keys ) ;
            // create a cache
            $this->cache = new xs_Cache ( 'type_cache_'.$hash, array ( 'time' => '1 week', 
                'cache_dir' => $this->glob->config['framework']['cache_directory'] ), $this->glob ) ;
            
            if ( $alias ) 
                $this->cache_alias = new xs_Cache ( 'type_cache_alias_'.$hash_alias, array ( 'time' => '1 week', 
                    'cache_dir' => $this->glob->config['framework']['cache_directory'] ), $this->glob ) ;
            
            // what is the filename?
            $f = $this->cache->getFilename () ;
            if ( $alias ) $f_alias = $this->cache_alias->getFilename () ;
            
            // the temporary data array
            $data = $data_alias = $test = array () ;
            
            // debug ( $this->cache->has_expired () ) ;
            
            if ( ! file_exists ( $f ) || $this->cache->has_expired () ) {
                
                // cache doesn't exist; go through the list, and compare it against the database
                // creating new or updating old as we go along, and put the data
                
                // 1. check topicmap for existing types
                $res = $this->glob->tm->query ( array ( 'name' => $keys ) ) ;
                
                // echo '[put]' ;
                if ( count ( $res ) > 0 ) {
                    
                    // yes, found some topics; update properties with this value
                    foreach ( $res as $topic_id => $topic ) {
                        $data[$topic['name']] = $topic_id ;
                        if ( ! isset ( $test[$topic['name']] ) )
                            $test[$topic['name']] = $topic_id ;
                        unset ( $therest[$topic['name']] ) ;
                    }
                    
                    // debug_r ( $data, count($data) ) ;
                    // debug_r ( $test, count($test) ) ;
                    
                    
                    // these were not found; create them, and update properties with its new value
                    foreach ( $therest as $idx ) {

                        $desc = null ;
                        if ( isset ( $final_types[$idx] ) )
                            $desc = $final_types[$idx] ;
                        
                        if ( $desc !== null ) {

                            $new_id = $this->glob->tm->create ( array (
                                'label' => $desc,
                                'name' => $idx,
                            ) ) ;

                            $data[$idx] = $new_id ;
                        
                        } else {
                            echo "[fail:$idx] " ;
                        }
                    }
                }

                $this->cache->put ( $data ) ;
                
            } else {

                // file exist; cached data should be fine, so get the data
                $data = $this->cache->get () ;
            }
            
            // debug_r ( $data ) ;
            
            if ( $alias ) {
                if ( ! file_exists ( $f_alias ) || $this->cache_alias->has_expired () ) {

                    // cache doesn't exist; go through the list, and compare it against the database
                    // creating new or updating old as we go along, and put the data

                    // 1. check topicmap for existing types
                    $res = $this->glob->tm->query ( array ( 'name' => $keys_alias ) ) ;

                    // echo '[put]' ;
                    if ( count ( $res ) > 0 ) {

                        // yes, found some topics; update properties with this value
                        foreach ( $res as $topic_id => $topic ) {
                            $data_alias[$topic['name']] = $topic_id ;
                            unset ( $therest_alias[$topic['name']] ) ;
                        }

                        // these were not found; create them, and update properties with its new value
                        foreach ( $therest_alias as $idx ) {

                            $desc = null ;
                            if ( isset ( $final_alias[$idx] ) )
                                $desc = $final_alias[$idx] ;

                            if ( $desc !== null ) {

                                $new_id = $this->glob->tm->create ( array (
                                    'label' => $desc,
                                    'name' => $idx,
                                ) ) ;

                                $data_alias[$idx] = $new_id ;

                            } else {
                                // echo "[fail:$idx] " ;
                            }
                        }
                    }

                    $this->cache_alias->put ( $data_alias ) ;

                } else {

                    // file exist; cached data should be fine, so get the data
                    $data_alias = $this->cache_alias->get () ;
                }
            }
            
            if ( is_array ( $data ) )
                foreach ( $data as $idx => $value )
                    $this->_type->$idx = $value ;
            
            // debug_r ($this->_type ) ;

            if ( $alias ) {
                if ( is_array ( $final_alias ) ) {
                    foreach ( $final_alias as $old => $new ) {
                        if ( isset ( $data_alias[$old] ) ) {
                            $this->_type->$new = $data_alias[$old] ;
                        }
                    }
                }
            }
            
            // debug_r ( $this->cache->status () ) ;
            
            
            // debug_r ( $this->_type->__get_array () ) ;
            // debug_r ( $this->_type ) ;
            // debug_r ( $final_alias ) ;
            // debug_r ( $data_alias ) ;
        }
        
    }
