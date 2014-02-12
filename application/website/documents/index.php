<?php

class xs_action_instance extends \xs\Action\Webpage {

    function ___action () {
        
        // Parse the request URI a bit differently, looking for facets (f[n])
        $this->glob->breakdown->_parse ( '{concept}/{f1}/{f2}/{f3}/{f4}/{f5}/{f6}/{f7}/{f8}/{f9}' ) ;
        
        $f = $this->glob->breakdown ;
        
        $fields = $this->glob->request->__get_fields () ;
        
        $control_strength = 0 ;

        $current_facets = array () ;
        $last = $path = '' ;

        // get the document manager module
        $dms = $this->_get_module ( 'dms' ) ;
        

        if ( $this->glob->request->_refresh == 'true' ) {
            $dms->_clear_cache () ;
            $this->alert ( 'notice', 'Refresh!', 'Folder caches have been emptied.' ) ;
        }
        
        // start with a fresh set
        $documents = $versions = $drafts = array () ;
        
        // what mode are we in?
        $mode = 'browse' ;
        $submode = null ;
        $version = null ;
        
        // if there's a document, this is it
        $document = null ;
        
        // test for uid recognition
        $uid = trim ( $f->__fetch ( 'f1', '' ) ) ;
        
        if ( is_numeric ( $uid ) ) {
            
            $mode = 'document' ;
            
            $documents = $this->glob->tm->query ( array ( 'id' => $uid ) ) ;
            // debug_r ( $documents ) ;
            
            if ( count ( $documents ) > 0 ) {
                
                // make list of one into one
                $document = reset ( $documents ) ;
                
                // if ( $document['type1'] !== $this->_type->doc || $document['type1'] !== $this->_type->doc_draft )
                //     $mode = 'not_found' ;
                
                $uid = substr ( $document['name'], strpos ( $document['name'], ':' ) + 1 ) ;
                // debug ( $uid ) ;
            } else {
                // $mode = 'not_found' ;
            }
            
        }
        
        if ( strlen ( $uid ) == 32 ) {
            
            // Probably UID
            $documents = $this->glob->tm->query ( array ( 'name' => 'document:'.$uid ) ) ;
            
            if ( count ( $documents ) > 0 ) {
                
                // make list of one into one
                $document = reset ( $documents ) ;
                
                // debug ( $document['id'] ) ;
                
                if ( count ( $documents ) > 1 ) {
                    echo "Duplication! " ;
                    $this->alert ( 'notice', 'Duplication', 'This document have duplicate topics. Inform the administrator about this problem (error code: 4)' ) ;
                }
                
                
                $mode = 'document' ;
                
                
                
                // if ( $document['type1'] !== $this->_type->doc || $document['type1'] !== $this->_type->doc_draft )
                //     $mode = 'not_found' ;
                
                $version = trim ( $f->__fetch ( 'f2', '' ) ) ;
                
                if ( $version != '' ) {
                    $submode = true ;
                }
                
            } else {
                // $mode = 'not_found' ;
            }
        }
        
        if ( $mode == 'document' ) {
            if ( ! isset ( $document['id'] ) ) {
                $mode = 'not_found' ;
            } elseif ( $document['type1'] != $this->_type->doc &&
                       $document['type1'] != $this->_type->doc_draft ) {
                $mode = 'not_found' ;
            }
            // debug_r ( $document ) ;
        }
        // if ( $document['type1'] == null )
        //     $mode = 'not_found' ;
        
        // Pull out all facets (well, first 9) from the request URI
        for ( $n=1; $n < 10; $n++) {
            $x = trim ( $f->__fetch ( "f{$n}", '' ) ) ;
            if ( $x != '' ) {
                $path .= $x . '/' ;
                $current_facets[urlsafe($x)] = $x ;
                $last = urlsafe($x) ;
            }
        }
        
        // just fix the path by removing trailing slash
        $path = substr ( $path, 0, -1 ) ;
        
        // what's the page's identity?
        $page_id = $f->__fetch ( 'concept', 'documents' ) ;
        
        // start the breadcrumb
        $breadcrumb = array( $page_id => 'Documents' ) ;
        
        if ( $uid == 'no_preview' ) $mode = 'no_preview' ;
        
        switch ( $mode ) {
            
            case 'no_preview':
                echo "<div style='color:#555;margin:35px 70px;'><h2 style='border-bottom:solid 2px #cdc;'>No preview</h2><p>Sorry, but there is no preview available for this file at the moment.</p></div>" ;
                die();
                
            case 'browse':
                
                $this->set_title ( 'Browse' ) ;
                
                // log this reading
                $this->log ( 'BROWSE', $path ) ;

                // create file directory and inject files, and pop them on the stack
                $dms->create_tree ( $path, $last ) ;

                // debug_r ( $this->glob->page ) ;
                
                $this->glob->page->relative_path = $path ;
                break ;
                
            case 'not_found' :
                
                $this->set_template ( 'not_found' ) ;
                $this->set_title ( 'Document not found' ) ;
                
                break ;
                
            case 'document':
                
                // Yes, it's a document / URI; use new template
                $this->set_template ( 'show' ) ;
                $this->set_title ( 'Document' ) ;
                
                    
                $p = pathinfo ( $document['original_path'] ) ;

                if ( ! isset ( $document['extension'] ) )
                    $document['extension'] = $p['extension'] ;

                if ( ! isset ( $document['basename'] ) )
                    $document['basename'] = $p['basename'] ;

                if ( ! isset ( $document['filename'] ) )
                    $document['filename'] = $p['filename'] ;

                $ext = $document['extension'] ;
                $document['uid'] = $uid ;

                if ( ! isset ( $document['pub_format'] ) ) {
                    $document['pub_format'] = 'false' ;
                    if ( isset ( $this->glob->config['dms']['publish_in_pdf_format'] ) && $this->glob->config['dms']['publish_in_pdf_format'] == '1' )
                        $document['pub_format'] = 'true' ;
                    // debug ( $document['pub_format'] ) ;
                }
                if ( isset ( $this->glob->config['dms']['publish_in_pdf_except'] ) ) {
                    $th = explode ( ',', $this->glob->config['dms']['publish_in_pdf_except'] ) ;
                    foreach ( $th as $ex ) {
                        // debug_r ( $ex, $ext ) ;
                        if ( $ext == $ex ) {
                            $document['pub_format'] = 'override' ;
                            break ;
                        }
                    }
                }

                $icon = '24x24/mimetypes/binary.png' ;

                $document['final_path'] = $dms->get_dir_structure ( $uid, null ) ;
                $document['home_path'] = $dms->get_dir_structure ( $uid, '' ) ;
                $document['final_dir'] = $document['final_path'].'/'.$uid.'.'.$ext ;
                $document['final_dir_txt'] = $document['final_path'].'/'.$uid.'.txt' ;
                $document['final_dir_html'] = $document['final_path'].'/'.$uid.'.html' ;

                $document['final_www_html'] = $this->glob->config['dms']['destination_uri'] .
                    $document['home_path'] .'/'. $uid.'.html' ;

                $file_exist_original = file_exists ( $document['original_path'] ) ;
                $file_exist_dest = @file_exists ( @$document['final_dir'] ) ;
                $file_exist_html = @file_exists ( @$document['final_dir_html'] ) ;
                $file_exist_txt = @file_exists ( @$document['final_dir_txt'] ) ;

                if ( ! $file_exist_html ) {
                    $document['final_www_html'] = $this->glob->dir->home . '/documents/no_preview' ;
                    $document['no-preview'] = 'true' ;
                }
                // debug_r($document);
                // debug(array($file_exist_original,$file_exist_dest,$file_exist_html,$file_exist_txt));
                /*
                if ( $file_exist_original === false ) {
                    // now what?
                    echo '<p>Original ['.$document['original_path'].'] not found.</p>' ;
                    if ( isset ( $document['controlled'] ) && $document['controlled'] == 'true' )
                        echo '<p>Document is controlled, so it\'s ok.</p>' ;
                    else
                        echo '<p>Document is NOT controlled; panic!</p>' ;
                }

                if ( $file_exist_dest === false ) {
                    // copy original to dest
                    echo '<p>Copy original to destination ['.$document['final_dir'].'].</p>' ;
                    if ( isset ( $document['controlled'] ) && $document['controlled'] == 'true' )
                        echo '<p>Document is controlled, so if there\'s no current document, panic!</p>' ;
                }

                if ( $file_exist_html === false ) {
                    // create HTML
                    echo '<p>No HTML; convert original to HTML ['.$document['final_dir_html'].']</p>' ;
                }

                if ( $file_exist_txt === false ) {
                    // create TXT
                    echo '<p>No TXT; convert HTML to TXT ['.$document['final_dir_txt'].'], invoke spidering and indexing</p>' ;
                }


                */





                if ( isset ( $this->glob->config['dms'][$ext.'.icon'] ) )
                    $icon = $this->glob->config['dms'][$ext.'.icon'] ;

                $document['icon'] = $icon ;

                $current_facets = array () ;
                $path = '' ;

                if ( isset ( $document['relative_path'] ) )
                    foreach ( explode ( '/', $document['relative_path'] ) as $i ) {
                        $path .= $i . '/' ;
                        $current_facets[urlsafe(substr ( $path, 0, -1 ))] = $i ;
                    }

                // if ( ! isset ( $document['next_review_date'] ) )
                //     $document['next_review_date'] = date ( "Y-m-d", mktime(0, 0, 0, date("m"),   date("d"),   date("Y")+1) ) ;


                if ( ! isset ( $document['publish_date'] ) && $file_exist_original )
                    $document['publish_date'] = date ( "Y-m-d", filemtime ( $document['original_path'] ) ) ;

                if ( ! isset ( $document['state'] ) )
                    $document['state'] = '' ;


                if ( isset ( $fields['approved_by'] ) ) {

                    // yes, someone is approving this document
                    $document['state'] = 'approved' ;
                    $document['approved_by'] = $fields['approved_by'] ;
                    $document['who'] = $this->glob->user->id ;

                    $this->glob->tm->update ( $document ) ;
                }

                if ( isset ( $document['approved_by'] ) ) {
                    $ab = $this->glob->tm->query ( array ( 'id' => $document['approved_by'] ) ) ;
                    $t = reset ( $ab ) ;
                    $document['approved_by_label'] = $t['label'] ;
                }

                $this->glob->stack->add ( 'xs_document', $document ) ;


                if ( isset ( $document['words_important'] ) ) {

                    // Find all important words in all documents
                    $tmp = unserialize ( $document['words_important'] ) ;

                    if ( !is_array ( $tmp ) )
                        $tmp = array () ;

                    // Sort them, retain keys
                    arsort ( $tmp ) ;

                    $tmp = array_slice ( $tmp, 0, 20 ) ;

                    // Get the resource for the tagcloud service
                    $cloud = $this->_get_resource ( '_api/services/keywords/cloud' ) ;

                    $res = $cloud->POST ( array ( 
                        'base' => $this->glob->dir->home . '/keyword',
                        'list' => $tmp,
                        'max' => '60'
                    ) ) ;

                    $this->glob->stack->add ( 'xs_content', array (
                        'keywords' => $res
                    ) ) ;                

                }


                $id = $document['id'] ;


                $tm = $this->_get_module ( 'topic_maps' ) ;

                $this->glob->stack->add ( 'xs_assoc_tags', $tm->get_assoc ( array (
                    'lookup' => $id,
                    'type' => $this->_type->has_tag,
                    'filter' => $this->_type->_tag,
                ) ) ) ;

                $this->glob->stack->add ( 'xs_assoc_controlled_tags', $tm->get_assoc ( array (
                    'lookup' => $id,
                    'type' => $this->_type->has_controlled_tag,
                    'filter' => $this->_type->_controlled_tag,
                ) ) ) ;

                $this->glob->stack->add ( 'xs_assoc_owner', $tm->get_assoc ( array (
                    'lookup' => $id,
                    'type' => $this->_type->has_owner,
                    'filter' => $this->_type->_user,
                ) ) ) ;

                $this->glob->stack->add ( 'xs_assoc_author', $tm->get_assoc ( array (
                    'lookup' => $id,
                    'type' => $this->_type->has_author,
                    'filter' => $this->_type->_user,
                ) ) ) ;


                // log this reading
                $this->log ( 'READ', $path ) ;


                // comments?
                $comments = $this->_get_module ( 'comments' ) ;
                $this->glob->stack->add ( 'xs_comments', $comments->get_for_topic ( $id ) ) ;
                
                break ;
        }
        
        // inject all path facets into the breadcrumb
        foreach ( $current_facets as $i => $label )
            $breadcrumb[$page_id.'/'.$i] = $label ;

        // Pop it on the stack
        $this->glob->stack->add ( 'xs_facets', $breadcrumb ) ;

    }
    
        function ___register_functionality () {

            $this->_register_functionality ( 'Controller', 'document:*' ) ;
            $this->_register_functionality ( 'Controlled document?', 'document:controlled?' ) ;
            $this->_register_functionality ( 'Control tab', 'document:control_tab' ) ;
            $this->_register_functionality ( 'Versions tab', 'document:versions_tab' ) ;
                
        }
    
}
