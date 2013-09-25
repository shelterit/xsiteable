<?php

    class xs_module_appendix extends xs_Action {
        
        public $meta = array (
            'name' => 'Appendix module',
            'description' => 'Appendix : a generic, simple indexer',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;
        
        private $idx = array () ;
        private $idx_uid = array () ;

        // Shortcut for our API
        private $resource_base = '_api/module/appendix' ;
        
        function ___modules () {

        }
        
        function get_total_count () {
            return count ( $this->idx ) ;
        }
        
        function get_total_uids_count () {
            return count ( $this->idx_uid ) ;
        }
        
        function reset () {
            $this->idx = array () ;
            $this->idx_uid = array () ;
        }
        
        function find_by_uid ( $uid ) {
            if ( isset ( $this->idx_uid[$uid] ) )
                return $this->idx_uid[$uid] ;
            return array () ;
        }
        
        function find_all_uids_but ( $incoming_uids ) {
            echo "<li> [".count($incoming_uids)."] incoming</li> " ;
            echo "<li> [".count($this->idx)."] terms found</li> " ;
            echo "<li> [".count($this->idx_uid)."] registered UIDs</li> " ;
            $idx = array () ;
            foreach ( $this->idx as $term => $uids )
                foreach ( $uids as $uid => $state )
                    $idx[$uid] = $uid ;
            echo "<li> [".count($idx)."] appendix total found UIDs (in - reg = [". ( count($incoming_uids) - count($idx) ) ."])</li> " ;
            
            
            
            $ret = $non = array () ;
            foreach ( $idx as $uid )
                if ( isset ( $incoming_uids[$uid] ) )
                    $ret[$uid] = $uid ;
                else
                    $non[$uid] = $uid ;
            
            // debug_r ( $this->idx_uid ) ;
            
                
                
                
            echo "<li> of incoming, [".count($ret)."] <b>were</b> found in the appendix (save)</li> " ;
            echo "<li> of incoming, [".count($non)."] were <b>not</b> found in the appendix (delete)</li> " ;
             
            return $non ;
        }
        
        function delete_by_uids ( $in_uids ) {

            $count = $term_count = 0 ;
            
            // echo "[".count($this->idx_uid)."] " ;
            
            foreach ( $this->idx as $term => $uids ) {
                foreach ( $uids as $uid => $score ) {
                    if ( isset ( $in_uids[$uid] ) ) {
                        // echo "![$uid]! " ;
                    }
                }
            }
            
            
            // for each uid in the index
            foreach ( $this->idx_uid as $uid => $terms ) {
                
                // is the current uid in our list of things to delete?
                if ( isset ( $in_uids[$uid] ) ) {
                    
                    // echo "[$uid]=[".count($this->idx_uid[$uid])."] " ;
                    // debug_r ( $this->idx_uid[$uid], 'terms') ;
                    
                    foreach ( $terms as $term => $zilch ) {
                        // debug_r ( $term, $uid ) ;
                        if ( isset ( $this->idx[$term][$uid] ) ) {
                            unset ( $this->idx[$term][$uid] ) ;
                            $term_count++ ;
                        }
                    }
                    unset ( $this->idx_uid[$uid] ) ;
                            $count++ ;
                } else {
                    // echo "[$uid] " ;
                }
            }
            return array ( 'uid' => $count, 'term' => $term_count ) ;
        }
        
        
        
        function add_term ( $term, $uid ) {
            $term = (string) $term ;
            // debug ( $term, $uid ) ;
            if ( $uid !== null ) {
                
                if ( ! isset ( $this->idx[$term] ) )
                    $this->idx[$term] = array () ;
                
                if ( isset ( $this->idx[$term][$uid] ) )
                    $this->idx[$term][$uid]++ ;
                else
                    $this->idx[$term][$uid] = 1 ;
                
                $this->idx_uid[$uid][$term] = true ;
                /*
                if ( isset ( $this->idx_uid[$uid][$term] ) ) 
                    $this->idx_uid[$uid][$term]++ ;
                else 
                    $this->idx_uid[$uid][$term] = 1 ;
                 * 
                 */
                return true ;
            }
            return false ;
        }
        
        function add_terms ( $terms, $uid = null  ) {
            $count = 0 ;
            // echo "add_terms=[".count($terms)."] " ;
            if ( is_array ( $terms ) )
                foreach ( $terms as $term )
                    if ( $this->add_term ( $term, $uid ) )
                        $count++ ;
            return $count ;
        }
        
        function purge () {
            // find all empty terms, and remove them
            $count = 0 ;
            foreach ( $this->idx as $term => $uids ) {
                if ( count ( $uids ) == 0 ) {
                    unset ( $this->idx[$term] ) ;
                    $count++ ;
                    // echo "[$term] " ;
                }
            }
            return $count ;
            // echo "<div>Purged [".$count."] terms from the index.</div>" ;
        }
        
        function get_uids () {
            return $this->idx_uid ;
        }
        
        function find ( $terms ) {
            $found = array () ;
            foreach ( $terms as $term ) {
                if ( isset ( $this->idx[$term] ) ) {
                    foreach ( $this->idx[$term] as $uid => $count )
                        if ( !isset ( $found[$term][$uid] ) )
                            $found[$term][$uid] = $count ;
                        else
                            $found[$term][$uid] += $count ;
                }
            }
            return $found ;
        }

        function save_index () {
            $all = 'abcdefghijklmnopqrstuvwxyz0123456789' ;
            $t = array () ;
            // debug_r ( $this->idx ) ;
            echo "<p style='color:green;'>Saving all indexes " ;
            foreach ( $this->idx as $term => $idx )
                $t[substr ( $term, 0, 1 )][$term] = $idx ;
            for ( $n=0; $n<strlen($all); $n++ ) {
                // foreach ( $t as $letter => $idx ) {
                echo "." ;
                $letter = $all[$n] ;
                $file = 'application/datastore/idx_'.$letter.'.array' ;
                if ( isset ( $t[$letter] ) ) {
                    $idx = $t[$letter] ;
                    file_put_contents ( $file, serialize ( $idx ) ) ;
                } else {
                    if ( file_exists ( $file ) )
                        unlink ( $file ) ;
                }
            }
            echo " Done.</p>" ;
        }

        function load_all_index () {
            $all = 'abcdefghijklmnopqrstuvwxyz0123456789' ;
            echo "<p style='color:green;'>Loading all indexes " ;
            for ( $n=0; $n<strlen($all); $n++ )
                $this->load_index ( $all[$n] ) ;
            echo " Done.</p>" ;
        }

        function load_index_from_terms ( $terms ) {
            
            $letters = array () ;
            
            if ( is_array ( $terms ) )
                foreach ( $terms as $term )
                    $letters[substr ( $term, 0, 1 )] = substr ( $term, 0, 1 ) ;
            
            foreach ( $letters as $letter )
                $this->load_index ( $letter ) ;
            
            // debug_r ( $this->idx ) ;
            // debug_r ( $this->idx_uid ) ;
        }
        
        function load_index ( $letter ) {
            
            $file = 'application/datastore/idx_'.$letter.'.array' ;
            
            $idx = null ;
            if (file_exists ( $file ) ) {
                // echo "<p>Loading index [$letter] ... " ;
                $idx = unserialize ( @file_get_contents ( $file ) ) ;
                // if ( $letter == '1' ) debug_r ( $idx ) ;
                // echo "Done.</p>" ;
            }
            
            if ( is_array ( $idx ) ) {
                // echo "[".$letter.":" ;
                $this->idx = array_merge ( $this->idx, $idx ) ;
                $items = 0 ;
                $terms = 0 ;
                foreach ( $idx as $term => $uids ) {
                    $terms++ ;
                    foreach ( $uids as $uid => $score ) {
                        $items++ ;
                        $this->idx_uid[$uid][$term] = $uid ;
                        /*
                        if ( isset ( $this->idx_uid[$uid][$term] ) )
                            $this->idx_uid[$uid][$term]++ ;
                        else
                            $this->idx_uid[$uid][$term] = 0 ;
                         * 
                         */
                        // if ( $uid == '6d42b0b52687e16b79acfc83101f4413' )
                        //     debug_r ( $this->idx_uid[$uid], $term ) ;
                    }
                }
                // echo $terms.":".$items."] " ;
            }
        }
        
        function add_topic ( $tid, $source = null ) {
            
            $topic = null ;
            $source_type = 'prop' ;
            $source_id = 'value' ;
            
            $topics = $this->glob->tm->query ( array ( 'id' => $tid ) ) ;
            if ( is_array ( $topics ) && count ( $topics ) > 0 )
                $topic = $topics[$tid] ;
            
            if ( $topic === null )
                return null ;
            
            $data = array (
                '20' => $topic['label'],
                '2'  => $topic['value']
            ) ;
            if ( isset ( $topic['pub_short'] ) )
                $data['10'] = $topic['pub_short'] ;
            
            if ( isset ( $topic['pub_full'] ) )
                $data['1'] = $topic['pub_full'] ;
            
            if ( $source != null ) {
                
                if ( substr ( $source, 0, 5 ) == 'file:' ) {
                    $source_type = 'file' ;
                    $source_id = substr ( $source, 6 ) ;
                    if ( file_exists ( $source_id ) )
                        $data['1'] = file_get_contents ( $source_id ) ;
                }
                
            }
        }


    }
