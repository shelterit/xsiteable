<?php

class xs_action_instance extends \xs\Action {

    private $base_folder = null ;
    private $base_uri = null ;
    private $dms = null ;
    
    private $registry = array () ;
    
    
    function get_files ( $uid, $pattern ) {
        
        $path = $this->dms->get_dir_structure ( $uid, null ) ;
        
        // debug ( $path ) ;
        
        $matches = $this->dms->process_dir_versions ( $path, $pattern ) ;
        
        if ( is_array ( $matches ) )
            rsort ( $matches ) ;

        return $matches ;
    }
    
    function sync_file ( $file = array () ) {
        
        // debug_r ( $file ) ;
    }
    
    function load_version_registry ( $path, $uid ) {
        $ret = array () ;
        $filename = $path. '/_registry_'.$uid.'.arr' ;
        if ( is_file ( $filename ) )
            $ret = @unserialize ( @file_get_contents ( $filename ) ) ;
        $this->registry = $ret ;
    }
    
    function save_version_registry ( $path, $uid ) {
        $filename = $path. '/_registry_'.$uid.'.arr' ;
        @file_put_contents ( $filename, serialize ( $this->registry ) ) ;
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
        
        $result = array () ;
        
        // quick, look up the topic for the id
        if ( $id != '' )
            $result = $this->glob->tm->query ( array ( 'id' => $id ) ) ;
        
        if ( $uid != '' )
            $result = $this->glob->tm->query ( array ( 'name' => 'document:'.$uid ) ) ;
        
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
        
        $this->draw_document ( $doc ) ;
        
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
    
    function draw_section ( $title, $space = false ) {
        if ( $space ) 
            echo "<tr><td colspan='7'> </td></tr>" ;
        echo "<tr><td colspan='7' class='section'>{$title}</td></tr>" ;
    }
    
    function draw_column_heads () {
        echo "<tr class='headlines'><td>version</td><td>age</td><td>size</td><td>uploader</td><td>owner</td><td>approver</td><td>controls</td></tr>" ;
    }
    
    function draw_document ( $doc ) {
        echo "<div><table width='100%' style='border:dotted 2px red;'>" ;
        
        $this->draw_section ( 'Draft version' ) ;
        
        $this->draw_section ( 'Current version', true ) ;
        
        $this->draw_section ( 'Older versions', true ) ;
        $this->draw_column_heads () ;
        
        foreach ( $doc->versions as $version ) {
            
            echo "<tr>
                     <td colspan='7'>$version</td>
                  </tr>" ;
        }
        
        echo "</table></div>" ;
    }
    
    
    function draw_draft ( $doc ) {
        
    }
    
    function draw_version ( $doc ) {
        
    }
}
