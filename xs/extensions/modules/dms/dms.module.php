<?php

require '_lib_files.php' ;
require '_lib_db.php' ;

class xs_module_dms extends \xs\Action\Generic {
    
    // the unique identifier for the xSiteable Document Control Manager
    private $uid = 'xs_module_dms' ;
    
    // are we running in safe mode? (safe=no reaction)
    public $safe_mode = false ;
    
    // if types are to be defined and used
    protected $___register_types = array ( 
                
        // topic types
        'doc' => 'A document',
        'doc_version' => 'A version of a document',
        'doc_draft' => 'A draft of a version of a document',

        // property types
        'original_path'   => 'Original path of a document',
        'relative_path'   => 'Relative path of a document',
        'final_path'      => 'Final path of a document',
        'home_directory'  => 'Hashed directory structure for a document',
        'filename'        => 'Filename of a document',
        'extension'       => 'Extension of a document',
        'timestamp'       => 'Timestamp of a document',
        'words_count'     => 'Number of words in a document',
        'words_pruned'    => 'Pruned words in a document',
        'words_important' => 'Filtered and weighted words in a document',
        'controlled'      => 'Is the document under DMS control?',
        'deleted'         => 'Is document to be deleted / not viewed?',
        'keywords'        => 'List of keywords found in a document',
        'publish_date'    => 'When document was published',
        'next_review_date'=> 'Next date for review of document',
        'document_id'     => 'Special identifier for the document',
        'document_register' => 'Serialized: history of versions and drafts',

        // association types and roles
        'has_owner'       => 'Document ownership',
        'has_contributed' => 'Document contributors',
        'has_author'      => 'Document author',
        'has_version'     => 'Document has versions',
        
    ) ;
    
        protected $___register_types_alias = array (
            
            'doc' => '_document',
            
        ) ;
        
    // document paths for files spidered from the source directory
    public $spidered_documents = array () ;
    public $controlled_documents = array () ;
    
    // object representations of those files
    // private $spidered_documents_objects = array () ;
    // private $controlled_documents_objects = array () ;

    public $document_objects = array () ;
    
    public $lut_s = null ;
    public $lut_c = null ;
    
    // altrernative path; used for uploaded files
    public $alternative_absolute_path = null ;
    
    private $actual_all_documents = null ;
    
    // index for saving words
    private $idx = array () ;
    
    private $source_path = null ;
    private $alt_source_path = null ;
    
    // indexes for the objects
    private $idx_spidered_path = array () ;
    private $idx_spidered_relative_path = array () ;
    private $idx_spidered_timestamp = array () ;
    private $idx_spidered_label = array () ;
    private $idx_spidered_topic_id = array () ;
    private $idx_spidered_uid = array () ;
    
    private $idx_doc_by_uid = array () ;
    private $idx_doc_by_id = array () ;
    
    private $lut_db = null ;
    private $lut_db_tmp = null ;
    private $lut_db_file = array () ;
    private $lut_db_source = array () ;
    
    private $lut_ctrl = null ;
    private $lut_timestamp = null ;
    private $lut_document_id = null ;
    private $lut_relative = null ;
    private $lut_path = null ;
    private $lut_topic = null ;
    private $lut_uid = null ;
    
    public $all_documents = null ;
    // private $all_final = null ;
    
    private $base_folder = null ;
    
    private $phase = 0 ;
    
    private $filter = null ;
    
    // holds a class for html2txt conversion; instantiated as needed
    private $html2text = null ;
    
    // Shortcut for our API
    private $resource_base = '_api/module/docs' ;

    // Some URIs we want to control
    private $uri_docs = 'docs' ;

    private $lib = null ;
    private $lib_files = null ;
    private $lib_db = null ;
    
    
    private $appendix = null ;
    
    function __construct () {
        
        parent::__construct () ;
        
        // this class owns and deals with these events
        $this->_register_event ( XS_MODULE, 'on_document_new' ) ;
        $this->_register_event ( XS_MODULE, 'on_document_update' ) ;
        $this->_register_event ( XS_MODULE, 'on_document_assign' ) ;
        $this->_register_event ( XS_MODULE, 'on_document_promote' ) ;
        $this->_register_event ( XS_MODULE, 'on_document_approve' ) ;
        $this->_register_event ( XS_MODULE, 'on_document_publish' ) ;
    }
    
    function ___modules () {
        
        // a URI to capture
        $this->_register_resource ( XS_MODULE, $this->resource_base . '/controlled' ) ;
        
        // register a few actions against a given type
        // $this->_register_action ( 'add', DOCUMENT, 'add_document' ) ;
        
        $this->base_folder = @$this->glob->config['dms']['destination_folder'] ;
        $this->base_uri = @$this->glob->config['dms']['destination_uri'] ;
        
        $this->source_path = @$this->glob->config['dms']['source_folder'] ;
        $this->alt_source_paths = @$this->glob->config['dms']['additional_source_folder'] ;
        
        // a couple of helper classes
        $this->lib_files = new dms_lib_files ( $this->glob ) ;
        $this->lib_db = new dms_lib_db ( $this->glob ) ;
        
        // make a quick shorthand for the type (saving to look it up all the time)
        define ( 'DOCUMENT', $this->_type->doc ) ;
        
        // debug_r ( $this->_type->doc ) ;
        // debug_r ( $this->_type ) ;

    }
    
    function health_check ( $safe_mode = false ) {
        
        $this->safe_mode = $safe_mode ;
        
        if ( $this->safe_mode )
            echo "<h2>Running in safe mode!</h2>" ;
        
        // make sure some things are set up first
        $this->_preamble () ;
        
        // we're going to use the appendix (search engine)
        $this->appendix = $this->_get_module ( 'appendix' ) ;
        
        // load the main index; we'll be making changes, for sure
        $this->appendix->load_all_index () ;
        
        // make sure some things are set up first
        $this->_preamble ( null, false ) ;
        
        // first, check that we're ok
        $this->_db_health_check () ;
        
        // secondly, check the integrity of the index
        $this->_index_health_check () ;
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done' ) ;
    }
    
    function daily_process ( $safe_mode = false ) {
        
        $this->_clear_cache () ;
        
        echo "<html>\n<head>\n   <script src='{$this->glob->dir->js}/jquery-1.8.2.js' type='text/javascript'></script>\n</head>\n<body>" ;
        
        $this->safe_mode = $safe_mode ;
        
        if ( $this->safe_mode )
            echo "<h2>Running in safe mode!</h2>" ;
        
        // make sure some things are set up first
        $this->_preamble () ;
        
        // we're going to use the appendix (search engine)
        $this->appendix = $this->_get_module ( 'appendix' ) ;
        
        // load the main index; we'll be making changes, for sure
        $this->appendix->load_all_index () ;
        
        
        // 1. spider, sync to topics, get controlled, merge
        // 2. copy, preview, text, appendix
        
        
        // tell 'em what we're doing
        $this->phaser ( 'Spider source area for supported documents' ) ;
        
        // spider the original directory structure for files
        $this->spidered_documents = $this->lib_files->spider () ;
        
        // create objects from those spidered paths
        $this->document_objects = $this->objectify_topics ( $this->spidered_documents ) ;
        
        $test = array () ;
        foreach ( $this->lut_ctrl as $idx => $v )
            if ( trim($v['value']) !== 'false' )
                $test[$idx] = $v ;

        $howmany0 = count ( $this->spidered_documents ) ;
        $howmany1 = count ( $this->document_objects ) ;
        $howmany2 = count ( $this->lut_db ) ;
        $howmany3 = count ( $this->lut_ctrl ) ;
        $howmany3b = count ( $test ) ;
        $howmany4 = count ( $this->lut_timestamp ) ;
        $howmany5 = count ( $this->lut_document_id ) ;
             
        echo "<li>Found <b>{$howmany0}</b> spidered files.</li>" ;
        
        if ( $howmany0 == 0 ) {
            echo "<li>Odd, none found at [".$this->glob->config['dms']['source_folder']."] Have I got access to read this directory?</li>" ;
        }
        echo "<li>Found <b>{$howmany1}</b> object representations of spidered files.</li>" ;
        echo "<li>Found <b>{$howmany2}</b> document representations in the database.</li>" ;
        echo "<li>Found <b>{$howmany3}</b> controlled properties in the database.</li>" ;
        echo "<li>   of which <b>{$howmany3b}</b> are true (ie. actually controlled).</li>" ;
        echo "<li>Found <b>{$howmany4}</b> timestamped items in the database.</li>" ;
        echo "<li>Found <b>{$howmany5}</b> items in the database with unique document id.</li>" ;
        

        if ( ! is_dir ( $this->base_folder ) ) {
            echo "<p><b>Ouch!</b> The directory [".$this->base_folder."] doesn't seem to exist. Forgotten to configure it properly?" ;
            return ;
        }
        
        if ( ! $this->lib_files->can_write_to_dir ( $this->base_folder ) ) {
            echo "<p><b>Ouch!</b> The directory [".$this->base_folder."] seems to have some access problems (the web server needs read and write access to this area). Forgotten to configure it properly?" ;
            return ;
        }
        
        my_flush() ;
        
        
        // sync spidered documents to topics, or create new topics for new files
        $this->process_sync_spidered_to_topics () ;
        
        // find topics in need of deletion (non-spidered and non-controlled)
        $this->process_find_controlled_and_deletions () ;
        
        // from now on, also work with controlled documents
        $this->document_objects += $this->objectify_topics ( $this->controlled_documents ) ;
        
        $this->process_documents () ;
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done!' ) ;
        
        echo "</body></html>" ;
        
        die () ;
        
        // check timestamps of files, and copy if needed
        $this->process_update_and_copy () ;
        
        // check if HTML and text files are there, or create them
        $this->process_create_previews_and_text_files () ;
        
        // harvest into the indexer / appendix
        $this->process_clean_and_harvest () ;
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done!' ) ;
        
        die() ;
        
    }
    
    function _reindex ( $safe_mode = false ) {
        
        $this->_clear_cache () ;
        
        echo "<html>\n<head>\n   <script src='{$this->glob->dir->js}/jquery-1.8.2.js' type='text/javascript'></script>\n</head>\n<body>" ;
        
        $this->safe_mode = $safe_mode ;
        
        if ( $this->safe_mode )
            echo "<h2>Running in safe mode!</h2>" ;
        
        // make sure some things are set up first
        $this->_preamble () ;
        
        // we're going to use the appendix (search engine)
        $this->appendix = $this->_get_module ( 'appendix' ) ;
        
        // load the main index; we'll be making changes, for sure
        $this->appendix->load_all_index () ;

        $uids = array () ;
        
        // just a quick purge of the index to remove files no longer in action
        foreach ( $this->all_documents as $topic_id => $doc ) {
            $uid = trim ( substr ( $doc['name'], 9 ) ) ;
            if ( strlen ( $uid ) == 32 ) {
                $uids[$uid] = $topic_id ;
            }
        }

        echo "<li>[".count($uids)."] UIDs in the database</li>" ;        
        
        $topics = $this->glob->tm->query ( array ( 'id' => $uids ) ) ;
        
        // debug_r ( $topics ) ;
        
        $docs = $this->objectify_topics ( $topics ) ;
        
        
        // tell 'em what we're doing
        $this->phaser ( 'Go through them all' ) ;
        
        
        foreach ( $docs as $doc ) {
            
            echo "<div>" ;
            $filename = $doc->file_dest_txt ;
            echo $filename . " : " ;
            if (file_exists($filename)) {
                echo 'Yup. ' ;
                $this->_process_index ( $doc ) ;
                // $text = file_get_contents ( $filename ) ;
                
            } else {
                echo 'Nope. ' ;
            }
            echo "</div>" ;
        }
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done!' ) ;
    }
    
    
    
