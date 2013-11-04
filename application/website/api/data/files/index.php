<?php

class xs_action_instance extends \xs\Action\Generic {

    function ___action () {

        // get the document manager module
        $dms = $this->_get_module ( 'dms' ) ;

        $type = isset ( $_POST['mimetype'] ) ? $_POST['mimetype'] : null ; 
        $xhr = @$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'; 

        $fields = $this->glob->request->__get_fields () ;
        
        // debug_r ( $fields ) ;
        
        $id = null ;
        $document = null ;
        
        if ( isset ( $fields['id'] ) ) {
            
            $documents = $this->glob->tm->query ( array ( 'id' => $fields['id'] ) ) ;
            
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
        
        if ( $document === null ) {
            echo "Found no associated topic." ; 
            die () ;
        }
        
        // debug ( $document ) ;
        
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

            } 
            
            echo "</div>" ;
            
        }
        
        /* Array ( 
            [dirname] => /home/alexander/develop/intranet/static/documents 
            [basename] => ba7da2a273f83e172f85ed24299441ba.pdf 
            [extension] => pdf 
            [filename] => ba7da2a273f83e172f85ed24299441ba 
        ) */

        
            // print_r ( $x ) ;
            // continue ;
            // echo '<pre>' ; print_r ( $keys ) ; print_r ( $doc ) ; echo '</pre><hr/>' ;
            
        
        // $dms->process_create_previews_and_text_files () ;
        // $dms->process_clean_and_harvest () ;
        die () ;
    }
}

