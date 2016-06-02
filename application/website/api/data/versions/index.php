<?php

class xs_action_instance extends \xs\Action\Generic {

    private $base_folder = null ;
    private $base_uri = null ;
    private $dms = null ;
    
    private $registry = array () ;
    
    
    function get_files ( $uid, $pattern ) {
        
        $path = $this->dms->get_dir_structure ( $uid, null ) ;
        
        $matches = $this->dms->process_dir_versions ( $path, $pattern ) ;
        
        if ( is_array ( $matches ) ) {
            rsort ( $matches ) ;
        }
        
        return $matches ;
    }

    function change_state_draft ( $o, $draft_version, $mode ) {
        
        $state = '' ;
        switch ( $mode ) {
            case 'promote': $state = 'promoted' ; break ;
            case 'approve': $state = 'approved' ; break ;
            case 'publish': $state = 'published' ; break ;
            case 'cancel' : $state = '' ; break ;
        }
        
        // first, find all existing versions
        $versions = $this->dms->get_versions ( $o ) ;
        $version  = $this->dms->find_last_version ( $versions ) ;

        $v = 'ver_'. (string) ( (int)$version + 1 ) ;
        // debug($v); debug($state); debug($draft_version);debug($mode);debug($state);
        if ( isset ( $this->registry[$v] ) )
            $this->registry[$v]['drafts'][$draft_version]['state'] = $state ;
        
    }
    