    function process_documents () {
        
        echo "\n<style> 
            table { color:#222;padding:0;margin:0;font-size:10px; } 
            table tr { margin:0;padding:0;} 
            table td { border-right:solid 1px #ddd;border-bottom:solid 1px #ddd;padding:2px 3px;margin:1px; }
            .hider { color:blue;cursor:pointer; }
            .hidden { background-color:#dfd;display:none; }
        </style>\n" ;
        
        echo "<script type='text/javascript'>\n
                function hider(who){ $(who).next().show('fast'); $(who).hide('fast'); }
              </script>\n" ;
        
        $max = count ( $this->document_objects ) ;
        
        foreach ( $this->document_objects as $counter => $doc ) {
            
            // echo $doc->file_timestamp_dest . '/'. $doc->file_timestamp_dest_html .' ' ;
            
            my_flush () ;
            
            if ( ! $doc->file_exist_original && ! $doc->file_exist_original_alt && ! $doc->file_exist_dest ) {
                $doc->show = false ;
                continue ;
            }
            
            // if no downloadable destination file is there
            if ( ! $doc->file_exist_dest || $doc->file_timestamp_original > $doc->file_timestamp_dest ) {
                 
                if ( $doc->controlled == 'true' ) {
                    // this is ok
                } else {
                    
                    // copy new from spidered path
                    $this->archive_copy_file ( $doc, true ) ;
                }
            }
             
            // if no downloadable destination html file is there
            if ( ! $doc->file_exist_dest_html || $doc->file_timestamp_dest > $doc->file_timestamp_dest_html ) {
                $this->_create_html ( $doc ) ;
            }

            // if no txt source file is there
            if ( ! $doc->file_exist_dest_txt || $doc->file_timestamp_dest_html > $doc->file_timestamp_dest_txt ) {
                $this->_create_txt ( $doc ) ;
                $this->_process_index ( $doc ) ;
            }

            // any special action happened?
            $any_action = false ;
            $action_list = '' ;
            $controlled = "<i style='background-color:orange;'>controlled</i>" ;
            if ( $doc->controlled !== 'true' )
                $controlled = "<b>not controlled</b>" ;
            foreach ( $doc->action as $what => $actions )
                foreach ( $actions as $action )
                    if ( $action ) {
                        $any_action = true ;
                        $action_list .= $action ;
                    }

            // If yes, report on this document
            if ( $any_action ) {
                
                echo "<div style='margin:5px 7px;padding:0;border:solid 2px #bdb;'><table width='100%'>" ;
                echo "<tr style='background-color:#efa;'>" ;
                echo "<td style='width:75px;font-size:1.2em;'><b>". ( (int) $counter + 1 ) ."</b> of {$max}<br/>{$controlled}</td>" ;
                echo "<td colspan='2' style='color:#228;font-weight:bold;font-size:1.2em;'><a style='text-decoration:none;' href='".$this->glob->dir->home."/documents/{$doc->uid}'>".$doc->label."</a><br/><span style='color:#44a;font-size:10px;'>[{$doc->relative_path}]</span></td>" ;
                echo "</tr>" ;
                echo "<tr style='background-color:#efa;font-size:11px;'>" ;
                echo "<td colspan='3' style='color:#111;'>uid=[<a style='text-decoration:none;' href='".$this->glob->dir->home."/documents/{$doc->uid}'>{$doc->uid}</a>] db_id=[<a style='text-decoration:none;' href='".$this->glob->dir->home."/_tm/topic/{$doc->db_id}'>{$doc->db_id}</a>] source=[<b style='color:blue;'>{$doc->source}</b>]</td>" ;
                echo "</tr>" ;
                // echo "<tr><td>relative</td><td colspan='2'></td></tr>" ;
                echo "<tr><td>original</td><td colspan='2'>{$doc->file_original}</td></tr>" ;
                echo "<tr><td>dest</td><td colspan='2'>{$doc->file_dest}</td></tr>" ;
                echo "<tr><td>dest_html</td><td colspan='2'>{$doc->file_dest_html}</td></tr>" ;
                echo "</table>" ;
                echo "<table width='100%'><tr style='background-color:#ddd;'>
                    <td>original</td><td>alternative</td><td>dest</td><td>dest_html</td><td>dest_txt</td></tr><tr>
                    <td>".$this->st($doc->file_exist_original)."</td>
                    <td>".$this->st($doc->file_exist_original_alt)."</td>
                    <td>".$this->st($doc->file_exist_dest)."</td>
                    <td>".$this->st($doc->file_exist_dest_html)."</td>
                    <td>".$this->st($doc->file_exist_dest_txt)."</td>
                    </tr><tr>    
                    <td>".$this->tm($doc->file_timestamp_original)."</td>
                    <td>".$this->tm($doc->file_timestamp_original_alt)."</td>
                    <td>".$this->tm($doc->file_timestamp_dest)."</td>
                    <td>".$this->tm($doc->file_timestamp_dest_html)."</td>
                    <td>".$this->tm($doc->file_timestamp_dest_txt)."</td>
                    </tr><tr>    
                    <td colspan='5'>{$action_list}</td>
                </tr></table></div> " ;
            } else {
                // if no, just list the name
                echo "<span title='({$doc->uid}) $doc->label' class='hider' onclick='hider(this)' style='float:left;font-size:10px;color:#999;margin:1px;'><b>". ( (int) $counter + 1 ) ."</b> >> </span><div class='hidden' style='clear:both;'> " ;
                echo "<table width='100%'>" ;
                echo "<tr style='background-color:#afa;font-size:11px;'>" ;
                echo "<td style='width:75px;'><b>".( (int) $counter + 1 )."</b> of {$max}<br/>{$controlled}</td>" ;
                echo "<td style='color:#224;font-size:0.9em;'><a style='text-decoration:none;' href='".$this->glob->dir->home."/documents/{$doc->uid}'>".$doc->label."</a></td>" ;
                echo "</tr></table> </div> " ;
            }
        }
    }
    
    function _copy_upload_to_dest ( $doc ) {
        
    }
    
    function _create_html ( $doc ) {
        
        $ext = $doc->extension ;

        $bash = '' ;
        
        // fetch procedure for conversion
        if ( isset ( $this->glob->config['dms'][$ext.'.create_html'] ) )
            $bash = $this->glob->config['dms'][$ext.'.create_html'] ;
        

        if ( trim ( $bash ) != '' ) {

            $bash = str_replace ( '{$from}', $doc->file_original, $bash ) ;
            $bash = str_replace ( '{$from_link}', $this->base_uri . $doc->home_directory .'/'. $doc->uid .'.'.$doc->extension, $bash ) ;
            $bash = str_replace ( '{$to}', $doc->file_dest_html, $bash ) ;
            $bash = str_replace ( '{$to_path}', $this->base_folder . $doc->home_directory, $bash ) ;
            $bash = str_replace ( '{$uid}', $doc->uid, $bash ) ;

            // echo "<pre style='background-color:yellow;'> $bash </pre>" ;
            // echo "<pre style='background-color:yellow;'> [{$this->glob->config['dms']['destination_folder']}] [{$this->base_folder}] </pre>" ;
            // echo "<pre style='background-color:gray;'>".print_r ( $doc, true )."</pre>" ;

            // my_flush() ;
            
            $retval = 1 ;
            if ( trim ( substr ( strtolower ( $bash ), 0, 4 ) ) == 'php:' ) {
                // echo "<pre style='background-color:red;'>".substr ( $bash, 4 )."</pre>" ;
                eval ( substr ( $bash, 4 ) . ';' ) ;
            } else {
                if ( ! $this->safe_mode ) {

                    // do the thing
                    $last_line = exec ( $bash, $retval ) ;

                    // tell her about it, tell her all your crazy dreams ...
                    $doc->action['create']['dest_to_html'] .= "Ran <span class='hider' onclick='hider(this)'>bash script >></span><span class='hidden'>{$bash}={".print_r($retval,true)."}</span>. " ;

                    // load in the new file
                    $html_content = @file_get_contents ( $doc->file_dest_html ) ;

                    if ( ! $html_content || strlen ( $html_content) == 0 ) {
                        $doc->action['process']['html'] .= "HTML file not found; creation problems? " ;
                        return ;
                    }
                    
                    // what are we to replace with what?
                    $find    = array ( 'bgcolor="#A0A0A0"', '<BODY', '</BODY>' ) ;
                    $replace = array ( 'bgcolor="#efefff"', '<body', '</body><link rel="stylesheet" href="'.$this->glob->dir->css.'/pdf.css?v=1"> ' ) ;

                    // replace things found
                    $html_content = str_replace ( $find, $replace, $html_content, $count ) ;

                    $doc->action['process']['html'] .= "Fresh HTML fixed. " ;

                    // save it back
                    if ( ! $this->safe_mode ) {
                        file_put_contents ( $doc->file_dest_html, $html_content ) ;
                    }
                }
            }
            
            // get state of the various files associated with this document
            $this->doc_get_state ( $doc ) ;

        }
    }
    
    function _create_txt ( $doc ) {
        
        if ( ! file_exists ( $doc->file_dest_html ) ) {
            $doc->action['process']['txt'] .= "No html file; abort. " ;
            return ;
        }
        
        // first, clean up the HTML file itself
        $html = file_get_contents( $doc->file_dest_html ) ;
        
        // we need the generic content module
        $content = $this->_get_module ( 'generic_content' ) ;
        
        // get some text back; convert!
        $txt = $content->convert_html_to_txt ( $html ) ;
        
        // finally, write it to a text file for harvesting
        if ( ! $this->safe_mode ) {
            if ( file_put_contents ( $doc->file_dest_txt, $txt ) )
                $doc->action['create']['html_to_txt'] .= "Converted <span class='hider' onclick='hider(this)'>html >></span><span class='hidden'>{$doc->file_dest_html}</span> to <span class='hider' onclick='hider(this)'>text >></span><span class='hidden'>{$doc->file_dest_txt}</span>. " ;
        }

        // get state of the various files associated with this document
        $this->doc_get_state ( $doc ) ;
    }
    
    function _process_index ( $doc ) {

        if ( ! file_exists ( $doc->file_dest_txt ) ) {
            $doc->action['process']['index'] .= "No text file; abort. " ;
            return ;
        }
        
        // first, clean up the HTML file itself
        $text = file_get_contents( $doc->file_dest_txt ) ;
        
        // harvest!
        $big_label = trim ( strtolower ( str_replace ( array (',','_','(',')','-','+','/'), ' ', $doc->label ) ) ) ;
        
        
        // boost ratings for the label of the document (by cheating the indexer)
        for ( $n=0; $n<100; $n++ )
            $text .= ' ' . $big_label ;
        
        // break up the text again
        $all = explode ( ' ', $text ) ;
        $res = array () ;
        
        // Clean up each chunk of text
        foreach ( $all as $word ) {
            $e = trim ( $word ) ;
            if ( $e != '' ) {
                $r = PorterStemmer::CleanUp (' ' .$e. ' ') ;
                if ($r !== null && $r != '' )
                    if ( isset ( $res[$r] ) )
                        $res[$r]++ ;
                    else
                        $res[$r] = 1 ;
            }
        }
        // debug_r ( $res ) ;
        $doc->action['process']['index'] .= 'Total ['.count($all).'] words. Cleaned ['.count($res).']. ' ;

        
        $final = array();
        $max = 1 ;
        $tot = 0 ;

        foreach ($res as $word => $count )
            if ( $count > 0 ) {
                $final[$word] = $count ;
                if ( $count > $max ) $max = $count ;
                $tot++ ;
            }
        $doc->action['process']['index'] .= 'Final ['.count($final).']. ' ;
        
        // get the appendix module
        $appendix = $this->_get_module ( 'appendix' ) ;
        
        
        // first, delete old references
        $count = $appendix->delete_by_uids ( array ( $doc->uid => $doc->uid ) ) ;
        $doc->action['process']['index'] .= "Deleted [".print_r($count,true)."] old index references. " ;
        
        // debug_r ( $final, 'final' ) ;
        
        // then, inject the new ones
        $count = $appendix->add_terms ( $final, $doc->uid ) ;
        $doc->action['process']['index'] .= "Injected [".print_r($count,true)."] new index references. " ;
        
    }
    
    /*
    function objectify_paths_spidered () {
        
        // create object representations of those spidered files,
        // and fill a few indexes with quick reference data
        $counter = 0 ;
        
        foreach ( $this->spidered_documents as $path => $fstat ) {
            
            // debug_r ( $fstat, $path ) ;
            
             $test = true ;
             
             if ( $this->filter  && $this->filter !== '' )
                 $test = stristr ( $path, $this->filter ) ;
             
             if ( $test ) {
                 
                 $pre = substr ( $path, 0, 6 ) ;
                 
                 if ( $pre == 'topic:' ) {
                     $topic_id = substr ( $path, 6 ) ;
                 }
                 
                // new object
                $this->spidered_documents_objects[$counter] = 
                        new \xs\DocumentManager\Document ( $path, $fstat ) ;

                // fill the look-up table
                $this->lut_s->add ( $counter, 
                    $this->spidered_documents_objects[$counter], 
                    array ( 'label', 'timestamp', 'absolute_path', 'relative_path' ) 
                ) ;
                
                // quick reference to the latest object
                $me = $this->spidered_documents_objects[$counter] ;

                // fill those indexes
                $this->idx_spidered_label[$me->label] = $counter ;
                $this->idx_spidered_path[$me->absolute_path] = $counter ;
                $this->idx_spidered_relative_path[$me->relative_path] = $counter ;
                $this->idx_spidered_timestamp[$me->timestamp] = $counter ;

                // bump if there's more files coming
                $counter++ ;
            }
        }
        
        return $counter ;
        
    }
    */
    
    function _index_health_check () {
        
        // tell 'em what we're doing
        $this->phaser ( 'Search-engine health-check' ) ;
        
        $uids = array () ;
        
        // just a quick purge of the index to remove files no longer in action
        foreach ( $this->all_documents as $topic_id => $doc ) {
            $uid = trim ( substr ( $doc['name'], 9 ) ) ;
            if ( strlen ( $uid ) == 32 ) {
                $uids[$uid] = $uid ;
            }
        }

        echo "<li>[".count($uids)."] UIDs in the database</li>" ;
        
        $other = $this->appendix->find_all_uids_but ( $uids ) ;
        echo "<li>[".count($other)."] UIDs that don't exist in the index</li>" ;
        
        // debug_r ( $other ) ;
        
        $count = $this->appendix->delete_by_uids ( $other ) ;
        echo "<li>[{$count['uid']}] UIDs deleted, in [{$count['term']}] terms.</li>" ;
        
        $count = $this->appendix->purge () ;
        echo "<li>[$count] empty terms purged.</li>" ;
    }
    
    function _db_health_check () {
        
        // tell 'em what we're doing
        $this->phaser ( 'Daily health-check' ) ;
        
        $this->_clear_cache () ;
        
        // quick, get all documents
        $quick = $this->glob->tm->query ( array ( 
            'name:like' => 'document:%',
            'select' => 'id,type1,name',
            'return' => 'topics'
        ) ) ;
        
        echo "<li>[".count($quick)."] documents checked</li>" ;
        
        // who needs a new type specified?
        $need_new_type = array () ;
        $pot = array () ;
        
        // go through them all
        foreach ( $quick as $id => $topic ) {
            if ( (int) $topic['type1'] != DOCUMENT ) {
                $topic['type1'] = DOCUMENT ;
                $need_new_type[$id] = $topic ;
            }
            if ( substr ( $topic['name'], -1 ) == '/' ) {
                $pot[$id] = $topic['name'] ;
            }
        }
        echo "<li>[".count($need_new_type)."] documents changed type</li>" ;
        echo "<li>[".count($pot)."] trailing slashes in relative paths found</li>" ;
        
        // set new type
        foreach ( $need_new_type as $id => $topic )
            if ( ! $this->safe_mode )
                $this->glob->tm->update ( $topic ) ;
        
        
        // ok, look for duplicate documents
        $sql = "select name,id,type1,count(*)
                from xs_topic
                WHERE name like ( 'document:%' )
                group by name
                HAVING COUNT(1) > 1
                ORDER BY COUNT(1) ASC
                " ;
        $duplicates = $this->glob->tm->fetchAll ( $sql ) ;
        
        if ( count ( $duplicates ) > 0 ) {
            
            echo "<li><b style='color:red;'>". count($duplicates) ."</b> duplicates found</li>" ;
            
            $mark_for_deleted = array () ;

            foreach ( $duplicates as $idx => $topic ) {

                $sql2 = "select name,id from xs_topic WHERE name = '{$topic['name']}' " ;
                
                $res = $this->glob->tm->fetchAll ( $sql2 ) ;
                $count = 0 ;
                
                foreach ( $res as $i => $v )
                    if ( $count++ > 0 )
                        $mark_for_deleted[$v['id']] = $i ;
            }
            
            if ( count ( $mark_for_deleted ) > 0 ) {
                echo "<div> - [".count($mark_for_deleted)."] documents marked for deletion</div>" ;
                foreach ( $mark_for_deleted as $topic_id => $unimportant ) {
                    echo "[<b style='color:orange;'>{$topic_id}</b>] " ;
                    if ( ! $this->safe_mode ) $this->glob->tm->delete ( $topic_id ) ;
                    my_flush () ;
                }
                $result = $this->glob->tm->fetchAll ( $sql ) ;
                echo "<li>[".count($result)."] documents still duplicated.</li>" ;
            }
        }
        
        // delete blanks
        $blanks = array () ;
        foreach ( $this->lut_db as $idx => $item ) {
            if ( trim ( $item['value'] == '' ) ) {
                $blanks[$idx] = true ;
                if ( $this->safe_mode )
                    $this->glob->tm->delete ( $idx ) ;
                unset ( $this->lut_db[$idx] ) ;
            }
        }
        echo "<li>[".count($blanks)."] blank documents deleted</li>" ;

        // debug_r ( $this->lut_db ) ;
        
        $this->lib_files->safe_mode = $this->safe_mode ;
        
        $ret = $this->lib_files->delete_duplicates (
            $this->lib_files->find_duplicates ( $this->lut_uid )
        ) ;
        echo "<li>[".count($ret)."] duplicate uid documents deleted</li>" ;
        
        $ret = $this->lib_files->delete_duplicates (
            $this->lib_files->find_duplicates ( $this->lut_db ), true
        ) ;
        echo "<li>[".count($ret)."] duplicate absolute_path documents deleted</li>" ;
        
        $ret = $this->lib_files->delete_duplicates (
            $this->lib_files->find_duplicates ( $this->lut_relative )
        ) ;
        echo "<li>[".count($ret)."] duplicate relative_path documents deleted</li>" ;
        
    }
    
    function forced_harvest_process ( $file_list = null ) {
        
        $this->_preamble () ;
        
        // spider the original directory structure for files
        $this->spidered_documents = $this->lib_files->spider ( $file_list ) ;
        
        $this->document_objects = $this->objectify_paths_spidered ( $this->spidered_documents ) ;
        
        $this->process_sync_spidered_to_topics () ;
        
        $this->process_clean_and_harvest ( true ) ;
        
        $this->phaser ( 'Done!' ) ;
    }
    
    function get_source ( $path ) {
        return $this->lib_files->get_source ( $path ) ;
    }
    
    function find_identity ( $path ) {
        
        // before anything, do a quick and dirty check for the absolute path
        // in the database. If there, no need for further complexity
        
        if ( isset ( $this->lut_path[$path] ) ) {
            return key ( $this->lut_path[$path] ) ;
        }
        
        $find = array () ;
        
        // ways of establishing identity;
        //   1. Is there a match between spidered:absolute_path and db:absolute_path?
        //   2. Is there a match between spidered:relative_path and db:relative_path?

        // what is the likely source? We test two possibilities
        $source = $this->get_source ( $path ) ;

        // next, let's see if we can find that in the database
        $identity = $check = false ;

        // first, convert incoming path to a relative link
        $rel = $this->lib_files->relative_path ( $path, $source ) ;
        
        // echo "<hr style='border-top:solid 2px red;' /> " ;
        
        // debug_r ( array ( $path, $source, $rel ), ' ' ) ;
        
        // then, break that apart into its possible path parts
        $trace = $this->lib_files->path_trace ( $rel, $source ) ;
  
        $check = array () ;
        
        $counter = 0 ;
        
        if ( is_array ( $this->lut_db_tmp ) ) {
            foreach ( $this->lut_db_tmp as $topic_id => $file ) {

                $counter++ ;

                $other_source = '' ;

                if ( isset ( $this->lut_db_source[$file] ) ) {
                    $other_source = $this->lut_db_source[$file] ;
                } else {
                    $other_source = $this->lut_db_source[$file] = $this->get_source ( $file ) ;
                }

                // if ( $counter < 14 ) debug_r ( $other_source, $file ) ;

                // if source not found, skip it
                if ( $other_source === null )
                    continue ;

                // $l = strlen ( $source ) ;
                // if ( $l != 0 ) $l++ ;
                $nab = trim ( substr ( $file, strlen ( $other_source ) + 1, -4 ) ) ;
                $test = array () ;
                foreach ( $trace as $v ) {
                    if ( ( $s = stripos ( $nab, $v ) ) !== false )
                        $test[$v] = (int) stripos ( $nab, $v ) ; 
                    // echo "<div>[".stripos ( $v, $nab )."] ($nab) ($v) </div> \n" ;
                }
                // if ( $counter < 4 ) debug_r ( $test, $counter ) ;

                if ( count ( $test ) > 0 ) {
                    asort ( $test ) ;
                    $check[$rel][$topic_id]['result'] = $test ; // $item['id'] ;
                    $check[$rel][$topic_id]['topic_id'] = $topic_id ;
                    $check[$rel][$topic_id]['id'] = 
                    isset ( $this->lut_timestamp[$topic_id]['id'] ) ? 
                        $this->lut_timestamp[$topic_id]['id'] : null ;
                // if ( $counter < 4 ) debug_r ( $test, $counter ) ;

                }

            }
        }
        
        foreach ( $check as $p => $res ) {
            foreach ( $res as $t_id => $t ) {
                foreach ( $t['result'] as $p => $score ) {
                    $find[$score][$p] = $t_id ;
                }
            }
        }

            // if ( $counter < 4 ) debug_r ( $check, $counter ) ;
        ksort ( $find ) ;
            // if ( $counter < 4 ) debug_r ( $find, $counter ) ;
        return $find ;
    }
    
    function process_sync_spidered_to_topics () {
        
        $this->phaser ( 'Match spidered files against the database' ) ;
        
        $profile = new \xs\Stats\Profiler () ;
        
        $count = 0 ;
        $max = count ( $this->document_objects ) ;
        
        // $lut_path = $lut_topic = array () ;
        
        foreach ( $this->lut_db as $idx => $v ) {
            $this->lut_path[$v['value']][$idx] = $v['id'] ;
            $this->lut_topic[$idx][$v['value']] = $v['id'] ;
        }
        if ( is_array ( $this->lut_path ) ) 
            ksort($this->lut_path);
        if ( is_array ( $this->lut_topic ) ) 
        ksort($this->lut_topic);
        // debug_r ( $lut_topic ) ;
         
        $profile->add ( 'Go!' ) ;
        
        // go through all spidered document objects
        foreach ( $this->document_objects as $counter => $doc ) {
            
            $profile->add ( "[{$counter}] >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> " ) ;
            
            $count++ ;
            // if ( $count > 6 ) break ;
            // echo "<hr/><div><b style='color:green;font-size:1.2em;'>".$doc->label." ({$count} of {$max})</b></div> \n" ;
            // echo "<div style='color:#778;margin-top:-5px;font-size:0.8em;font-style:italic;'>".$doc->absolute_path."</div> " ;
            
            // has the current files identity been established in the database?
            // $identity = $this->find_paths ( $doc->absolute_path ) ;
            // $identity = false ;
            
            $identity = $this->find_identity ( $doc->file_original ) ;
            $profile->add ( "[{$counter}] Find identity ... Done." ) ;
            
            $found_score = 0 ;
            $found_path = '' ;
            
            // echo (int)count($identity)." DB matches. " ;
            // debug_r ( $identity, ' ');
            
            // No topic set yet
            $topic = null ;
            // continue ;
            
            // found in the db?
            if ( $identity ) {
                
                // array means a few options
                if ( is_array ( $identity ) ) {
                    
                    // have we found a better match?
                    $found = null ;
                    
                    // go through identities found (usually just one)
                    foreach ( $identity as $score => $candidate ) {
                        
                        foreach ( $candidate as $path => $topic_id ) {
                            $found = $topic_id ;
                            $found_path = $path ;
                            break ;
                        }
                        
                        $found_score = strlen ( $doc->file_original ) - $score ;
                        break ;
                    }
                    
                    $identity = null ;
                    
                    // if we found something, pass in the identity
                    if ( $found ) 
                        $identity = $found ;
                } else{
                    // echo "Huh? Non array identity? " ;
                }
                 
                // echo "Picked doc with score ".$found_score." (of max ".strlen($doc->absolute_path)."). " ;
 
                $profile->add ( "[{$counter}] Check / query for identity ... Done." ) ;
                
                // do we think we have found the file in the db somewhere?
                if ( $identity ) {
                    
                    // Yes! Fetch that topic!
                    // $find_topic = $this->glob->tm->query ( array ( 'id' => $identity ) ) ;

                    $find_topic = isset ( $this->all_documents[$identity] ) ;
                    
                    // got it?
                    if ( $find_topic ) {

                        // just turn the array of one into just one
                        // $topic = $find_topic[$identity] ;
                        $topic = $this->all_documents[$identity] ;

                        $type = isset ( $topic['type1'] ) ? $topic['type1'] : DOCUMENT ;
                        if ( (int) $type == 0 ) 
                            $type = DOCUMENT ;
                        
                        // inject some more info from the topic into the copied file object
                        $doc->inject ( array (
                            'db_id' => isset ( $topic['id'] ) ? $topic['id'] : '',
                            'label' => $topic['label'],
                            'type' => $type,
                            'timestamp' => isset ( $topic['timestamp'] ) ? $topic['timestamp'] : 0,
                            'name' => isset ( $topic['name'] ) ? $topic['name'] : '',
                        ) ) ;
                        
                        // if topic didn't have a timestamp property, create one
                        if ( ! isset ( $topic['timestamp_db_property'] ) ) {
                            if ( ! $this->safe_mode )
                                $doc->timestamp_db_property = $topic['timestamp_db_property'] = 
                                    $this->glob->tm->create_property ( 
                                        $topic['id'], 'timestamp_db_property', '0'
                                    ) ;
                            // echo "Fixed missing <b>timestamp_db_property</b>. " ;
                        }
                        // inject this after as it relies on info injected above (safety; it could 
                        // be ok, depending on the order in which things are done, but we don't want
                        // no sneaky bugs here
    
                        // is our document controlled?
                        $test = isset ( $this->lut_ctrl[$doc->db_id] ) ;
                        
                        $ctrl = '' ;
                        
                        if ( $test ) {
                            // echo "<li><b>Controlled</b>, so setting up.</li>" ;
                            $doc->inject ( array ( 'controlled' =>  'true' ) ) ;
                            $ctrl = '!';
                        // } else { $doc->inject ( array ( 'spidered' =>  'true' ) ) ;
                        }
                        
                        // update the index
                        $this->idx_spidered_topic_id[$doc->db_id] = $counter ;
                        
                        echo "[<span style='color:green'>$identity</span>".$ctrl."] " ;
                
                        // echo "<b>Synched</b> with <i style='background-color:yellow;'>topic {$identity}</i>. " ;
                        // print_r ( $topic ) ;
                    } else {
                        // echo " Topic [{$doc->db_id}] not found. " ;
                    }
   
                } else {
                    // echo "No identity [".print_r($found,true)." / $identity]. " ;
                }
                
                $profile->add ( "[{$counter}] Check / query for identity ... Done." ) ;
                my_flush () ;
            
            } else {
                
                // continue ;
                
                // no, not found in the database
                // create a topic in the db for it
                
            $profile->add ( "[{$counter}] Add new topic ... " ) ;
                
                $arr = array (
                    'label' => $doc->label,
                    'name' => 'document:' . $doc->uid,
                    'type1' => DOCUMENT,
                    'original_path' => $doc->file_original,
                    'relative_path' => $doc->relative_path,
                    'final_path' => $doc->file_dest,
                    'home_directory' => $doc->home_directory,
                    'filename' => $doc->filename,
                    'extension' => $doc->extension,
                    'timestamp' => $doc->timestamp,
                    'timestamp_db_property' => 0
                ) ;
                
                if ( ! $this->safe_mode )
                    $doc->db_id = $this->glob->tm->create ( $arr ) ;
                
                // update the index
                $this->idx_spidered_topic_id[$doc->db_id] = $counter ;

                // Fetch that topic back!
                $find_topic = $this->glob->tm->query ( array ( 'id' => $doc->db_id ) ) ;

                // got it?
                if ( isset ( $find_topic[$doc->db_id] ) )

                    // just turn the array of one into just one
                    $topic = $find_topic[$doc->db_id] ;

                    echo "[<span style='color:orange'>$doc->db_id</span>] " ;
                // echo "<b>Created</b> new <i style='background-color:#9fa;'>topic {$doc->db_id}</i>. " ;

                $profile->add ( "[{$counter}] Add new topic ... Done." ) ;
                
            }
             
            if ( $topic != null ) {
                $doc->attach_topic ( $topic ) ;
                $this->idx_spidered_uid[$doc->uid] = $counter ;
                // echo "<li>Got a topic. Checking for directories, and associations between them.</li>" ;
                 
            }
            $profile->add ( "[{$counter}] <<<<< " ) ;
            my_flush() ;
        }
        
            $profile->add ( "Done." ) ;
            
            // echo $profile->report () ;
    }
    
    function process_find_controlled_and_deletions () {
        
        // what phase are we?
        $this->phaser ( 'Check for deleted files, and purge if necessary' ) ;

        // load the index for keywords (used for search)
        $appendix = $this->_get_module ( 'appendix' ) ;
        // $appendix->load_all_index () ;
        
        echo "<hr/>" ;
        echo "keywords in index:". $appendix->get_total_count() ."<hr/>" ;
        echo "total uids in index:". $appendix->get_total_uids_count() ."<hr/>" ;
        echo "spidered objects:".count($this->document_objects)."<hr/>" ;
        echo "synched to topics:".count($this->idx_spidered_topic_id)."<hr/>" ;
        echo "spidered uids:".count($this->idx_spidered_uid)."<hr/>" ;
        echo "claimed relative_path's:".count($this->lut_relative)."<hr/>" ;
        echo "all documents with name document*:".count($this->all_documents)."<hr/>" ;
        echo "total documents in db:".count($this->lut_db)." of which ".count($this->lut_ctrl)." are controlled.<hr/>" ;
        
        /*
         *  1. Find all documents that we know about exists; controlled, and spidered
         *  2. If filter is used, don't try to remove anything from the index; only daily full process
         *  3. Get all uids from the index
         *  4. Compare the index uids against the ones we know (in 1.)
         *  5. delete all uids from the index that we don't find in the 1. list
         */
        
        // make sure we're not filtering; no big index changes can be done in fragments
        if ( $this->filter === null ) {
        
            $all_uids = $remove_uids = $all_documents = array () ;

            foreach ( $this->lut_ctrl as $topic_id => $item ) {
                $uid = null ;
                if ( isset ( $this->all_documents[$topic_id] ) ) {
                    $uid = substr ( $this->all_documents[$topic_id]['name'], 9 ) ;
                    $this->controlled_documents[$topic_id] = $this->all_documents[$topic_id] ;
                }
                
                echo "# " ;
                $all_documents[$topic_id] = array (
                    'controlled' => 'true',
                    'uid' => $uid
                ) ;
            }//

            $e = 0 ;
            foreach ( $this->idx_spidered_topic_id as $topic_id => $item ) {
                $test = $this->document_objects[$item] ;
                $all_documents[$topic_id] = array (
                    'controlled' => 'false',
                    'uid' => $test->uid
                ) ;
                // if ( $e++ < 5 ) debug_r ( $test, $topic_id ) ;
            }
            krsort ( $all_documents ) ;

            // debug_r ( $all_documents ) ;

            // ok, got all documents, and hopefully their UIDs.
            
            
            $count = 0 ;
            $blanks = array () ;

            echo "<p>All_docs_count=[".count($this->all_documents)."] actual_docs_count=[".count($all_documents)."]</p>" ;

            $found = $not_found = array () ;
            $counter = 0 ;
            
            foreach ( $this->all_documents as $topic_id => $item ) {
                // if ( $counter++ > 30 ) break ;
                $uid = @substr ( $item['name'], 9 ) ;
                $all_uids[$uid] = $item['id'] ;
                if ( isset ( $all_documents[$topic_id] ) ) {
                    
                    $found[$topic_id] = $item ;
                    
                    if ( ! isset ( $item['relative_path'] ) ) {

                        $find = array () ;

                        // has the current files identity been established in the database?
                        $identity = $this->find_paths ( $item['original_path'] ) ;
                        // debug_r ( $identity, 'original' ) ;

                        if ( is_array ( $identity ) )
                            foreach ( $identity as $path => $res ) {
                                foreach ( $res as $t_id => $t ) {
                                    foreach ( $t['result'] as $path => $score ) {
                                        $find[$score][$path] = $t_id ;
                                    }
                                }
                            }

                        if ( isset ( $this->glob->config['dms']['additional_source_folder'] ) ) {
                            $identity = $this->find_paths ( $item['original_path'], $this->glob->config['dms']['additional_source_folder'] ) ;
                            // debug_r ( $identity, 'extended' ) ;
                            if ( is_array ( $identity ) )
                                foreach ( $identity as $path => $res ) {
                                    foreach ( $res as $t_id => $t ) {
                                        foreach ( $t['result'] as $path => $score ) {
                                            $find[$score][$path] = $t_id ;
                                        }
                                    }
                                }
                        }

                        ksort ( $find ) ;
                        //debug_r ( $find ) ;
                        $final = reset ( $find ) ;
                        $key = key ( $final ) ;
                        // debug ( $final, $key ) ;
                        $r = '' ;
                        if ( ! $this->safe_mode )
                            $r = $this->glob->tm->create_property ( $topic_id, 'relative_path', $key ) ;
                        echo "<div>added property 'relative_path'=[$key] to topic[".$topic_id."] (".print_r($r,true).")</div>" ;
                        my_flush () ;
                    }
                    
                } else {
                    $not_found[$topic_id] = $item ;
                    if ( isset ( $this->lut_document_id[$topic_id] ) ) {
                        unset ( $this->spidered_documents_objects[$this->idx_spidered_topic_id[$topic_id]] ) ;
                    }
                }
            }
            
            $not_found_uids = array () ;
            foreach ( $not_found as $id => $item )
                $not_found_uids[substr($item['name'],9)] = $id ;
            
            
            $old_count = count ( $this->lut_relative ) ;
            $this->lut_relative = $this->lib_db->find_db_properties ( DOCUMENT, 'relative_path' ) ;
            $new_count = count ( $this->lut_relative ) ;
            echo "<p style='border:solid 3px orange;margin:5px 0;'>Old relative_path count=[{$old_count}], new count=[{$new_count}]</p>" ;
            
            echo "<p>All_uids=[".count($all_uids)."] Found_count=[".count($found)."] not_found_count=[".count($not_found)."] not_found_uids_count=[".count($not_found_uids)."]</p>" ;
            echo "<p>To be deleted (".count($not_found).") 
                should be all (".count($this->all_documents).") 
              - all_actual (".count($all_documents).") 
              = (". (int) ( count($this->all_documents) - ( count ( $all_documents ) ) ) .")</p>" ;

            $this->actual_all_documents = $all_documents ;
            
            
            /*
            
            echo "<div>of all UIDs actually found in the index: " ;
            $idx_uids = $appendix->get_uids () ;
            
            // debug ( $idx_uids ) ;
            // debug ( $all_uids ) ;
            
            
            $count = $cc = 0 ;
            foreach ( $idx_uids as $uid => $item ) {
                if ( isset ( $not_found_uids[$uid] ) ) {
                    // echo "[$uid] found in topic [{$not_found_uids[$uid]}] to be deleted from db. " ;
                }
                if ( ! isset ( $all_uids[$uid] ) ) {
                    // echo "[$uid] not found to be deleted from index. " ;
                    if ( ! $this->safe_mode ) 
                        $appendix->delete_by_uid ( $uid ) ;
                    my_flush () ;
                    $cc++ ;
                } else {
                    $count++ ;
                }
            }
            echo "</div> Safe=[{$count}], deleted=[{$cc}] <hr />" ;
             * 
             */
        }

        foreach ( $not_found as $topic_id => $item ) {
            $uid = trim ( substr ( $item['name'], 9 ) ) ;
            
            if ( isset ( $this->idx_doc_by_id[$topic_id] ) ) {
                echo "!!! " ;
            }

            if ( ! $this->safe_mode ) {
                echo "[<span style='color:red'>$topic_id</span>] " ;
                my_flush () ;
                $this->glob->tm->delete ( $topic_id ) ;
            } else{
                echo "[<span style='color:orange;font-size:9px;'>$topic_id</span>] " ;
            }
                
            $found = $appendix->find_by_uid ( $uid ) ;
            // if ( count ( $found ) > 0 )  debug_r ( $found, $uid . ' : ' . $topic_id ) ;

            // echo "[$topic_id] " ;
        }
        
        
        
        /*

        echo "<p>Going through ".count($this->lut_db)." database items, finding them in ".count($this->idx_spidered_topic_id)." topics spidered.</p>" ;
        
        // debug_r ( $this->idx_spidered_topic_id ) ;
        
        foreach ( $this->lut_db as $topic_id => $item ) {
            
             if ( ! isset ( $this->idx_spidered_topic_id[$topic_id] ) ) {

                // echo '[in_db_not_spidered]' ;
                // Yes! Fetch that topic!
                // 
                // $topic = $this->glob->tm->query ( array ( 'id' => $topic_id ) ) ;
                 
                 if ( isset ( $this->idx_spidered_topic_id[$topic_id] ) )  {
                     
                    // echo '[in_db_and_spidered]' ;
                
                    $idx = $this->idx_spidered_topic_id[$topic_id] ;
                    $doc = $this->spidered_documents_objects[$idx] ;
                    if ( $doc ) {

                        $fetch = $doc->get_topic () ;

                        // got it?
                        if ( $fetch ) {

                            // just turn the array of one into just one
                            $topic = $fetch ;
                            // echo "<li>Topic [$topic_id] with path [{$topic['original_path']}] seems to NOT be found in the spidered directory structure.</li>" ;

                            if ( isset ( $topic['controlled'] ) )
                                echo "<li>Topic [$topic_id] with path [{$topic['original_path']}] not found, but seems to be a controlled document. Will <i style='background-color:#9fa;'>not delete</i> automatically.</li>" ;
                            else {
                                echo "<li>Topic [$topic_id] with path [{$topic['original_path']}] <i style='background-color:yellow;'>slated for deletion</i>.</li>" ;

                                if ( isset ( $topic['deleted'] ) && $topic['deleted'] == 'true' ) {
                                    // echo "<li>ignore</li>" ;
                                } else {
                                    if ( ! $this->safe_mode )
                                        $this->glob->tm->create_property ( $topic_id, 'deleted', 'true' ) ;
                                    $topic['deleted'] = 'true' ;
                                    $doc->attach_topic ( $topic ) ;
                                    $doc->deleted = 'true' ;
                                    unset ( $this->idx_spidered_uid[$doc->uid] ) ;
                                }
                            }

                        } else {
                            echo "<li><b style='background-color:red;padding:2px 4px;margin:3px;color:white;'>Topic [$topic_id] seems to NOT be found in DB. Hmm, odd.</b> </li>" ;
                        }
                    } else {

                        echo " Topic [{$idx}] not found. " ;
                    }
                 } else {
                    // echo " No index for topic [{$topic_id}] found. " ;
                 }
             }
            my_flush() ;
        }
        */
        // $appendix->save_index () ;
    }
    
    /*
    function _process_controlled_document ( $topic ) {
        
    }
    
    function _process_spidered_document ( $topic ) {
        
    }
    */
    
    function st ( $x ) { if ( $x == true ) return 'true' ; return 'false' ; }
    
    function tm ( $t ) { return date ( $this->glob->config['framework']['date_format'], $t ) ; }
    
    
    
    function _action_touch_original ( $topics ) {
        
        $this->safe_mode = false ;
        $this->_preamble () ;
        
        if ( $this->appendix == null )
            $this->appendix = $this->_get_module ( 'appendix' ) ;
        
        // load the main index; we'll be making changes, for sure
        $this->appendix->load_all_index () ;
        
        $this->document_objects = $this->objectify_topics ( $topics ) ;
        
        foreach ( $this->document_objects as $doc ) {
            if ( $doc->controlled == 'true' ) {
                if (file_exists ( $doc->file_dest ) )
                    if ( touch_it_good ( $doc->file_dest ) )
                        $doc->action['touch']['original'] .= "Touched destination original. " ;
                    else
                        $doc->action['touch']['original'] .= "Failed to touch destination original (permission problems, probably). " ;
            } else {
                if (file_exists ( $doc->file_original ) )
                    if ( touch_it_good ( $doc->file_original ) )
                        $doc->action['touch']['original'] .= "Touched original. " ;
                    else
                        $doc->action['touch']['original'] .= "Failed to touch original (permission problems, probably). " ;
            }
        }
        
        // get state of the various files associated with this document
        $doc = $this->doc_get_state ( $doc ) ;
        
        $this->process_documents () ;
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done!' ) ;
        
    }

    function _action_touch_dest ( $topics ) {
        
        $this->safe_mode = false ;
        $this->_preamble () ;
        
        if ( $this->appendix == null )
             $this->appendix = $this->_get_module ( 'appendix' ) ;
        
        // load the main index; we'll be making changes, for sure
        $this->appendix->load_all_index () ;
        
        $this->document_objects = $this->objectify_topics ( $topics ) ;
        
        foreach ( $this->document_objects as $doc ) {
            if (file_exists ( $doc->file_dest ) )
                if ( @touch ( $doc->file_dest ) )
                    $doc->action['touch']['dest'] .= "Touched destination file. " ;
                else
                    $doc->action['touch']['dest'] .= "Failed to touch destination file (permission problems, probably). " ;
        }
        
        // get state of the various files associated with this document
        $this->doc_get_state ( $doc ) ;
        
        $this->process_documents () ;
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done!' ) ;
        
    }
    
    function _action_reindex ( $topics ) {
        
        $this->safe_mode = false ;
        $this->_preamble () ;
        
        if ( $this->appendix == null )
            $this->appendix = $this->_get_module ( 'appendix' ) ;
        
        // load the main index; we'll be making changes, for sure
        $this->appendix->load_all_index () ;
        
        $this->document_objects = $this->objectify_topics ( $topics ) ;
        
        foreach ( $this->document_objects as $doc ) {
            $uid = $doc->uid ;
            $find = $this->appendix->find_by_uid ( $uid ) ;
            // debug_r ( $find ) ;
            // debug_r ( $doc ) ;
            $doc->action['touch']['dest'] .= "Re-index the document for searching. " ;
        }
        
        // get state of the various files associated with this document
        $this->doc_get_state ( $doc ) ;
        
        $this->process_documents () ;
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done!' ) ;
    }

    function _action_touch_dest_html ( $topics ) {
        
        $this->safe_mode = false ;
        $this->_preamble () ;
        
        if ( $this->appendix == null )
            $this->appendix = $this->_get_module ( 'appendix' ) ;
        
        // load the main index; we'll be making changes, for sure
        $this->appendix->load_all_index () ;
        
        $this->document_objects = $this->objectify_topics ( $topics ) ;
        
        foreach ( $this->document_objects as $doc ) {
            if (file_exists ( $doc->file_dest_html ) )
                if ( @touch ( $doc->file_dest_html ) )
                    $doc->action['touch']['dest_html'] .= "Touched HTML. " ;
                else
                    $doc->action['touch']['dest_html'] .= "Failed to touch HTML (permission problems, probably). " ;
        }
        
        // get state of the various files associated with this document
        $this->doc_get_state ( $doc ) ;
        
        $this->process_documents () ;
        
        // is it safe? Then save the index.
        if ( ! $this->safe_mode )
            $this->appendix->save_index () ;
        
        $this->phaser ( 'Done!' ) ;
        
    }

    function process_create_previews_and_text_files () {
        
        
        // move this into the preceding step
        // $this->all_final = 
        //         $this->spidered_documents_objects + 
        //         $this->controlled_documents_objects ;
        
        $count = 0 ;
        $max = count ( $this->document_objects ) ;
        $this->phaser ( 'Create previews and indexing text files for ['.$max.']' ) ;

        foreach ( $this->document_objects as $counter => $doc ) {
            
            $count++ ;
                
            if ( $doc->deleted == 'true'  || $doc->show == false ) {
                continue ;
            }

            if ( $doc->create_preview || ! file_exists ( $doc->file_dest_html ) ) {
                
                echo "<hr/><h3>".$doc->label." ({$count} of {$max}) [{$doc->id}]</h3> " ;
                echo "<div style='color:#778;margin:-18px 0 10px 0;font-size:0.8em;font-style:italic;'>".$doc->file_dest_html." [".file_exists ( $doc->file_dest_html )."] [".$doc->create_preview."]</div> " ;
             
                if ( ! file_exists ( $doc->file_dest_html ) )
                    echo "File has no preview; <i style='background-color:#9fa;'>create it</i>. " ;
                else
                    echo "<li>File needs to <i style='background-color:#9fa;'>update preview</i>. " ;
                    
                // create the HTML and TXT versions of the original file
                $this->create_html_and_txt ( $doc ) ;
                
                $doc->harvest = 'true' ;
            } else {
                echo "<div>Preview file safe and sound.</div>" ;
                // debug ( file_exists ( $doc->file_dest_html ) );
            }
            my_flush() ;
        }
    }
    
    function process_clean_and_harvest ( $forced = false ) {
        
        $this->phaser ( 'Clean and harvest text from preview files, and update search indexes' ) ;

        // move this into the preceding step
        // $this->all_final = 
        //         $this->spidered_documents_objects + 
        //         $this->controlled_documents_objects ;
        
        
        // load the index for keywords (used for search)
        $appendix = $this->_get_module ( 'appendix' ) ;
        // $appendix->reset () ;
        // $appendix->load_all_index () ;
        
        $count = 0 ;
        
        if ( $forced ) {
            
            // if forced, remember to merge in not only spidered docs, but also
            // controlled ones from the database
        }
        
        
        
        $max = count ( $this->document_objects ) ;
        echo "<p>Cleaning [{$max}] documents.</p>" ;
        
        
        foreach ( $this->document_objects as $counter => $doc ) {
            
            $count++ ;
            
            $topic = $doc->get_topic () ;
            
            if ( $doc->deleted == 'true' || $doc->show == false )
                continue ;
            
            // $txt  = $this->base_folder . $doc->home_directory . "/{$doc->uid}.txt" ;
            // $pre  = $this->base_folder . $doc->home_directory . "/{$doc->uid}.html" ;
            
            $cond = false ;
            if ( $doc->harvest == 'true' || ! ( file_exists ( $doc->file_dest_txt ) ) )
                $cond = true ;
            
            if ( $forced ) 
                $cond = true ;
            
            if ( $cond ) {
                
                echo "<hr/><h3>".$doc->label." ({$count} of {$max}) [{$topic['id']}]</h3> " ;
                echo "<div style='color:#778;margin:-18px 0 10px 0;font-size:0.8em;font-style:italic;'>".$doc->final_file."</div> " ;
                
                if ( $doc->harvest == 'true' )
                    echo "Asked to harvest. " ;
                
                if ( ! file_exists ( $doc->file_dest ) )
                    echo "<b style='color:red;'>No copy file</b>?? " ;
                
                if ( ! file_exists ( $doc->file_dest_html ) )
                    echo "<b style='color:red;'>No preview file</b> where we should already have one. " ;
                
                if ( ! ( file_exists ( $doc->file_dest_txt ) ) )
                    echo "No file; forced to harvest. " ;
                
                if ( $forced )
                    echo "Forced to harvest. " ;
                
                $this->clean_and_harvest ( $doc, $appendix ) ;
            }
            my_flush() ;
        }

        // ... aaaaaand, save the index
        // $appendix->save_index () ;
        
    }
    
    function clean_and_harvest ( $doc, $appendix ) {
        
        $profiler = new \xs\Stats\Profiler () ;
        
        if ( $this->html2text == null )
            $this->html2text = new html2text () ;

        // $doc hasn't got the Topic structure. Convert! Convert!
        $topic = $doc->get_topic () ;
        
        // keep counting
        $count = 0 ;
        
        // represent some reporting
        $rep = '' ;
        
        if ( ! file_exists ( $doc->file_dest_html ) ) {
            echo "<div>No preview file found. Exiting.</div>" ;
            return ;
        }
        
        // $appendix->delete_by_uid ( $doc->uid ) ;
        
        // first, clean up the HTML file itself
        $html_content = file_get_contents( $doc->file_dest_html ) ;
        $profiler->add ( 'HTML read' ) ;
        
        // what are we to replace with what?
        $find    = array ( 'bgcolor="#A0A0A0"', '<BODY', '</BODY>' ) ;
        $replace = array ( 'bgcolor="#efefff"', '<body', '</body><link rel="stylesheet" href="'.$this->glob->dir->css.'/pdf.css?v=1"> ' ) ;
        
        // replace things found
        $html_content = str_replace ( $find, $replace, $html_content, $count ) ;
        $profiler->add ( 'replaced BODY' ) ;
        
        // save it back
        if ( ! $this->safe_mode ) {
            file_put_contents ( $doc->file_dest_html, $html_content ) ;
            $profiler->add ( 'saved file back' ) ;
        }
        // report
        $rep .= "Fix; [".$count."] html concepts. " ;

        
        // second, create a clean, neat text representation, and save that!

        // replace a few naughty characters
        $naughty = array ( '&nbsp;', '$', 'â' ) ;
        $html_content = str_replace( $naughty, ' ', $html_content, $count ) ;
        $rep .= "Replaced [".$count."] problematic characters. " ;
        $profiler->add ( 'fixed naughty' ) ;
        
        // inject the html into the converter
        $this->html2text->set_html ( $html_content ) ;
        
        // pull out the text
        $text = $this->html2text->get_text () ;
        
        $profiler->add ( 'HTML2text done' ) ;

        // no content?
        if ( trim ( $text ) == '' )
            echo "Hmm. No content. <b style='color:red;'>Permission problems, or conversion failure?</b> " ;
        
        // echo "<div style='font-size:0.8em;color:#966;'>$text</div>" ;
        
        // make sure the characters of the text is in a certain range
        $w = '' ;
        for ( $n=0; $n<strlen($text); $n++ ) {
            $q = ord ( $text[$n] ) ;
            if ( ( $q > 64 && $q < 91 ) || ( $q > 96 && $q < 123 ) || ( $q > 47 && $q < 58 ) || $q == 32 || $q == 13 )
                $w .= $text[$n] ;
        }
        $text = strtolower ( $w ) ;
        $profiler->add ( 'range check' ) ;
        
        // echo "<div style='font-size:0.8em;color:#777;'>$text</div>" ;
        
        // break up all words; BigMama of a trim!
        $all = explode ( ' ', $text ) ;
        $r = '' ;
        foreach ( $all as $l ) {
            $l = trim ( $l ) ;
            if ( strlen ( $l ) > 1 )
                $r .= $l . ' ' ;
        }
        $text = $r ;
        $profiler->add ( 'text trimmed' ) ;

        // finally, write it to a text file for harvesting
        if ( ! $this->safe_mode ) {
            file_put_contents ( $doc->file_dest_txt, $text ) ;
            $profiler->add ( 'text file saved' ) ;
        }
        
        // report
        $rep .= "Cleaned text file [{$doc->file_dest_txt}], found [".count($all).'] words, cleaned ['.count(explode(' ',$r))."]. " ;

        // harvest!
        $big_label = trim ( strtolower ( str_replace ( array (',','_','(',')','-','+','/'), ' ', $doc->label ) ) ) ;
        $profiler->add ( 'big character replace' ) ;
        
        
        // boost ratings for the label of the document (by cheating the indexer)
        for ( $n=0; $n<100; $n++ )
            $text .= ' ' . $big_label ;
        
        // break up the text again
        $all = explode ( ' ', $text ) ;
        $res = array () ;
        
        // Clean up each chunk of text
        foreach ( $all as $word ) {
            $e = trim ( $word ) ;
            if ( $e != '' ) {
                $r = PorterStemmer::CleanUp (' ' .$e. ' ') ;
                if ($r !== null && $r != '' )
                    $res[] = $r;
            }
        }
        $rep .= 'Cleaned ['.count($res).'] of total ['.count($all).'] words. ' ;
        $profiler->add ( 'cleaned and stemmed' ) ;


        $n = array();
        foreach ($res as $r)
            if (isset($n[$r]))
                $n[$r]++;
            else
                $n[$r] = 1;

         print_r ( $n ) ;
        
        foreach ( $n as $keyword => $count ) {
            if ( ! isset ( $this->idx[$keyword][$doc->uid] ) )
                $this->idx[$keyword][$doc->uid] = $count ;
            else
                $this->idx[$keyword][$doc->uid] += $count ;
        }
        
        $topic['words_count'] = count ( $res ) ;
        $topic['words_pruned'] = count ( $n ) ;
        
        $words_pruned = sizeof ( $n ) ;
        $words_keyed = sizeof ( $this->idx ) ;
        $rep .= 'Pruned ['.$words_pruned.'] words, while keyworded ['.$words_keyed.']. ' ;
        $profiler->add ( 'keywords extracted' ) ;
        
        
        
        $m = array();
        $max = 1 ;
        $tot = 0 ;

        foreach ($n as $w => $c)
            if ($c > 2) {
                $m[$w] = $c;
                if ( $c > $max ) $max = $c ;
                $tot++ ;
            }
        $words_final = count ( $m ) ;
        $rep .= 'Final words ['.$words_final.']. ' ;
        $profiler->add ( 'word count' ) ;
        
        
        /*
        $div = 250 / $max ;
        foreach ($m as $w => $c) {
            $cal = (int) 251 - (int) ( $div * $c ) ;
        } */
        $words_important = $m ;
        $rep .= 'Important words ['.count($words_important).']. ' ;
        $topic['words_important'] = serialize ( $m ) ;
        
        
        $appendix->add_terms ( $words_important ) ;
        
        
        $profiler->add ( 'misc' ) ;
        /*
        echo "Type is '".$topic['type1']."'. " ;
            
        if ( (int) $topic['type1'] == 0 ) {
            $topic['type1'] = DOCUMENT ;
            echo "Fixed document type (from '0' to '".DOCUMENT."'. " ;
            $profiler->add ( 'fixed document type' ) ;
        }
        */
        // echo "<pre>" . print_r ( $tmp, true ) . "</pre> " ;
        
        
        // update the topic with the added info
        if ( ! $this->safe_mode ) {
            $this->glob->tm->update ( $topic ) ;
            $profiler->add ( 'updated topic' ) ;
        }
        
        // creates some facets
        // $words_facets = explode ( '/', $doc->relative_path ) ;
        
        echo $rep ; // . ' :: ' . $profiler->report () ;

    }

    function create_html_and_txt ( $doc ) {
        
            
        // $doc = $this->spidered_documents_objects[$idx] ;
        
        // $file = $this->base_folder . $doc->home_directory . "/{$doc->uid}.{$doc->extension}" ;
        // $html = $this->base_folder . $doc->home_directory . "/{$doc->uid}.html" ;
        // $txt  = $this->base_folder . $doc->home_directory . "/{$doc->uid}.txt" ;

        $ext = $doc->extension ;

        $bash = '' ;
        
        // fetch procedure for conversion
        if ( isset ( $this->glob->config['dms'][$ext.'.create_html'] ) )
            $bash = $this->glob->config['dms'][$ext.'.create_html'] ;
        
            // echo "<pre style='background-color:yellow;'> [{$this->glob->config['dms']['destination_folder']}] [{$this->base_folder}] [{$doc->home_directory}] </pre>" ;

        if ( trim ( $bash ) != '' ) {

            $bash = str_replace ( '{$from}', $doc->file_dest, $bash ) ;
            $bash = str_replace ( '{$from_link}', $this->base_uri . $doc->home_directory .'/'. $doc->uid .'.'.$doc->extension, $bash ) ;
            $bash = str_replace ( '{$to}', $doc->file_dest_html, $bash ) ;
            $bash = str_replace ( '{$to_path}', $this->base_folder . $doc->home_directory, $bash ) ;
            $bash = str_replace ( '{$uid}', $doc->uid, $bash ) ;

            // echo "<pre style='background-color:yellow;'> $bash </pre>" ;
            // echo "<pre style='background-color:yellow;'> [{$this->glob->config['dms']['destination_folder']}] [{$this->base_folder}] </pre>" ;
            // echo "<pre style='background-color:gray;'>".print_r ( $doc, true )."</pre>" ;

            my_flush() ;
            
            $retval = 1 ;
            if ( trim ( substr ( strtolower ( $bash ), 0, 4 ) ) == 'php:' ) {
                // echo "<pre style='background-color:red;'>".substr ( $bash, 4 )."</pre>" ;
                eval ( substr ( $bash, 4 ) . ';' ) ;
            } else {
                    if ( ! $this->safe_mode ) {
                        
                        // do the thing
                        $last_line = exec ( $bash, $retval ) ;
                        
                        // load in the new file
                        $html_content = file_get_contents ( $doc->file_dest_html ) ;

                        // what are we to replace with what?
                        $find    = array ( 'bgcolor="#A0A0A0"', '<BODY', '</BODY>' ) ;
                        $replace = array ( 'bgcolor="#efefff"', '<body', '</body><link rel="stylesheet" href="'.$this->glob->dir->css.'/pdf.css?v=1"> ' ) ;

                        // replace things found
                        $html_content = str_replace ( $find, $replace, $html_content, $count ) ;
                        
                        $doc->action['process']['html'] .= "Fresh HTML fixed. " ;

                        // save it back
                        if ( ! $this->safe_mode ) {
                            file_put_contents ( $doc->file_dest_html, $html_content ) ;
                        }
                        
                        
                        
                    }
                    // $this->docs_repo[$uid]['_converted'] = $file['_spidered'] ;
            }
            $doc->action['create']['dest_to_html'] .= "Ran <span style='background-color:#ccc;'>{$bash}={$retval}</span>. " ;
            
            // echo "Command: <b style='color:green'>{$bash}'</b> = '<b style='color:blue'>".print_r($retval,true)."'</b>. " ;

        }
    }
    
    
    function doc_get_state ( $doc ) {
        
        $doc->file_exist_original_alt = file_exists ( $doc->file_original_alt ) ;
        $doc->file_exist_original = file_exists ( $doc->file_original ) ;
        $doc->file_exist_dest = file_exists ( $doc->file_dest ) ;
        $doc->file_exist_dest_html = file_exists ( $doc->file_dest_html ) ;
        $doc->file_exist_dest_txt  = file_exists ( $doc->file_dest_txt ) ;

        if ( $doc->file_exist_original )
            $doc->file_timestamp_original = filemtime ( $doc->file_original ) ;

        if ( $doc->file_exist_original_alt )
            $doc->file_timestamp_original_alt = filemtime ( $doc->file_original_alt ) ;

        if ( $doc->file_exist_dest )
            $doc->file_timestamp_dest = filemtime ( $doc->file_dest ) ;

        if ( $doc->file_exist_dest_html )
            $doc->file_timestamp_dest_html = filemtime ( $doc->file_dest_html ) ;

        if ( $doc->file_exist_dest_txt )
            $doc->file_timestamp_dest_txt = filemtime ( $doc->file_dest_txt ) ;

        return $doc ;
    }
    
    function objectify_topics ( $these ) {
        
        // returning array
        $ret = array () ;
        
        // create object representations of controlled topics in the db
        $counter = 0 ;
        
        foreach ( $these as $topic_id => $topic ) {
            
            $fstat = array () ;
            $path = '' ;
             
            if ( ! isset ( $topic['id'] ) ) {
                $fstat = $topic ;
                $path = $topic_id ;
                $topic = null ;
            }
            // debug_r ( $fstat, $path ) ;
            
             $test = true ;
             
             // if ( $this->filter  && $this->filter !== '' )
             //    $test = stristr ( $path, $this->filter ) ;
             
             // if ( $test ) {

             // if ( rand ( 0, 20 ) == 1 ) debug_r ( $topic ) ;
             
             // debug_r ( $topic ) ;
             
             if ( isset ( $topic['timestamp'] ) )
                 $fstat['mtime'] = $topic['timestamp'] ;

             if ( isset ( $topic['original_path'] ) ) 
                 $path = $topic['original_path'] ;
             

            // new object
            $doc = new \xs\DocumentManager\Document ( $path, $fstat, $topic ) ;
            // $doc->attach_topic ( $topic ) ;

            // if ( isset ( $topic['relative_path'] ) )
            //     $doc->relative_path = $topic['relative_path'] ;
                 
            $uid  = $doc->uid ;
            $path = $this->get_dir_structure ( $doc->uid, null, true ) ;
            $file = $path.'/'. $uid ;

            $doc->path_dest = $path ;
            $doc->file_dest = $file.'.'. $doc->extension ;
            $doc->file_dest_html = $file.'.html' ;
            $doc->file_dest_txt = $file.'.txt' ;

            $doc = $this->doc_get_state ( $doc ) ;
            
            if ( ! $doc->file_exist_original ) {

                $doc->source = $this->get_source ( $path ) ;
            
                // redo the label; it might be wrong
                // $doc->update_relative () ;
                // $doc->update_label () ;

                if ( isset ( $this->glob->config['dms']['convert_additional_to_source'] ) && 
                     $this->glob->config['dms']['convert_additional_to_source'] == true ) {

                    // echo " <div>Original file not found at original path. " ;

                    $test = array_keyify ( array_merge ( 
                        array ( $this->source_path ), 
                        $this->alt_source_paths
                    ) ) ;

                    $file_found = null ;

                    $rel = '/' . $doc->relative_path 
                               .'.'. $doc->extension ;
                    
                    foreach ( $test as $idx => $path ) {
                        $file = $path . $rel ;
                        if ( file_exists ( $file ) ) {
                            $file_found = $path ;
                            // echo " <b>Found [$idx]</b> " ;
                        }
                        // debug(,$file);
                    }
                    
                    if ( $file_found !== null ) {
                        // echo " <i>choosing [$file_found]</i>; converting: " ;
                        // debug_r ( $topic ) ;
                        // echo "Found file at [$file_found]; converting. " ;
                        $doc->original_copy_at = $file_found ;
                        $doc->file_original_alt = $file_found . $rel ;
                        $doc->file_exist_original_alt = file_exists ( $doc->file_original_alt ) ;
                        $doc->file_timestamp_original_alt = filemtime ( $doc->file_original_alt ) ;

                    }
                }
                
            }

            // fill those indexes
            $this->idx_spidered_label[$doc->label] = $counter ;
            $this->idx_spidered_path[$doc->absolute_path] = $counter ;
            $this->idx_spidered_relative_path[$doc->relative_path] = $counter ;
            $this->idx_spidered_timestamp[$doc->timestamp] = $counter ;
            
            $this->idx_doc_by_uid[$doc->uid] = $counter ;
            $this->idx_doc_by_id[$doc->db_id] = $counter ;
            
            $versions = $this->get_versions ( $doc ) ;
            $doc->versions = array_merge ( $versions, array ( $doc->uid.'.'.$doc->extension ) ) ;
            // $versions_count = count ( $versions ) ;
            // $version  = $this->dms->find_last_version ( $versions ) ;
                
                
            // if ( rand ( 0, 20 ) == 1 ) debug_r ( $this->controlled_documents_objects[$counter]->versions ) ;
            // if ( rand ( 0, 20 ) == 1 ) debug_r ( $this->controlled_documents_objects[$counter] ) ;

                // 
            $ret[$counter] = $doc ;
            
                // bump if there's more files coming
            $counter++ ;
            // }
        }
        
        return $ret ;
        
    }
    
    function _clear_cache () {
        
        $config = array ( 
            'time' => '+1 day',
            'cache_dir' => $this->glob->config['framework']['cache_directory'] 
        ) ;
        
        $lut = new \xs\Cache\Cache ( 'preamble_lut_relative', $config, $this->glob ) ;
        $all = new \xs\Cache\Cache ( 'preamble_all_docs', $config, $this->glob ) ;

        $lut->reset () ;
        $all->reset () ;
    }
    
    
    function _preamble_search () {
        
        $config = array ( 
            'time' => '+1 day',
            'cache_dir' => $this->glob->config['framework']['cache_directory'] 
        ) ;
        
        $all = new \xs\Cache\Cache ( 'preamble_all_docs', $config, $this->glob ) ;
        if ( $all->has_expired () ) {
            
            $all->put ( $this->glob->tm->query ( 
                array ( 'name:like' => 'document:%', 'type' => DOCUMENT ) 
            ) ) ;
            
        } 
        $this->all_documents = $all->get () ;

            
            
        $lut = new \xs\Cache\Cache ( 'preamble_lut_relative', $config, $this->glob ) ;
        if ( ! $this->lut_relative || count ( $this->lut_relative ) == 0 ) {
        
            if ( $lut->has_expired () )
                $lut->put ( $this->lib_db->find_db_properties ( DOCUMENT, 'relative_path' ) ) ;

            $this->lut_relative = $lut->get () ;

        }
 
    }

    function _preamble_browse () {
        
        $config = array ( 
            'time' => '+1 day',
            'cache_dir' => $this->glob->config['framework']['cache_directory'] 
        ) ;
  
        $lut = new \xs\Cache\Cache ( 'preamble_lut_relative', $config, $this->glob ) ;
        if ( ! $this->lut_relative || count ( $this->lut_relative ) == 0 ) {
        
            if ( $lut->has_expired () )
                $lut->put ( $this->lib_db->find_db_properties ( DOCUMENT, 'relative_path' ) ) ;

            $this->lut_relative = $lut->get () ;

        }
 
    }

    // _preamble is treated as a static (first-time) initiation function, but only when
    // we use functionality that requires these lookup tables
    
    function _preamble ( $filter = null, $verbose = true ) {
        
        if ( $verbose ) 
            $this->phaser ( 'Preamble :: setting up data' ) ;
        
        if ( $filter == null )
            $filter = trim ( $this->glob->breakdown->id ) ;
        
        if ( $filter != '' ) {
            if ( $verbose ) 
                echo "<h1>Filter: '{$filter}'</h1>" ;
            $this->filter = $filter ;
        }
        
        // get all documents from the database
        $this->all_documents = $this->glob->tm->query ( array ( 'name:like' => 'document:%', 'type' => DOCUMENT ) ) ;
        
        // find all items in the db that has a relative path
        if ( ! $this->lut_relative || count ( $this->lut_relative ) == 0 )
            $this->lut_relative = $this->lib_db->find_db_properties ( DOCUMENT, 'relative_path' ) ;
        
        // check the database for all documents with paths
        if ( ! $this->lut_db || count ( $this->lut_db ) == 0 )
            $this->lut_db = $this->lib_db->find_db_properties ( DOCUMENT, 'original_path' ) ;
        
        foreach ( $this->lut_db as $topic_id => $item )
            $this->lut_db_tmp[$topic_id] = $item['value'] ;


        // echo "<p>Found <b>[".count($this->lut_db)."]</b> 'original_path's in the database.</p>" ;
        // echo "<pre>".print_r($this->lut_db,true)."</pre>" ;
        
        // check the database for all documents with paths
        if ( ! $this->lut_uid || count ( $this->lut_uid ) == 0 )
            $this->lut_uid = $this->lib_db->find_db_properties ( DOCUMENT, 'uid' ) ;
        // echo "<p>Found <b>[".count($this->lut_db)."]</b> 'original_path's in the database.</p>" ;
        // echo "<pre>".print_r($this->lut_db,true)."</pre>" ;
        
        // find all things with a timestamp property (not timestamps of topics)
        if ( ! $this->lut_timestamp  || count ( $this->lut_timestamp ) == 0 )
            $this->lut_timestamp = $this->lib_db->find_db_properties ( DOCUMENT, 'timestamp' ) ;
        // echo "<p>Found <b>[".count($this->lut_timestamp)."]</b> timestamps in the database.</p>" ;
        // echo "<pre>".print_r($this->lut_timestamp,true)."</pre>" ;
        
        // check the database for all controlled documents
        if ( ! $this->lut_ctrl  || count ( $this->lut_ctrl ) == 0 )
            $this->lut_ctrl = $this->lib_db->find_db_properties ( DOCUMENT, 'controlled', 'true' ) ;
        // echo "<p>Found <b>[".count($this->lut_ctrl)."]</b> controlled items in the database.</p>" ;
        // echo "<pre>".print_r($this->lut_ctrl,true)."</pre>" ;
        
    }

    function get_doc_relative ( $lut ) {
        // debug( $lut, 'get_doc_relative');
        // debug( $this->idx_spidered_relative_path, 'get_doc_relative');
        if ( isset ( $this->idx_spidered_relative_path[$lut] ) )
            if ( isset ( $this->spidered_documents_objects[$this->idx_spidered_relative_path[$lut]] ) )
                return $this->spidered_documents_objects[$this->idx_spidered_relative_path[$lut]] ;
    }
    
    // create a headline
    function phaser ( $phaser ) {
        $this->phase++ ;
        echo "<div style='background-color:#ccc;border:solid 8px orange;margin:20px 0;padding:10px;font-size:1.2em;'> Phase {$this->phase}: $phaser </div> " ;
    }
    
    function create_tree_list ( $incoming, $current_path, $last = '' ) {
        
        $index = array () ;
        $subs = array () ;
        $ids = array () ;
        
        // incoming current position
        $la = urldecode ( trim ( $last ) ) ;

        // debug_r ( $this->lut_relative ) ;
        
        // loop through all found relative paths to documents
        if ( is_array ( $incoming ) )
            
            foreach ( $incoming as $topic_id => $item ) {
            
            // split path apart to create facets
            $facets = explode ( '/', $item['value'] ) ;
            
            // any?
            if ( count ( $facets ) > 0 ) {
                
                $index = recurse_array ( $facets, $index ) ;
                
                $test = substr ( $item['value'], 0, strrpos ( $item['value'], '/' ) ) ;
                
                // echo "[".$item['value']."]_[$test]-[$current_path]=".(trim ( $test ) == trim ( $current_path ))."<br /> " ;
                
                if ( trim ( $test ) == trim ( $current_path ) )
                    $ids[$topic_id] = $topic_id ;
                
                $here = false ;
                
                foreach ( $facets as $idx => $value ) {
                    if ( $here ) {
                        if ( isset ( $facets[$idx + 1] ) ) {
                            $path = '' ;
                            foreach ( $facets as $z => $q ) {
                                if ( $z == $idx + 1 ) break ;
                                $path .= urlencode($q) . '/' ;
                            }
                            $subs[substr($path, 0, -1)] = $value ;
                            break ;
                        }
                    }
                    if ( $value == $la )
                        $here = true ;
                }                
                
            }
        }
        
        return array ( 
            'ids' => $ids,
            'subs' => $subs,
            'index' => $index
        ) ;
    }

    function create_tree ( $current_path, $last = '' ) {
        
        // a few data points we need
        $this->_preamble_browse () ;
        
        $props = array () ;
        $p = $this->glob->tm->get_all_prop_for_topic_type ( $this->_type->doc_draft, 'relative_path' ) ;
        foreach ( $p as $a => $b ) {
            $props[$b['parent']]['id'] = $b['id'] ;
            $props[$b['parent']]['value'] = $b['value'] ;
        }
        
        // arrays to fill with joy!
        $docs = array () ;
        $drafts = array () ;
        
        $from_docs = $this->create_tree_list ( $this->lut_relative, $current_path, $last ) ;
        $from_drafts = $this->create_tree_list ( $props, $current_path, $last ) ;
        
        
        // debug_r ( $from_drafts) ;
        
        
        $documents = $this->glob->tm->query ( array ( 'id' => $from_docs['ids'] ) ) ;

        foreach ( $documents as $topic_id => $document ) {
            $uid = trim ( substr ( $document['name'], 9 ) ) ;
            $docs[$uid]['id'] = $document['id'] ;
            $docs[$uid]['uid'] = $uid ;
            $docs[$uid]['label'] = $document['label'] ;
            $docs[$uid]['controlled'] = isset ( $document['controlled'] ) ? $document['controlled'] : 'false'  ;
            $docs[$uid]['source'] = isset ( $document['source'] ) ? $document['source'] : ''  ;
            $ext = isset ( $document['extension'] ) ? $document['extension'] : 'doc'  ;
            $docs[$uid]['extension'] = '' ;
            if ( isset ( $this->glob->config['dms'][$ext.'.icon'] ) )
                $docs[$uid]['extension'] = $this->glob->config['dms'][$ext.'.icon'] ;
        }
        
        natsort2d ( $docs, 'label' ) ;
        natsort ( $from_docs['subs'] ) ;
        
        $this->glob->stack->add ( 'xs_tree', $from_docs['index'] ) ;
        $this->glob->stack->add ( 'xs_current_folders', $from_docs['subs'] ) ;
        $this->glob->stack->add ( 'xs_tree_docs', $docs ) ;
        
        
        
        $documents = $this->glob->tm->query ( array ( 'id' => $from_drafts['ids'] ) ) ;

        foreach ( $documents as $topic_id => $document ) {
            $uid = trim ( substr ( $document['name'], 9 ) ) ;
            $drafts[$uid]['id'] = $document['id'] ;
            $drafts[$uid]['uid'] = $uid ;
            $drafts[$uid]['label'] = $document['label'] ;
            $drafts[$uid]['controlled'] = isset ( $document['controlled'] ) ? $document['controlled'] : 'false'  ;
            $drafts[$uid]['source'] = isset ( $document['source'] ) ? $document['source'] : ''  ;
            $ext = isset ( $document['extension'] ) ? $document['extension'] : 'doc'  ;
            $drafts[$uid]['extension'] = '' ;
            if ( isset ( $this->glob->config['dms'][$ext.'.icon'] ) )
                $drafts[$uid]['extension'] = $this->glob->config['dms'][$ext.'.icon'] ;
        }
        
        natsort2d ( $drafts, 'label' ) ;
        natsort ( $from_drafts['subs'] ) ;
        
        $this->glob->stack->add ( 'xs_tree_drafts', $from_drafts['index'] ) ;
        $this->glob->stack->add ( 'xs_current_folders_drafts', $from_drafts['subs'] ) ;
        $this->glob->stack->add ( 'xs_tree_docs_drafts', $drafts ) ;
        
        // TODO : bug in the future; the tree structures (folders only) for docs and drafts 
        // should probably be merged at some point so you can browse the whole 
        // structure without getting confused
        
    }
    
    function get_relative_paths ( $dir = '' ) {
        
        $this->_preamble_browse () ;
        
        $count_dir = count ( explode ( '/', $dir ) ) ;
        
        $rel = array () ;
        foreach ( $this->lut_relative as $tid => $item ) {
            $bits = explode ( '/', $item['value'] ) ;
            $count_path = count ( $bits ) ;
            $f = $bits[$count_path - 1] ;
            unset ( $bits[$count_path - 1] ) ;
            $p = implode ('/', $bits) ;
            $rel[$p][$f] = $tid ;
            
        }
            // $rel[$item['value']] = $tid ;
        return $rel ;
        
        echo "<pre>".print_r($rel,true)."</pre>" ;
        
        $paths = array () ;
        
        foreach ( $this->lut_relative as $topic_id => $item ) {
            
            $path = $item['value'] ;
            
            $paths[$topic_id] = $path ;
        
            $bits = explode ( '/', $path ) ;
            $count_path = count ( $bits ) ;

            if ( $count_dir == $count_path ) {
                // echo "<pre>($count_dir)($dir)</pre>" ;
                // debug_r ( $item ) ;
                // echo "<pre>($count_path)[$path]</pre><hr/>" ;

            }
        
        }
        
        asort ( $paths ) ;
        return ; // $paths ;
        
        echo "<pre>".print_r($paths,true)."</pre>" ;
        
    }
    
    function check_directories ( $uid ) {
        $size = strlen ( $uid ) ;
        // if filename is larger than 4, creating matching sub-folders
        if ( $size > 3 ) {
            $q = $w = '/' ;
            $q .= $uid[0] ; $q .= $uid[1] ;
            $w .= $uid[2] ; $w .= $uid[3] ;
            $l1 = $this->base_folder . $q ;
            $l2 = $l1 . $w ;
            if ( ! is_dir ( $l1 ) ) mkdir ( $l1 ) ;
            if ( ! is_dir ( $l2 ) ) mkdir ( $l2 ) ;
        }
    }
    
    function archive_copy_file ( $doc, $force = false ) {
  
        if ( ! $force && $doc->controlled == 'true' ) {
            echo "<li>File is controlled. No copying as it's handled manually.</li> " ;
            return ;
        }
        
        //debug_r ( $doc, $doc->file_dest ) ;
        $dest = $doc->file_dest ;
        
        // make sure we've got the directories we need; if not, create them
        $this->check_directories ( $doc->uid ) ;
        
        // if ( ! $doc->final_path ) $doc->final_path = $this->base_folder . $dest ;
        // if ( ! $doc->final_file )  $doc->final_file = $doc->final_path . '/' . $doc->uid . '.' . $doc->extension ;

        // are we to archive and version old files?
        
        if ( isset ( $this->glob->config['dms']['archive'] ) &&
             ( $this->glob->config['dms']['archive'] == true || 
                $this->glob->config['dms']['archive'] == 'controlled' ) && 
             file_exists ( $doc->file_dest ) 
           ) {
            
            // file there already. Make a copy?
            
            // find other versions
            $matches = $this->lib_files->process_dir_versions ( $doc->path_dest,
                    $doc->uid.'.*.'. $doc->extension ) ;
            
            // how many versions?
            $version = count ( $matches ) ;
            
            // only if there's any other versions there
            if ( $version != 0 ) {
                
                // what would a new version path look like?
                $newversion = $this->lib_files->version_full_path ( $doc, $version ) ;

                // ok, make a versioned copy
                echo "<li>version copy [{$doc->final_file}] (of total [{$version}]) to [<i style='background-color:#dde;'>{$newversion}</i>]</li>" ;

                $doc->action['copy']['dest_copy_version'] .= 'Copied new version. ' ;

                if ( ! $this->safe_mode )
                    stream_copy ( $dest, $newversion ) ;

            }
        }
        
        $absolute = $doc->file_original ;
        
        if ( $this->alternative_absolute_path != null ) {
            $absolute = $this->alternative_absolute_path ;
            $doc->action['copy']['source_alt_to_dest'] .= 'Used alternative source. ' ;
        }
        
        if ( $doc->original_copy_at != null ) {
            $absolute = $doc->file_original_alt ;
            $doc->action['copy']['source_alt_to_dest'] .= 'Used alternative source. ' ;
        }
        // echo "copy [{$doc->absolute_path}] to [$final] \n<br/><hr/>" ;
        // make a generic copy
        echo "<li>copy [<i style='background-color:#dde;'>{$absolute}</i>] to [<i style='background-color:#dde;'>{$dest}</i>] </li>" ;
        
        // debug_r ( $doc, $dest ) ;
        
        $doc->action['copy']['source_to_dest'] .= "Copy <span class='hider' onclick='hider(this)'>original >></span><span class='hidden'>[{$absolute}]</span> to <span class='hider' onclick='hider(this)'>destination >></span><span class='hidden'>[{$dest}] !! </span>. " ;
        
        if ( ! $this->safe_mode ) {
            if ( (int) stream_copy ( $absolute, $dest ) == 0 )
                $doc->action['copy']['source_to_dest'] .= 'Fail. ' ;
        } else {
            $doc->action['copy']['source_to_dest'] .= 'Safe-mode; no copying. ' ;
        }
        $this->doc_get_state ( $doc ) ;
        // debug_r ( $doc->action ) ;
    }
  
    function draft_to_version_copy_file ( $doc, $draft = null ) {
        
        // if ( ! $doc->path_dest ) 
        //     $doc->path_dest = $this->lib_files->get_dir_structure ( $doc->uid, null, true ) ;
        
        // if ( ! $doc->file_dest ) 
        //     $doc->file_dest = $doc->path_dest . '/' . $doc->uid . '.' . $doc->extension ;
        
        // are we to archive and version old files?

        // find other versions
        // $matches = $this->lib_files->get_versions ( $doc ) ;
        
        // which version?
        $version = (int) $this->lib_files->find_last_version ( $this->lib_files->get_versions ( $doc ) ) ;
        
        $old_file = null ;
        $new_file = null ;
        $draft_file = null ;
        $version_file = null ;
        
        if ( $version == null || $version == 0 ) { 
            
            // meaning; no version yet; this is an incoming draft that will 
            // become the first draft

            $version = 1 ;
            
            $new_file = $this->lib_files->version_full_path ( $doc, $version ) ;
            
        } else {
            
            // Normal copying of versions
            $new_file = $this->lib_files->version_full_path ( $doc, $version + 1 ) ;
            
        }

        // what is the incoming draft path?
        $draft_file = $this->lib_files->draft_full_path ( $doc, $version, $draft ) ;
        
        // find all drafts of last version
        // $drafts = $this->lib_files->get_drafts ( $doc, $version ) ;
        // debug_r ( $drafts, 'get_drafts' ) ;
        // $draft = (int) $this->lib_files->find_last_draft ( $drafts ) ;

        // $drafts_count = count ( $drafts ) ;
        

        $version_file = $this->lib_files->full_path ( $doc ) ;
        
        // $absolute = $doc->file_original ;
        
        // debug_r ( array ( 'old' => $old_file, 'new' => $new_file, 'draft' => $draft_file, 'version' => $version_file ) ) ;
        
        // echo "<li>copy [{$old_file}] to new version [<i style='background-color:#dde;'>{$new_file}</i>]</li>" ;
        // echo "<li>copy from draft [{$draft_file}] to new version [<i style='background-color:#dde;'>{$new_file}</i>]</li>" ;
        // echo "<li>copy from draft [{$draft_file}] to normal [<i style='background-color:#dde;'>{$version_file}</i>]</li>" ;
        
        // if ( $old_file && file_exists ( $old_file ) ) { }
        
        if ( ! $this->safe_mode ) stream_copy ( $draft_file, $new_file ) ;
        if ( ! $this->safe_mode ) stream_copy ( $draft_file, $version_file ) ;
        
    }
    
    function draft_copy_file ( $doc, $force = false ) {
  
        // $doc = $this->spidered_documents_objects[$idx] ;
        
        // if ( ! $force && $doc->controlled == 'true' ) {
        //     echo "<li>File is controlled. No copying as it's handled manually.</li> " ;
        //     return ;
        // }
        
        // what's our destination?
        if ( ! $doc->path_dest ) 
            $doc->path_dest = $this->lib_files->get_dir_structure ( $doc->uid, null, true ) ;
        
        if ( ! $doc->file_dest ) 
            $doc->file_dest = $doc->path_dest . '/' . $doc->uid . '.' . $doc->extension ;
        
        // are we to archive and version old files?

        // find other versions
        $matches = $this->lib_files->get_versions ( $doc ) ;
        // debug_r ( $matches, 'get_versions' ) ;
        $version = $this->lib_files->find_last_version ( $matches ) ;
        
        
        if ( $version == null || $version == 0 ) { 
            
            // meaning; no version yet; this is an incoming draft that will 
            // become the first draft

            $version = 1 ;

        } else {
            $version++ ;
        }


        
        
        
        // find all drafts of the next version
        $drafts = $this->lib_files->get_drafts ( $doc, $version ) ;
        // debug_r ( $drafts, 'get_drafts' ) ;
        $draft = $this->lib_files->find_last_draft ( $drafts ) ;

        $drafts_count = count ( $drafts ) ;
        
        // what is the new draft path?
        $newdraft = $this->lib_files->draft_full_path ( $doc, $version, $draft + 1 ) ;

        $absolute = $doc->file_original ;
        
        if ( $this->alternative_absolute_path != null )
            $absolute = $this->alternative_absolute_path ;
        
        if ( $doc->original_copy_at != null )
            $absolute = $doc->original_copy_at . '/' . $doc->relative_path . '.' . $doc->extension ;
        
        // echo "<li>draft copy [{$absolute}] (of total [{$drafts_count}]) to [<i style='background-color:#dde;'>{$newdraft}</i>]</li>" ;
        if ( ! $this->safe_mode )
            stream_copy ( $absolute, $newdraft ) ;
    }
    
    function find_paths ( $path, $extended = false ) {
        
        $identity = false ;

        $rel = $this->lib_files->relative_path ( $path, $extended ) ;
        $trace = $this->lib_files->path_trace ( $rel, $extended ) ;

        // is the absolute path found in the db?
        if ( ( $check = array_search ( $path, $this->lut_db, true ) ) )
            $identity = $check ;

        // is a few different path variations found?
        if ( ! $identity && ( $check = $this->multi_array_search ( $trace, $this->lut_db, true ) ) ) {
            $identity = $check ;
        }

        // returns a number if original_path found, or 
        // an array if more relative paths found
        
        return $identity ;
    }
    
    function multi_array_search ( $paths, $db, $source ) {
        
        $check = array () ;
        
        foreach ( $db as $topic_id => $item ) {
            
            $rel = $this->lib_files->relative_path ( $item['value'], $source ) ;
            $test = $this->lib_files->array_lsearch ( $rel, $paths ) ;

            if ( count ( $test ) > 0 ) {
                $check[$item['value']][$topic_id]['result'] = $test ; // $item['id'] ;
                $check[$item['value']][$topic_id]['topic_id'] = $topic_id ;
                $check[$item['value']][$topic_id]['id'] = 
                   isset ( $this->lut_timestamp[$topic_id]['id'] ) ? 
                      $this->lut_timestamp[$topic_id]['id'] : null ;
            }
        }
        // debug_r ( $check, 'result' ) ;
        return $check ;
    }
    

    
    
    
    function action () {

        // Is the incoming URI the URI we want to control / hijack?
        if ( $this->_meta->uri == $this->uri_docs ) {

            // Ok, create a new action controller for the page we'll show
            $page = new \xs\Action\Webpage () ;

            // Inject this file path (so, not the path of where the class
            // is defined, but where this module is located
            $page->_meta->action_path = __FILE__ ;

            $this->glob->log->add ( 'Document control module : Action!' ) ;
        }

    }

    public function GET () {
        $uid = 'document:' . $this->glob->request->uid ;
        $type = $this->glob->request->type ;
        $documents = $this->glob->tm->query ( array ( 'name' => $uid ) ) ;
        $ret = '-' ;
        if ( count ( $documents ) > 0 ) {
            foreach ( $documents as $idx => $doc ) {
                $uid = trim ( substr ( $doc['name'], 9 ) ) ;
                $controlled = isset ( $doc['controlled'] ) ? $doc['controlled'] : false  ;

                // echo "<pre style='background-color:green;'>" ; var_dump ( $doc ) ; echo "</pre>" ;
                // echo "<pre style='background-color:red;'>" ; var_dump ( $controlled ) ; echo "</pre>" ;

                $topic = new \xs\TopicMaps\Topic ( $doc ) ;

                if ( $controlled !== 'true' ) {
                    $topic->set ( 'controlled', 'true' ) ;
                    if ( $type == 1 )
                        $ret = '<img src="'.$this->glob->dir->images.'/icons/24x24/actions/button_ok.png" height="15" style="margin:0;padding:0;" />' ;
                    else
                        $ret = '<input type="checkbox" checked="checked" />' ;
                } else {
                    $topic->set ( 'controlled', 'false' ) ;
                    if ( $type == 1 )
                        $ret = '<img src="'.$this->glob->dir->images.'/icons/24x24/actions/exec.png" height="15" style="margin:0;padding:0;" />' ;
                    else
                        $ret = '<input type="checkbox" />' ;
                }

                if ( ! $this->safe_mode )
                    $this->glob->tm->update ( $topic->get_as_array () ) ;
                
                // var_dump ( $topic ) ;
            }
            echo $ret ; //. ' found' ;
        } else {
            echo $ret ; //. ' NOT found' ;
        }
        die () ;
    }
    
    function process_controlled_documents () {
        
        $this->phaser ( 'Process controlled documents' ) ;
        
        $this->controlled_documents = array () ;
        $count = 0 ;
        foreach ( $this->lut_ctrl as $topic_id => $doc ) {
            if ( $count++ < 4 ) {
                // if ( isset ( $this->all_documents[$topic_id] ) )
                    // debug_r ( $this->all_documents[$topic_id] ) ;
                // else
                    // echo " !!!!!![$topic_id]!!!!!! " ;
            }
        }
        
    }
    
    

    // fall-through functions (for external places where the DMS module is used)
    
    function process_dir_versions ( $path, $pattern ) {
        return $this->lib_files->process_dir_versions ( $path, $pattern ) ;
    }
    function get_dir_structure ( $uid, $a = '', $b = false ) {
        return $this->lib_files->get_dir_structure ( $uid, $a, $b ) ;
    }
    
    function get_versions ( $doc ) {
        return $this->lib_files->get_versions ( $doc ) ;
    }
    
    function find_last_version ( $versions ) {
        return $this->lib_files->find_last_version ( $versions ) ;
    }
    
    function get_drafts ( $doc, $version ) {
        return $this->lib_files->get_drafts ( $doc, $version ) ;
    }
    
    function find_last_draft ( $versions ) {
        return $this->lib_files->find_last_draft ( $versions ) ;
    }
    
    function next_draft ( $draft ) {
        return $this->lib_files->next_draft ( $draft ) ;
    }
    
    function draft_full_path ( $a, $b = 0, $c = 0, $d = false ) {
        return $this->lib_files->draft_full_path ( $a, $b, $c, $d ) ;
    }
    
    function draft_filename ( $a, $b = 0, $c = 0 ) {
        return $this->lib_files->draft_filename ( $a, $b, $c ) ;
    }

    function version_filename ( $a, $b = 0 ) {
        return $this->lib_files->version_filename ( $a, $b ) ;
    }

    function get_relative_lut () {
        return $this->lut_relative ;
    }
    
    function version_full_path ( $doc, $version ) {
        return $this->lib_files->version_full_path ( $doc, $version ) ;
    }
    
    function full_path ( $doc ) {
        return $this->lib_files->full_path ( $doc ) ;
    }
    
    function find_db_properties ( $type, $property ) {
        return $this->lib_db->find_db_properties ( $type, $property ) ;
    }
    function filename_pick_version ( $match ) {
        return $this->lib_files->filename_pick_version ( $match ) ;
    }

    function filename_pick_draft ( $match ) {
        return $this->lib_files->filename_pick_draft ( $match ) ;
    }
  
    
}
