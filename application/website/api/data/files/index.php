<?php

class xs_action_instance extends \xs\Action\Generic {

    function ___action () {

        // get the document manager module
        $dms = $this->_get_module ( 'dms' ) ;
        
        // get the appendix!
        $appendix = $this->_get_module ( 'appendix' ) ;

        $type = isset ( $_POST['mimetype'] ) ? $_POST['mimetype'] : null ; 
        $xhr = @$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'; 

        $fields = $this->glob->request->__get_fields () ;
        $base_folder = @$this->glob->config['dms']['destination_folder'] ;
        
        $pick_default = isset ( $this->glob->config['dms']['next_review_months'] ) ? $this->glob->config['dms']['next_review_months'] : 24 ;
        $next_review_date_pick = $this->glob->request->__fetch ( 'next_review_date', $pick_default ) ;
        $next_review_date = date ( "Y-m-d", strtotime ( "+{$next_review_date_pick} months" ) ) ;
        
        $comment = $this->glob->request->__fetch ( 'comment', '' ) ;
        
        $id = null ;
        $document = null ;
        
        $mode = isset ( $fields['mode'] ) ? $fields['mode'] : 'update' ;
        
        
        if ( isset ( $fields['cmd'] ) && $fields['cmd'] == 'process' ) {
            
            $command = $this->glob->request->command ;
            // debug_r ( $command ) ;
            
            $ids = $this->glob->request->check ;
            // debug_r ( $ids ) ;
            
            $dms->_clear_cache () ;
  
            $this->glob->tm->delete ( $ids ) ;
            $uids = array () ;
            
            foreach (  $this->glob->tm->query ( array ( 'id' => $ids ) ) as $topic )
                $uids[$topic['uid']] = $topic['uid'] ;

            $dms->_preamble_browse () ;
            $dms->_preamble_search () ;
            
            $count = $appendix->delete_by_uids ( $uids ) ;
            
            $appendix->save_index() ;
            
            // die() ;
            
            $my_redirect = $this->glob->request->__fetch ( '_redirect', '' ) ;
            if ( $my_redirect != '' )
                header ( "Location: " . $my_redirect ) ;
            
            die() ;
        }
        
        
        
        if ( isset ( $fields['id'] ) ) {
            
            $documents = $this->glob->tm->query ( array ( 'id' => $fields['id'] ) ) ;
            // debug_r ( $documents,'find documents' ) ;
            if ( count ( $documents ) > 0 ) {
                
                // Yes, in the Topic Map
                reset($documents);
                $key = key($documents);

                if ( $key !== null ) {
                    $id = $key ;
                    $document = $documents[$id] ;
                }
            }
        }
        
        if ( isset ( $fields['name'] ) ) {
            
            $documents = $this->glob->tm->query ( array ( 'name' => 'document:'.$fields['name'] ) ) ;
            
            if ( count ( $documents ) > 0 ) {
                
                // Yes, in the Topic Map
                reset($documents);
                $key = key($documents);

                if ( $key !== null ) {
                    $id = $key ;
                    $document = $documents[$id] ;
                }
            }
        }
        
        // debug ( $document ) ;
        /*
        if ( isset ( $fields['mode'] ) ) {

            echo "<div style='font-size:0.8em;'>" ;

            $dms->_preamble ( $document['relative_path'] ) ;
            $dms->spider () ;
            $dms->objectify_paths_spidered () ;

            $docs = $dms->spidered_documents_objects ;
            $doc = reset ( $docs ) ;
            
            if ( ! $doc ) {
                
            }

            // debug_r ( $doc, 'files' ) ;
            
            foreach($_FILES as $file) { 
                $n = $file['name']; 
                $f = $file['tmp_name']; 
                $s = $file['size']; 
                if (!$n) continue; 

                $x = pathinfo ( $n ) ;
                
                $dms->alternative_absolute_path = $f ;
                $key = key ( $docs ) ;
                $ext = $doc->extension ;
                
                switch ( isset ( $fields['mode'] ) ) {

                    case 'draft' :
                        
                        if ( strtolower ( trim ( $x['extension'] ) ) != strtolower ( trim ( $ext ) ) ) {
                            echo "!!!! Error: File uploaded has a different extension than the original document. Exiting." ;
                            die () ;
                        }

                        // $document['state'] = 'not_approved' ;
                        // $this->glob->tm->update ( $document ) ;

                        // $key = end ( $keys ) ;
                        if ( $dms->draft_copy_file ( $key, true ) ) {
                            
                            // Ok
                            
                            
                        }
                        
                        break ;

                    case 'published-version' :
                        
                        if ( strtolower ( trim ( $x['extension'] ) ) != strtolower ( trim ( $ext ) ) ) {
                            echo "!!!! Error: File uploaded has a different extension than the original document. Exiting." ;
                            die () ;
                        }

                        $version_file = $this->lib_files->full_path ( $doc ) . '.pdf' ;
                        
                        debug_r ( $version_file ) ;
                        
                        
                        break ;

                    case 'version' :
                        break ;
 
                    case 'preview' :
                        break ;

                    default:
                        
                        if ( strtolower ( trim ( $x['extension'] ) ) != strtolower ( trim ( $ext ) ) ) {
                            echo "!!!! Error: File uploaded has a different extension than the original document. Exiting." ;
                            die () ;
                        }

                        $document['state'] = 'not_approved' ;
                        $document['who'] = $this->glob->user->id ;
                        $this->glob->tm->update ( $document ) ;

                        // $key = end ( $keys ) ;
                        $dms->archive_copy_file ( $key, true ) ;

                        $dms->spidered_documents_objects[$key]->create_preview = true ;
                        
                        break ;
                }

                                  // $document['state'] = 'not_approved' ;
                        // $this->glob->tm->update ( $document ) ;

                        // $key = end ( $keys ) ;
                        if ( $dms->draft_copy_file ( $key, true ) ) {
                            
                            // Ok
                            
                            
                        }
  } 
            
            echo "</div>" ;
            die () ;
            
        }
*/
        // $mode = 'update' ;
        
        if ( $document == null ) {
            // echo "Found no associated topic. Possibly new document. " ; 
            $mode = 'new' ;
        }
        
        if ( count ( $_FILES ) > 0 ) {
            
            $dms->_preamble_browse () ;
            
            $found = false ;
            // $rel = $dms->lut_relative ;
            
            foreach ( $_FILES as $file ) {
                
                $from = $file['tmp_name'] ;
                $info = pathinfo ( $file['name'] ) ;

                
                switch ( $mode ) {
                    
                    case 'new' : 
                        
                        $to = $base_folder . '/' . $fields['path'] .'/'. $file['name'] ;

                        // ok, we need to check if other files with the same name
                        // is already there

                        // find relative paths that match
                        $rel = $dms->get_relative_paths ( $fields['path'] ) ;

                        // found any?
                        if ( isset ( $rel[$fields['path']] ) ) {
                            // debug_r ( $rel[$fields['path']], "Found paths" ) ;
                            $tids = array () ;
                            foreach ( $rel[$fields['path']] as $id )
                                $tids[(int)$id] = $id ;
                            // debug_r ( $tids ) ;
                            $topics = $this->glob->tm->query ( array ( 'id' => $tids ) ) ;
                            // debug_r ( $topics, 'topics' ) ;
                            foreach ( $topics as $tid => $topic ) {
                                $f = $topic['filename'] . '.' . $topic['extension'] ;
                                if ( trim ( $f == $file['name'] ) ) {
                                    $found = $tid ;
                                }
                                // debug_r ( $f, $found ) ;
                            }
                            if ( $found ) {
                                // echo "Found same file; only updates can happen. " ;
                            } else {
                                // echo "No same files; can only be added as a new file. " ;
                            }
                        } else {
                            if ( $mode == 'update' ) {
                                // echo "Hmm. Trying to update, however I can't find any topics. " ;
                            }
                        }

                        $dms->safe_mode = false ;
                        $dms->_clear_cache () ;

                        $fstat = stat ( $from ) ;

                        $uid = md5 ( $from ) ;

                        $arr = array (
                            'label' => $fields['label'],
                            'name' => 'draft:' . $uid,
                            'type1' => $this->_type->doc_draft,
                            'original_path' => $from,
                            'relative_path' => $fields['path'] .'/'. $info['filename'],
                            'source' => 'manual',
                            'uid' => $uid,
                            // 'final_path' => $doc->file_dest,
                            'home_directory' => $dms->get_dir_structure ( $uid ),
                            'filename' => $info['filename'],
                            'extension' => isset ( $info['extension'] ) ? $info['extension'] : '',
                            'next_review_date' => $next_review_date,
                            // 'timestamp' => $doc->timestamp,
                            // 'timestamp_db_property' => 0, 
                            'm_c_who' => $this->glob->user->id,
                            'controlled' => 'true'
                        ) ;

                        $tmp = $this->_fire_event ( 'on_document_new_pre', $arr ) ;
                        // debug_r ( $tmp, 'event pre return' ) ;

                        $db_id = $this->glob->tm->create ( $arr ) ;
                        $arr['id'] = $db_id ;

                        // Fetch that topic back!
                        $find_topics = $this->glob->tm->query ( array ( 'id' => $db_id ) ) ;
                        $fi = reset ( $find_topics ) ;
                        $fi['_event'] = 'on_document_new' ;
                        // debug_r ( $fi, 'topic' ) ;

                        $tmp = $this->_fire_event ( 'on_document_new', $fi ) ;
                        // debug_r ( $tmp, 'event return' ) ;

                        $test = $dms->objectify_topics ( $find_topics ) ;

                        // debug_r ( $test ) ;

                        if ( is_array ( $test ) && isset ( $test[0] ) && is_object ( $test[0] ) ) {

                            $doc = $test[0] ;
                            $doc->attach_topic ( $fi ) ;
                            $doc->load_history () ;

                        }

                        $dms->draft_copy_file ( $doc ) ;
                        $dms->_create_html ( $doc ) ;

                        $newdraft = $dms->draft_filename ( $doc, 1, 1 ) ;
                        $doc->history->add_to_draft ( 1, 1, 'created', $this->glob->user->id ) ;
                        $doc->history->add_to_draft ( 1, 1, 'comment', $comment ) ;


                        $tmp = $this->_fire_event ( 'on_document_new_post', $arr ) ;
                        // debug_r ( $tmp, 'event post return' ) ;

                        // $dms->_action_touch_dest ( $find_topics ) ;

                        $doc->save_history () ;
                        
                        $my_redirect = $this->glob->request->__fetch ( '_redirect', '' ) ;
                        
                        if ( $my_redirect != '' )
                            header ( "Location: " . $my_redirect ) ;
                        
                        break ;
                        
                    case 'update' : 
                        
                        $dms->safe_mode = false ;
                        $dms->_clear_cache () ;

                        $test = $dms->objectify_topics ( $documents ) ;

                        if ( isset ( $test[0] ) ) {
                            $doc = $test[0] ;
                            $doc->load_history () ;

                            $versions = $dms->get_versions ( $doc ) ;
                            $version = $dms->find_last_version ( $versions ) ;

                            if ( $version == null )
                                $version = 0 ;

                            $drafts = $dms->get_drafts ( $doc, $version + 1 ) ;
                            $draft = $dms->find_last_draft ( $drafts ) ;

                            if ( $draft == null )
                                $draft = 0 ;

                            // inject the incoming file path
                            $doc->file_original = $from ;

                            $dms->draft_copy_file ( $doc, true ) ;
                            $doc->history->add_to_draft ( $version + 1, $draft + 1, 'created', $this->glob->user->id ) ;                        
                            $doc->history->add_to_draft ( $version + 1, $draft + 1, 'comment', $fields['comment'] ) ;

                            $doc->save_history () ;
                            $fi = $doc->get_topic () ;
                            $fi['_event'] = 'on_document_new_draft' ;

                            $tmp = $this->_fire_event ( 'on_document_new_draft', $fi ) ;

                        } else {
                            echo "Hmm, no object of the document could be created." ;
                        }
                        
                        break ;
                        
                    case 'published-format' : 
                        
                        $dms->safe_mode = false ;
                        $dms->_clear_cache () ;

                        $test = $dms->objectify_topics ( $documents ) ;

                        if ( isset ( $test[0] ) ) {
                            $doc = $test[0] ;
                            $doc->load_history () ;

                            $file = $dms->full_path ( $doc ) . '.pdf' ;
                            
                            stream_copy ( $from, $file ) ;
                                    
                            $fi = $doc->get_topic () ;
                            $fi['_event'] = 'on_document_published_pdf' ;

                            $tmp = $this->_fire_event ( 'on_document_published_pdf', $fi ) ;
                            
                            echo "Upload successful! <script>$('#publish-submit').show('slow');$('#pubuploadsection').hide('fast');</script>" ;

                        } else {
                            echo "Hmm, no object of the document could be created." ;
                        }
                        
                        break ;
                        
                }
            }
            
        }
        
        die () ;
    }
    
    
    
    
    
    
}