    function ___action () {

        // setting up
        $this->base_folder = $this->glob->config['dms']['destination_folder'] ;
        $this->base_uri    = $this->glob->config['dms']['destination_uri'] ;
        
        // gettting the DMS module
        $this->dms = $this->_get_module ( 'dms' ) ;
        
        // debug_r ( $this->glob->request->__get_array () , 'request' ) ;
        
        // get a few inputs
        $id = $this->glob->request->__fetch ( 'id', '' ) ;
        $uid = $this->glob->request->__fetch ( 'uid', '' ) ;
        $just_drafts = $this->glob->request->__fetch ( 'just_drafts', false ) ;
        
        $mode = $this->glob->request->__fetch ( 'mode', false ) ;
        $draft_version = $this->glob->request->__fetch ( 'draft_version', false ) ;
        $draft = $this->glob->request->__fetch ( 'draft', false ) ;
        
        $pick_default = isset ( $this->glob->config['dms']['next_review_months'] ) ? $this->glob->config['dms']['next_review_months'] : 24 ;
        $next_review_date_pick = $this->glob->request->__fetch ( 'next_review_date', $pick_default ) ;
        $next_review_date = date ( "Y-m-d", strtotime ( "+{$next_review_date_pick} months" ) ) ;
        
        // debug_r ( $this->glob->request ) ;
        
        $result = array () ;
        
        // quick, look up the topic for the id
        if ( $id != '' )
            $result = $this->glob->tm->query ( array ( 'id' => $id ) ) ;
        
        if ( $uid != '' )
            $result = $this->glob->tm->query ( array ( 'name' => array ( 'document:'.$uid, 'draft:'.$uid ) ) ) ;
        
        if ( count ( $result ) > 1 )
            echo "<p>Hmm, there are ".count($result)." versions of that document. Something odd.</p>" ;
        // echo 'a ' ;
        $topic = reset ( $result ) ;
        
        // echo 'b ' ;
        if ( ! $topic || count ( $result ) < 1 ) {
            echo "<hr/>Hmm. No topic found for ID [$id] or [$uid], meaning no versions can be found.<hr/>" ;
            die () ;
        }

        
        
        
        $objects = $this->dms->objectify_topics ( $result ) ;

        $doc = reset ( $objects ) ;
        $topic = $doc->get_topic () ;

        // just make sure we've got the history down pat
        $doc->load_history () ;
        
        $versions  = $this->dms->get_versions ( $doc ) ;
        $version = $this->dms->find_last_version ( $versions ) ;
        
        if ( $version == null ) 
            $version = 0 ;
        
        $this_drafts  = $this->dms->get_drafts ( $doc, (int) $version + 1 ) ;

        $last_draft = 0 ;
        
        if ( isset ( $doc->history->struct[$version]['drafts'] ) ) { 
            
            foreach ( $doc->history->struct[$version]['drafts'] as $draft_id => $d ) {
                
                $last_draft = $draft_id ;
            }
            
        }
        
        // echo "<div>version=[$version] draft=[$draft] draft_version=[$draft_version] last_draft=[$last_draft] mode=[$mode]</div>" ;
        
        // $deployment_state       = isset ( $topic['deployment_state'] )       ? $topic['deployment_state']       : '' ;
        // $deployment_state_value = isset ( $topic['deployment_state_value'] ) ? $topic['deployment_state_value'] : '' ;
        

        switch ( $mode ) {
            
            case 'promote' :
                $topic['deployment_state'] = 'promoted' ;
                $topic['deployment_state_value'] = $draft ;
                
                $doc->history->add_to_draft ( $version + 1, $draft, 'promoted', $this->glob->user->id ) ;
                $doc->history->save() ;
                
                $this->glob->tm->update ( $topic ) ;
                
                $topic['_event'] = 'on_document_promoted' ;
                $tmp = $this->_fire_event ( 'on_document_promoted', $topic ) ;

                break ;
                
            case 'approve' :
                $topic['deployment_state'] = 'approved' ;
                $topic['deployment_state_value'] = $draft ;
                
                $doc->history->add_to_draft ( $version + 1, $draft, 'approved', $this->glob->user->id ) ;
                $doc->history->save() ;
                
                $this->glob->tm->update ( $topic ) ;
                
                $topic['_event'] = 'on_document_approved' ;
                $tmp = $this->_fire_event ( 'on_document_approved', $topic ) ;
                
                break ;
                
            case 'publish' :
                
                $tm = $this->_get_module ( 'topic_maps' ) ;
                
                $owners = $tm->get_assoc ( array (
                    'lookup' => $topic['id'],
                    'type' => $this->_type->has_owner,
                    'filter' => $this->_type->_user,
                ) ) ;
                $own = array () ;
                foreach ( $owners['members'] as $member => $data )
                    $own[$member] = $member ;
                // debug_r ( $own, 'owners' ) ;
                
                $authors = $tm->get_assoc ( array (
                    'lookup' => $topic['id'],
                    'type' => $this->_type->has_author,
                    'filter' => $this->_type->_user,
                ) ) ;
                $auth = array () ;
                foreach ( $authors['members'] as $member => $data )
                    $auth[$member] = $member ;
                // debug_r ( $auth, 'authors' ) ;
                
                $this->dms->draft_to_version_copy_file ( $doc, $draft ) ;
                
                $doc->history->add_to_version ( $version + 1, 'published', $this->glob->user->id ) ;
                $doc->history->add_to_version ( $version + 1, 'has_owner', $own ) ;
                $doc->history->add_to_version ( $version + 1, 'has_author', $auth ) ;
                
                $oldversion = $version ;
                $newversion = $version + 1 ;
                if ( $version == 0 )
                    $oldversion = 1 ;
                
                $doc->history->copy_to_version_from_draft ( $newversion, $oldversion, $draft, 'published' ) ;
                $doc->history->copy_to_version_from_draft ( $newversion, $oldversion, $draft, 'approved' ) ;
                $doc->history->copy_to_version_from_draft ( $newversion, $oldversion, $draft, 'promoted' ) ;
                $doc->history->copy_to_version_from_draft ( $newversion, $oldversion, $draft, 'created' ) ;
                $doc->history->copy_to_version_from_draft ( $newversion, $oldversion, $draft, 'comment' ) ;
                
                $doc->history->save() ;
                
                // debug_r ( $doc->history ) ;
                
                $uid = '' ;
                
                if ( substr ( $topic['name'], 0, 6 ) == 'draft:' ) {
                    $uid = substr ( $topic['name'], 6 ) ;
                    $topic['name'] = 'document:'.$uid ;
                    $topic['type1'] = $this->_type->doc ;
                } else {
                    $uid = substr ( $topic['name'], 9 ) ;
                }
                $topic['home_directory'] = $this->dms->get_dir_structure ( $uid ) ;

                $topic['next_review_date'] = $next_review_date ;

                $topic['_event'] = 'on_document_published' ;
                
                $this->dms->_clear_cache () ;
                
                $tmp = $this->_fire_event ( 'on_document_published', $topic ) ;
                
                // break ;
                // note : no break ; a publish also resets the state
                
            case 'reset' :
                $topic['deployment_state'] = '' ;
                $topic['deployment_state_value'] = '' ;
                
                $this->glob->tm->update ( $topic ) ;
                break ;
        }
        
        
        // attach the topic back into the document
        $doc->attach_topic ( $topic ) ;
        
        // draw it all up
        $this->draw_document ( $doc, $just_drafts ) ;
        
        die() ;
        
        // print_r ( $doc ) ;
        // die();
            
        
        
        
        // die() ;
        // $o = new \xs\DocumentManager\Document ( $doc['original_path'], null ) ;
        // debug_r($o);
        // $o->final_path = $this->dms->get_dir_structure ( $o->uid, null, true ) ;
        // $o->final_file = $o->final_path . '/' . $uid . '.' . $extension ;

        // $this->load_version_registry ( $o->final_path, $uid ) ;
        
        // debug_r ( $this->registry ) ;
        
        if ( trim ( $mode ) !== '' )
            $this->change_state_draft ( $o, $draft_version, $mode ) ;
        
        /*   
        if ( $mode == 'reset' ) {
            
            // first, find all existing versions
            $versions = $this->dms->get_versions ( $o ) ;
            // $versions = array_merge ( $versions, array ( $uid.'.'.$extension ) ) ;
            $versions_count = count ( $versions ) ;
            $version  = $this->dms->find_last_version ( $versions ) ;
            
            $v = 'ver_'. (string) ( (int)$version + 1 ) ;
            if ( isset ( $registry[$v] ) ) {
                
                $registry[$v]['drafts'][$draft_version]['state'] = '' ;
            }
            
        } elseif ( $mode == 'approve' ) {
            
            // first, find all existing versions
            $versions = $this->dms->get_versions ( $o ) ;
            // $versions = array_merge ( $versions, array ( $uid.'.'.$extension ) ) ;
            $versions_count = count ( $versions ) ;
            $version  = $this->dms->find_last_version ( $versions ) ;
            
            $v = 'ver_'. (string) ( (int)$version + 1 ) ;
            if ( isset ( $registry[$v] ) ) {
                
                $registry[$v]['drafts'][$draft_version]['state'] = 'approved' ;
            }
            
        } elseif ( $mode == 'publish' ) {
            
            // first, find all existing versions
            $versions = $this->dms->get_versions ( $o ) ;
            // $versions = array_merge ( $versions, array ( $uid.'.'.$extension ) ) ;
            $versions_count = count ( $versions ) ;
            $version  = $this->dms->find_last_version ( $versions ) ;
            
            $v = 'ver_'. (string) ( (int)$version + 1 ) ;
            if ( isset ( $registry[$v] ) ) {
                
                $registry[$v]['drafts'][$draft_version]['state'] = 'published' ;
            }
            
        }
        */
        
        $versions = $doc->versions ;
        // debug_r ( $tmp ) ;
        // debug_r ( $versions ) ; die() ;
        
        // $versions = array_merge ( $versions, array ( $uid.'.'.$extension ) ) ;
        $versions_count = count ( $versions ) ;
        $version  = $this->dms->find_last_version ( $versions ) ;
        // debug($versions);
         $html = '' ;
        
        // $html .= '<h4>Approved versions</h4>' ;
        
        if ( ! $just_drafts ) {
            $html .= '<div style=""><div id="filesubmitsuccess" style="display:none;margin:5px 10px;padding:10px;font-size:1.1em;font-weight:bold;"></div><table class="infotable">' ;

            $ss = "background-color:#eee;color:#555;font-weight:bold;padding:7px 5px;font-size:1.05em;" ;

            $html .= '<tr style="'.$ss.'">' ;
            $html .= '   <td style="'.$ss.'">Version</td>' ;
            $html .= '   <td style="'.$ss.'">Date and time</td>' ;
            $html .= '   <td style="'.$ss.'">Size</td>' ;
            $html .= '   <td style="'.$ss.';min-width:100px;">Uploader</td>' ;
            $html .= '   <td style="'.$ss.';min-width:100px;">Owner</td>' ;
            $html .= '   <td style="'.$ss.';min-width:100px;">Approver</td>' ;
            $html .= '   <td style="'.$ss.'">Controls</td>' ;
            $html .= '</tr>' ;
        }
        $counter = 0 ;
        
        
        $drft = $extra = '' ;
        // $cnt = count ( $matches ) ;

        // if ( $versions_count > 0 ) $drft = ' ('.$versions_count.')' ;
        
        
        $drafts = $this->dms->get_drafts ( $doc, (int) $version + 1 ) ;
        $drafts_count = count ( $drafts ) ;
        $draft = $this->dms->find_last_draft ( $drafts ) ;

        $v = 'ver_'. (string) ( (int)$version + 1 ) ;
        if ( ! isset ( $this->registry[$v] ) ) {
            $this->registry[$v]['dummy'] = true ;
            $this->registry[$v]['data'] = array () ;
            $this->registry[$v]['drafts'] = array () ;
        }
        
        if ( $drafts_count > 0 ) 
            $drft = " ({$drafts_count})" ;
        
        // $controlled = ( isset ( $doc->controlled ) && $doc['controlled'] == 'true' ) ? true : false ;

        if ( ! $just_drafts ) $html .= '<tr id="vdraft">' ;
        if ( ! $just_drafts ) $html .= '   <td colspan="6"> <i>Drafting the next version</i> </td>' ;
        if ( ! $just_drafts ) $html .= '<td><span id="vdraftctrl" onclick="$(\'#drafts\').slideDown();$(\'#vdriftctrl\').show();$(this).hide();return false;" class="nolink">drafts'.$drft.' &gt;&gt;</span> ' ;
        if ( ! $just_drafts ) $html .= '   <span id="vdriftctrl" onclick="$(\'#drafts\').slideUp();$(\'#vdraftctrl\').show();$(this).hide();return false;" style="display:none;" class="nolink">drafts'.$drft.' &lt;&lt;</span> ' ;
        if ( ! $just_drafts ) $html .= '</td></tr>' ;

        if ( ! $just_drafts ) $html .= '<tr><td colspan="7" style="margin:0;padding:0;"><div id="drafts" style="display:none;padding:5px 8px;margin:4px;">' ;


        // go through all current drafts found

        $counter = 0 ;

        $html .= '<table style="margin:0;padding:0;">' ;

        foreach ( $drafts as $draft ) {

            $extra = $dr = '' ;
            if ( $counter == 0 ) {
                $extra = "background-color:#fe9;font-weight:bold;" ;
                $dr = ' (latest)' ;
            }

            $tfile = $path . '/' . $draft ;
            if ( file_exists ( $tfile ) ) {
                $tfstat = stat ( $tfile ) ;
                $tversion = (int) substr($draft,strrpos($draft,'-')+1,-4) ;
                $tdate = date ( "Y-m-d H:i:s", $tfstat['mtime'] ) ;
                
                // debug($registry);
                $this->registry[$v]['drafts'][(string)$tversion]['date'] = $tdate ;
                
                $f = $this->dms->draft_full_path ( $o, $version + 1, $tversion, $this->glob->dir->home.'/static/documents'.$home ) ;
                // debug($f);
                $html .= '<tr>' ;
                $html .= '   <td style="'.$extra.'">'. $tversion . $dr . '</td>' ;
                $html .= '   <td style="'.$extra.'"><a href="'.$f.'" style="color:blue;">'.$tdate.'</a></td>' ;
                $html .= '   <td style="'.$extra.'">'.human_filesize($tfstat['size']).'</td> ' ;
                $html .= '   <td style="'.$extra.'">uploaded by <span class="nolink">Some Guy</span></td> ' ;
                             
                // debug($registry[$v]['drafts'][(string)$tversion]['state']);
                
                if ( isset ( $this->registry[$v]['drafts'][(string)$tversion]['state'] ) ) {
                    switch ( $this->registry[$v]['drafts'][(string)$tversion]['state'] ) {
                        case 'promoted' :
                            $html .= '   <td style="'.$extra.'"><span class="nolink" onclick="approve_draft(\''.$tversion.'\',\''.$draft.'\');"><b>Approve</b></span> | <span class="nolink" onclick="reset_draft(\''.$tversion.'\',\''.$draft.'\');">Cancel</span></td>' ;
                            break ;
                        case 'approved' :
                            $html .= '   <td style="'.$extra.'"><span class="nolink" onclick="publish_draft(\''.$tversion.'\',\''.$draft.'\');"><b>Publish</b></span> | <span class="nolink" onclick="reset_draft(\''.$tversion.'\',\''.$draft.'\');">Cancel</span></td>' ;
                            break ;
                        case 'published' :
                            $html .= '   <td style="'.$extra.'"> [published] </td>' ;
                            break ;
                        default:
                            $html .= '   <td style="'.$extra.'"><span class="nolink" onclick="promote_draft(\''.$tversion.'\',\''.$draft.'\');">Submit for approval</span></td>' ;
                            break ;
                    }
                } else {
                    $html .= '   <td style="'.$extra.'"><span class="nolink" onclick="promote_draft(\''.$tversion.'\',\''.$draft.'\');">Submit for approval</span></td>' ;
                }
                $html .= '</tr>' ;
                // $html .= "<div style='padding-left:20px;'>[$draft]</div>" ;
            }
            $counter++ ;
        }
        $html .= "</table><div style='padding:4px;'><button onclick='$(\"#draft-upload-form\").dialog(\"open\");'>Upload draft</button></div>" ;


        if ( ! $just_drafts ) $html .= '</div></td></tr>' ;


        $counter = 0 ;
        
        // go through all versions found
        
        
        // $versions = $this->glob->tm->query ( array ( 'parent' => $doc['id'] ) ) ;
        
        
        foreach ( $versions as $idx => $filename ) {
            
            $version = trim ( substr ( $filename, strlen ( $uid ) + 1, 5 ) ) ;
            // debug ( $version ) ;
            // if ( $version == '' ) 
            //     $version = $matches_count ;
            // else
            //     $version = (int) $version + 1 ;
            
            $v = 'ver_'.(int)$version ;

            
            if ( ! isset ( $this->registry[$v] ) ) {
                $this->registry[$v]['data'] = array () ;
                $this->registry[$v]['drafts'] = array () ;
            }
            
            $doc_version = null ;
            $ctrl = '' ;
            
            if ( is_numeric ( $version ) ) {
                $n = $doc['name'].'.'.$version ; 
                $a = $this->glob->tm->query ( array ( 'name' => $n ) ) ;
                $doc_version = end ( $a ) ;
                $this->registry[$v]['data']['name'] = $n ;
            } else {
                // echo "NOT_numeric " ;
                $doc_version = $doc ;
                $version = $versions_count ;
            }
            
            // $registry[$v]['date'] = $tdate ;
                
            // print_r ( $doc_version ) ;
            // if ( ! $doc_version ) {
                // $ctrl = "[{$doc_version['id']}] (No control)" ;
            // }+
            // var_dump( $doc_version ) ;
            
            $draft_pattern = $uid.'-draft.'.$version.'-*.'. $doc->extension ;
            // $drafts = $this->dms->process_dir_versions ( $path, $draft_pattern ) ;
            
            $drafts = $this->get_files ( $uid, $draft_pattern ) ;
            $this->registry[$v]['data']['drafts'] = $versions_count ;
            
            // debug ( $drafts, $version . ' :: ' . $draft_pattern ) ;
            
            // if ( ! is_array ( $drafts ) ) 
            //     $drafts = array () ;
            
            // rsort ( $drafts ) ;
            // echo "<div>[$draft_pattern]:".count($drafts)."</div>" ;
            
            $file = $doc->dest_path . '/' . $filename ;
            
            // if ( ! file_exists ( $file ) ) continue ;
            
            $fstat = stat ( $file ) ;
            $version = trim(substr($filename,strpos($filename,'.')+1,-4)) ;
            
            $date = date ( "Y-m-d H:i:s", $fstat['mtime'] ) ;
            
            $version_uid = trim(substr($filename,0,-4)) ;
            $res = array () ;
            
            foreach ( $versions as $lut_idx => $lut_version )
                if ( isset ( $lut_version['name'] ) )
                    $res[$lut_idx] = $lut_version['name'] ;
            
            if ( count ( $res ) < 1 ) {
                // no topic; sync up!
                $new_topic = array (
                    'label' => $doc->label . ' (version '.$version.')',
                    'name' => 'document:'.$version_uid,
                    'type' => $this->_type->doc_version,
                ) ;
                $this->sync_file ( $new_topic ) ;
                
            }
            
            $rnd = rand ( 1000, 9999999 ) ;
            
            $v = "v{$rnd}" ;
            $d = "d{$rnd}" ;
            $vr = "$('#{$v}')" ;
            $dr = "$('#{$d}')" ;
            $vvr = "$('#v{$v}')" ;
            $vdr = "$('#v{$d}')" ;
            $e = "'slow'" ;
            $drft = $extra = '' ;
            
            $cnt = count ( $drafts ) ;
            
            if ( $cnt > 0 ) 
                $drft = ' ('.$cnt.')' ;
            
            if ( $counter == 0 ) {
                $extra = "background-color:#fe9;font-weight:bold;" ;
            }
            
            $ver = (int) $version + 1 ;
            if ( $version == '' )
                $ver = $versions_count . ' (current)' ;
            
            $f = $this->dms->version_full_path ( $o, $version, $this->glob->dir->home.'/static/documents'.$home ) ;
            
            if ( ! $just_drafts ) $html .= '<tr id="'.$v.'">' ;
            if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'">'. $ver .$ctrl.'</td>' ;
            if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"><a href="'.$f.'" style="color:blue;">'.$date.'</a></td>' ;
            if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'">'.human_filesize($fstat['size']).'</td>' ;
            
            if ( ! $controlled ) {
                if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"><span style="font-size:0.8em;color:#999;">uncontrolled</span></td>' ;
            } else {
                if ( rand ( 1, 4 ) == 1 ) {
                // really; if document has uploader info
                    if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"><a href="#" style="color:blue;">Alexander Johannesen</a></td>' ;
                } else {
                    if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"> </td>' ;
                }
            }
            
            if ( ! $controlled ) {
                if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"><span style="font-size:0.8em;color:#999;">uncontrolled</span></td>' ;
            } else {
                if ( rand ( 1, 4 ) == 1 ) {
                // really; if document has owner info
                    if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"><a href="#" style="color:blue;">Alexander Johannesen</a></td>' ;
                } else {
                    if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"> </td>' ;
                }
            }
            
            if ( ! $controlled ) {
                if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"><span style="font-size:0.8em;color:#999;">uncontrolled</span></td>' ;
            } else {
                if ( rand ( 1, 4 ) == 1 ) {
                // really; if document has approver info
                    if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"><a href="#" style="color:blue;">Alexander Johannesen</a></td>' ;
                } else {
                    if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'"> </td>' ;
                }
            }
            
            if ( ! $just_drafts ) $html .= '<td style="'.$extra.'">' ;
            // $html .= '   <span class="nolink">make controlled</span> ' ;
            if ( $cnt > 0 ) {
                if ( ! $just_drafts ) $html .= '   <span id="v'.$v.'" onclick="'.$dr.'.slideDown();'.$vdr.'.show();$(this).hide();return false;" class="nolink">drafts'.$drft.' &gt;&gt;</span> ' ;
                if ( ! $just_drafts ) $html .= '   <span id="v'.$d.'" onclick="'.$dr.'.slideUp();'.$vvr.'.show();$(this).hide();return false;" style="display:none;" class="nolink">drafts'.$drft.' &lt;&lt;</span> ' ;
            }
            if ( ! $just_drafts ) $html .= '</td></tr>' ;
            
            if ( count ( $drafts ) > 0 ) {
                if ( ! $just_drafts ) $html .= '<tr><td colspan="7" style="margin:0;padding:0;"><div id="'.$d.'" style="display:none;padding:5px 8px;margin:4px;">' ;
                foreach ( $drafts as $draft ) {
                    
                    $tfile = $path . '/' . $draft ;
                    $tfstat = stat ( $tfile ) ;
                    $tversion = (int) substr($draft,strrpos($draft,'-')+1,-4) ;
                    $tdate = date ( "Y-m-d H:i:s", $fstat['mtime'] ) ;
                    
                    if ( ! $just_drafts ) $html .= '<table style="margin:0;padding:0;border-top:solid 1px red;"><tr>' ;
                        // $html .= '   <td style="'.$extra.'">'. $tversion . $dr . '</td>' ;
                    if ( ! $just_drafts ) $html .= '   <td>'. $tversion .'</td>' ;
                    if ( ! $just_drafts ) $html .= '   <td><a href="'.$this->glob->dir->home.'/documents/'.$uid.'/'.$version.'/'.$tversion.'" style="color:blue;">'.$tdate.'</a></td>' ;
                    if ( ! $just_drafts ) $html .= '   <td>'.human_filesize($tfstat['size']).'</td> ' ;
                    if ( ! $just_drafts ) $html .= '   <td style="'.$extra.'">uploaded by <span class="nolink">Some Guy</span></td> ' ;
                    // $html .= '   <td><span class="nolink">Promote this draft to publishing</span></td>' ;
                    if ( ! $just_drafts ) $html .= '</tr></table>' ;
                    // $html .= "<div style='padding-left:20px;'>[$draft]</div>" ;
                }
                // $html .= "<div style='padding:4px;'><button>Upload draft</button></div>" ;
                if ( ! $just_drafts ) $html .= '</div></td></tr>' ;
            
            
            }
            $counter++ ;
        }
        if ( ! $just_drafts ) $html .= '</table></div>' ;
        
        // $html .= '<h4>Work space</h4>' ;
        // $html .= '<p>no documents.</p>' ;
        
        
        
        /*
        
        $query = array ( 
            'type' => $this->_type->_manifestation,
            'parent' => $id,
        ) ;
        
        $docs = $this->glob->tm->query ( $query ) ;
        
        if ( count ( $docs ) < 1 ) {
            echo "No versions for document {$id}." ; die () ;
        }
        
        $html = '' ;
        
        foreach ( $docs as $doc )
            $html .= '['.$doc['label'].'] ' ;
        */
        echo $html ;

        $this->save_version_registry ( $o->final_path, $uid ) ;
        
        // debug_r ( $this->registry ) ;
        
        die();

    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    function list_people ( $input = null ) {
        $return = '' ;
        if ( $input !== null ) {
            $labels = $this->glob->tm->lookup_topics ( $input ) ;
            foreach ( $labels as $idx => $item ) {
                $return .= '<a href="'.$this->glob->dir->home.'/profile/'.$idx.'">'.$item['label'] . '</a>  ' ;
            }
        }
        return $return ;
    }
    
    function draw_section ( $title, $space = false ) {
        if ( $space ) 
            echo "<tr><td colspan='7' style='border:none;'> </td></tr>" ;
        echo "<tr><td colspan='7' class='section ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>{$title}</td></tr>" ;
    }
    
    function draw_column_heads ( $ret = false ) {
        $str = "<tr class='headlines'><td>version</td><td>age</td><td>size</td><td>publisher</td><td>owner</td><td>approver</td><td>controls</td></tr>" ;
        if ( $ret ) return $str ;
        echo $str ;
    }
    
    function draw_column_heads_draft ( $ret = false ) {
        $str = "<tr class='headlines'><td>draft</td><td>age</td><td>size</td><td>uploader</td><td>comments</td><td colspan='2'>controls</td></tr>" ;
        if ( $ret ) return $str ;
        echo $str ;
    }
    
    function draw_document ( $doc, $just_drafts = false ) {
        
        $topic = $doc->get_topic () ;
        
        // $stored = unserialize ( isset ( $topic['versions'] ) ? $topic['versions'] : '' ) ;
        
        $versions  = $this->dms->get_versions ( $doc ) ;
        $version = $this->dms->find_last_version ( $versions ) ;
        
        // if ( $version == 0 || $version == null ) $version = 1 ;
        
        // $history = new \xs\DocumentManager\History ( $doc->path_dest . '/' . $doc->uid . '.history' ) ;
        // debug_r ( $doc ) ;
        
        $is_controlled = $doc->controlled ;
        // debug_r ( $is_controlled) ;
        
        if ( ! $just_drafts ) {
            ?> <style>
                .section { font-size:1.4em;padding:6px 20px; }
                .headlines, .headlines td { background-color:#ddd;color:#000;font-style:italic; }
                .func { font-size:1.2em;padding:7px 18px;color:#222;background-color:#f90; }
                .func:hover { background-color:#fb2;color:#000; }
                .cmd { color:blue;cursor:pointer;font-size:0.9em; }
            </style> <?php
            echo "<div style='padding:5px 15px;'><table width='100%' id='drafts'>" ;
        }
        
        if ( $is_controlled !== 'false' ) {
            
            $this->draw_section ( 'Drafting area' ) ;
            // debug ( $version ) ;

            $this_drafts  = $this->dms->get_drafts ( $doc, (int) $version + 1 ) ;
            // debug_r ( $this_drafts ) ;

            if ( count ( $this_drafts ) < 1 ) {
                echo "<tr> <td colspan='7'><i>No current drafts found.</i></td> </tr>" ;
            } else {
                $this->draw_column_heads_draft () ;
            }

            foreach ( $this_drafts as $draft_filename ) {

                $version = $this->dms->filename_pick_version ( $draft_filename ) ;
                $draft = $this->dms->filename_pick_draft ( $draft_filename ) ;

                $this_draft = $draft ;

                $d = array () ;

                if ( isset ( $doc->history->struct[$version]['drafts'][$draft] ) )
                    $d = $doc->history->struct[$version]['drafts'][$draft] ;

                $path = $this->dms->draft_full_path ( $doc, $version, $draft ) ;
                $stat = stat ( $path ) ;
                $date = timed ( date ( "Y-m-d H:i:s", $stat['mtime'] ) ) ;
                $size = filesize_formatted ( $stat['size'] ) ;
                $uploader = $this->list_people ( isset ( $d['created'] ) ? $d['created'] : null ) ;

                $comment = '' ;
                if ( isset ( $d['comment'] ) ) {
                    foreach ( $d['comment'] as $idx => $item ) {
                        $comment  .= $idx . '  ' ;
                    }
                }

                $deployment_state = '' ;
                $deployment_state_value = '' ;

                if ( isset ( $topic['deployment_state'] ) ) 
                    $deployment_state = $topic['deployment_state'] ;

                if ( isset ( $topic['deployment_state_value'] ) ) 
                    $deployment_state_value = (int) $topic['deployment_state_value'] ;

                $commands = '' ;

                if ( $deployment_state == '' ) {
                    $commands .= '<span class="cmd" onclick="promote_draft(\''.$version.'\',\''.$draft.'\');">Promote</span>' ; //  | <span class="nolink" onclick="reset_draft(\''.$version.'\',\''.$draft.'\');">Cancel</span>' ;
                } else if ( $deployment_state == 'promoted' ) {
                    if ( $draft == $deployment_state_value )
                        $commands .= '<span class="cmd" onclick="approve_draft(\''.$version.'\',\''.$draft.'\');">Approve</span> | <span class="cmd" onclick="reset_draft(\''.$version.'\',\''.$draft.'\');"><i>Cancel</i></span>' ;
                } else if ( $deployment_state == 'approved' ) {
                    if ( $draft == $deployment_state_value )
                        $commands .= '<span class="cmd" onclick="publish_draft(\''.$version.'\',\''.$draft.'\');">Publish</span> | <span class="cmd" onclick="reset_draft(\''.$version.'\',\''.$draft.'\');"><i>Cancel</i></span>' ;
                }
                $uri = $this->dms->get_dir_structure ( $doc->uid, @$this->glob->config['dms']['destination_uri'] ) .
                        '/' . $this->dms->draft_filename ( $doc, $version, $draft );

                echo "<tr>
                        <td><a href='".$uri."'>draft {$draft}</a></td>
                        <td>{$date}</td>
                        <td>{$size}</td>
                        <td>{$uploader}</td>
                        <td>{$comment}</td>
                        <td><div class='' id='draft-cmd-".$draft."'>{$commands}</div></td>
                    </tr>" ;
            }

            if ( ! $just_drafts )
                echo "</table>" ;
        
        }
        // debug_r ( $versions, $version ) ;
        // debug_r ( $this->dms->find_last_version ( $versions ), 'real_version' ) ;

        // debug_r ( $this_drafts, $this_draft ) ;
        // debug_r ( $this->dms->find_last_draft ( $version, $versions ), 'real_draft' ) ;

        // debug_r ( $doc->history->struct, 'history' ) ;
        
        if ( ! $just_drafts ) {
            
            if ( $is_controlled !== 'false' ) {
                echo "<div style=''>
                    <button id='new-draft' type='button' class='func'>Upload new draft</button>
                </div>" ;
            }
            
            echo "<table style='width:100%'>" ;
        
            $this->draw_section ( 'Versions', true ) ;

            if ( count ( $versions ) == 0 ) {
                echo "<tr> <td><i>No other versions found.</i></td> </tr>" ;
            } else {
                $this->draw_column_heads () ;
            }
            
            $versions = array_reverse ( $versions ) ;
            
            foreach ( $versions as $idx => $ver ) {
                
                $v = $this->dms->filename_pick_version ( $idx ) ;
                $extra = '' ;
                
                if ( $v == $version ) {
                    $extra = " style='background-color:#ec8;color:#530;'" ;
                }
                echo "<tr>";
                
                $d = array () ;
                if ( isset ( $doc->history->struct[$v] ) )
                    $d = $doc->history->struct[$v];

                $commands = '' ;
                
                $path = $this->dms->version_full_path ( $doc, $v ) ;
                $stat = stat ( $path ) ;
                $date = timed ( date ( "Y-m-d H:i:s", $stat['mtime'] ) ) ;
                $size = filesize_formatted ( $stat['size'] ) ;
                $publisher = $this->list_people ( isset ( $d['published'] ) ? $d['published'] : null ) ;
                $approver = $this->list_people ( isset ( $d['approved'] ) ? $d['approved'] : null ) ;
                $owner = $this->list_people ( isset ( $d['has_owner'] ) ? $d['has_owner'] : null ) ;
                
                $comment = '' ;
                if ( isset ( $d['comment'] ) ) {
                    foreach ( $d['comment'] as $idx => $item ) {
                        $comment  .= $idx . '  ' ;
                    }
                }
                
                $uri = $this->dms->get_dir_structure ( $doc->uid, @$this->glob->config['dms']['destination_uri'] ) .
                       '/' . $this->dms->version_filename ( $doc, $v );

                echo "<td".$extra."><a href='".$uri."'><b>$v";
                if ( $v == $version ) echo " (latest)" ;
                echo "</b></a></td>";
                
                $commands = '<span onclick="$(\'#d-'.$v.'\').toggle(\'slow\');" style="cursor:pointer;">drafts &gt;&gt;</span> ' ;
                
                echo "<td".$extra.">$date</td>";
                echo "<td".$extra.">$size</td>";
                echo "<td".$extra.">$publisher</td>";
                echo "<td".$extra.">$owner</td>";
                echo "<td".$extra.">$approver</td>";
                echo "<td".$extra.">$commands</td>";

                echo "</tr>" ;
                
                $this_drafts  = $this->dms->get_drafts ( $doc, $v ) ;
                
                $content = "<table width='100%'>" ;
                $content .= $this->draw_column_heads_draft ( true ) ;
                
                foreach ( $this_drafts as $didx => $draft ) {
                    
                    $draft_no = $this->dms->filename_pick_draft ( $draft ) ;
                    $d = array () ;
                    if ( isset ( $doc->history->struct[$v]['drafts'][$draft_no] ) )
                        $d = $doc->history->struct[$v]['drafts'][$draft_no] ;


                    $path = $this->dms->draft_full_path ( $doc, $v, $draft_no ) ;
                    $stat = stat ( $path ) ;
                    $date = timed ( date ( "Y-m-d H:i:s", $stat['mtime'] ) ) ;
                    $size = filesize_formatted ( $stat['size'] ) ;
                    $uploader = $this->list_people ( isset ( $d['created'] ) ? $d['created'] : null ) ;

                    $comment = '' ;
                    if ( isset ( $d['comment'] ) ) {
                        foreach ( $d['comment'] as $idx => $item ) {
                            $comment  .= $idx . '  ' ;
                        }
                    }
                    
                    $uri = $this->dms->get_dir_structure ( $doc->uid, @$this->glob->config['dms']['destination_uri'] ) .
                            '/' . $this->dms->draft_filename ( $doc, $v, $draft_no );
                    
                    $content .= "<tr>
                        <td><a href='".$uri."'>draft {$draft_no}</a></td>
                        <td>{$date}</td>
                        <td>{$size}</td>
                        <td>{$uploader}</td>
                        <td>{$comment}</td>
                        <td>&nbsp;</td>
                    </tr>" ;

                    
                }
                
                $content .= "</table> <i style='font-size:0.9em;color:#666;'>(note: the last draft is sometimes the same but most often the next version)</i>" ;
                
                // $draft_no : ".print_r($d,true)."
                echo "<tr id='d-{$v}' style='display:none;'>
                        <td colspan='7'>$content</td>
                    </tr>" ;

            }

            echo "</table></div>" ;
        }
        // debug_r ( $versions, $version ) ;
        // debug_r ( $doc->history->struct, 'history' ) ;
        
        if ( $just_drafts )
            return ;
        
        $this_uri = $this->glob->dir->_this ;
?>
    
         <div id="new-draft-form-dialog" title="New draft" style="display:none;background-color:#eee;font-size:1.1em;">
            <form id="new-draft-form" action="<?php echo $this->glob->dir->home ?>/api/data/files" method="post" enctype="multipart/form-data">
            <div class="text ui-widget-content ui-corner-all" style="padding:10px;margin:10px;">
                
                <p style="margin:24px 0;font-size:1.1em;">
                    <label for="myfile"><b>1.</b> select your draft file</label><br/>
                    <input type="file" id="myfile" name="myfile"/>
                </p>
                
                <p>
                    <label for="f:comment"><b>2.</b> What are the changes?</label><br/>
                    <textarea name="f:comment" style="width:100%;height:90px;"></textarea>
                </p>
                
                <div class="progress">
                    <div class="bar"></div >
                    <div class="percent">0%</div >
                </div>

                <div id="status"></div>
                <div id="uploadOutput"></div>


                    <!-- <input type="hidden" name="_redirect" value="<?php echo $this_uri ?>" /> -->
                    <input type="hidden" name="f:id" value="<?php echo $doc->db_id ?>" />
                    <input type="hidden" name="f:upload_mode" value="draft" />
                    <!-- <b>2.</b> <input id="" type="button" onclick="$('#new-draft-form').submit();" value="Upload!" /> -->
       
            </div>
            </form>
        </div>   
        

    <script>

    $(document).ready ( function() {

        $('#new-draft').click(function(){ 
            $('#new-draft-form-dialog').dialog('open');
            $('#myfile').change ( function (test) {
                
                enableOk ( '#new-draft-form-dialog', false ) ;
                // $('button:eq(0)',$('#new-draft-form-dialog').dialog.buttons).button('disable');
                
                // get the file name, possibly with path (depends on browser)
                var filename = $(this).val();

                // Use a regular expression to trim everything before final dot
                var extension = filename.replace(/^.*\./, '');

                // If there is no dot anywhere in filename, we would have extension == filename,
                // so we account for this possibility now
                if (extension === filename) {
                    extension = '';
                } else {
                    // if there is an extension, we convert to lower case
                    // (N.B. this conversion will not effect the value of the extension
                    // on the file upload.)
                    extension = extension.toLowerCase();
                }
                if ( extension == '<?php echo $doc->extension ; ?>' ) {
                    enableOk ( '#new-draft-form-dialog', true ) ;
                    // $('button:eq(0)',$('#new-draft-form-dialog').dialog.buttons).button('enable');
                } else {
                    alert ( 'To upload, the extension of the original (which is <?php echo $doc->extension ; ?>) and the new one (' + extension + ') must be the same' ) ;
                }
            } ) ;
        }) ;
        function enableOk(finder,enable)
        {
            var dlgFirstButton = $(finder).find('button:first');

            if (enable) {
                dlgFirstButton.attr('disabled', '');
                dlgFirstButton.removeClass('ui-state-disabled');
            } else {
                dlgFirstButton.attr('disabled', 'disabled');
                dlgFirstButton.addClass('ui-state-disabled');
            }
        }
        $('#new-draft-form-dialog').dialog({
            height: 600, width: 630,
            autoOpen: false, modal: true,
            show: "explode", hide: "explode",
            buttons: {
                "Upload": function () {
                    
                    var bar = $('.bar');
                    var percent = $('.percent');
                    var status = $('#status');

                    $('#new-draft-form').ajaxForm({
                        beforeSend: function(test) {
                            // alert(test);
                            status.empty();
                            var percentVal = '0%';
                            bar.width(percentVal)
                            percent.html(percentVal);
                        },
                        uploadProgress: function(event, position, total, percentComplete) {
                            var percentVal = percentComplete + '%';
                            bar.width(percentVal);
                            percent.html(percentVal);
                        },
                        success: function(data) {
                            var percentVal = '100%';
                            bar.width(percentVal);
                            percent.html(percentVal);
                            
                            var $out = $('#uploadOutput');
                            $out.html('Form success handler received: <strong>' + typeof data + '</strong>');
                            if (typeof data == 'object' && data.nodeType) {
                                data = elementToString(data.documentElement, true);
                            } else if (typeof data == 'object') {
                                data = objToString(data);
                            }
                            $out.append('<div><pre>'+ data +'</pre></div>');
        
                        },
                        complete: function(xhr) {
                                status.html(xhr.responseText);
                    redraw_versions();
                        }
                    }).submit(); 

                    $(this).dialog('close') ;
                    redraw_versions();
                },
                Cancel: function() { $(this).dialog('close'); }
            }, close: function() { $(this).dialog('close') ;}
        });

     } );
    

    </script>
<?php
    }
    
    
    function draw_draft ( $doc ) {
        
    }
    
    function draw_version ( $doc ) {
        
    }
}
