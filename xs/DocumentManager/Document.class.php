<?php

namespace xs\DocumentManager ;

class Document extends \xs\Store\Properties {

    public $uid = '' ;
    
    public $show = true ;
    
    public $label = '' ;
    public $filename = '' ;
    public $extension = '' ;
    public $timestamp = 0 ;
    public $spidered_timestamp = 0 ;
    public $timestamp_db_property = 0 ;
    
    public $history = null ;
    
    
    public $controlled = 'false' ;
    public $deleted = 'false' ;
    public $harvest = 'false' ;
    
    // public $absolute_path = '' ;
    // public $absolute_path_alt = '' ;
    // public $absolute_path_md5 = '' ;
    
    public $relative_path = '' ;
    public $relative_path_md5 = '' ;
    
    public $home_directory = '' ;
    
    public $uri = '' ;
    public $type = 0 ;
    
    public $db_name = '' ;
    public $db_id = '' ;
    
    public $lut = null ;
    
    public $create_preview = false ;
    
    private $fstat = array () ;
    
    private $topic = null ;
    
    public $versions = array () ;
    
    public $source = null ;
    
    
    
    
    // public $source_alt = null ;
    // public $final_path = '' ;
    // public $final_file = '' ;
    // public $final_file_alt = '' ;
    // public $final_file_html = '' ;
    // public $final_file_txt = '' ;
    
    
    
    // path to places
    public $path_original = '' ;
    public $path_dest = '' ;
    
    // files
    public $file_original = '' ;
    public $file_original_md5 = '' ;
    public $file_original_alt = '' ;
    public $file_dest = '' ;
    public $file_dest_html = '' ;
    public $file_dest_txt = '' ;
    
    // timestamps
    public $file_timestamp_original = 0 ;
    public $file_timestamp_original_alt = 0 ;
    public $file_timestamp_dest = 0 ;
    public $file_timestamp_dest_html = 0 ;
    public $file_timestamp_dest_txt = 0 ;
    
    // existence checks
    public $file_exist_original = null ;
    public $file_exist_original_alt = null ;
    public $file_exist_dest = null ;
    public $file_exist_dest_html = null ;
    public $file_exist_dest_txt = null ;
    
    public $original_copy_at = null ;
    
    public $action = array (
        'touch' => array (
            'original' => false,
            'dest' => false,
            'dest_html' => false
        ),
        'copy' => array (
            'source_to_dest' => false,
            'source_alt_to_dest' => false,
            'upload_to_dest' => false,
            'dest_copy_version' => false,
            'dest_copy_draft' => false,
        ),
        'create' => array (
            'dest_to_html' => false,
            'dest_to_html_quality' => false,
            'html_to_txt' => false,
        ),
        'process' => array (
            'html' => false,
            'txt' => false,
            'index' => false,
        )
    ) ;


    function __construct ( $path = '', $fstat = null, $topic = null ) {
        parent::__construct () ;
        $this->init ( $path, $fstat, $topic ) ;
    }
    
    function load_history () {
        if ( $this->history == null )
            $this->history = new \xs\DocumentManager\History ( $this->path_dest . '/' . $this->uid . '.history' ) ;
    }
    
    function save_history () {
        $this->history->save () ;
    }
    
    function attach_topic ( $topic ) {
        $this->topic = $topic ;
        if ( isset ( $topic['name'] ) ) {
            $p = explode ( 'document:', $topic['name'] ) ;
            if ( isset ( $p[1] ) )
                $this->uid = $p[1] ;
        }
            // $this->uid = $topic['uid'] ;
        if ( isset ( $topic['id'] ) ) 
            $this->db_id = $topic['id'] ;
    }
    
    function get_topic () {
        return $this->topic ;
    }
    
    function init ( $path = '', $fstat = null, $topic = null ) {
        
        $this->topic = $topic ;
        
        $this->file_original = $path ;
        $this->file_original_md5 = $this->uid = md5 ( $this->file_original ) ;

        // debug ( $topic ) ;
        
        if ( isset ( $topic['name'] ) ) {
            $p = explode ( 'document:', $topic['name'] ) ;
            if ( isset ( $p[1] ) )
                $this->file_original_md5 = $this->uid = $p[1] ;
        } else {
            $topic['name'] = 'document:' . $this->uid ;
        }
        
        $this->relative_path = $this->relative_path ( $path ) ;
        $this->relative_path_md5 = md5 ( $this->relative_path ) ;
        
        if ( isset ( $topic['relative_path'] ) ) {
            $this->relative_path = $topic['relative_path'] ;
        }
        
        if ( isset ( $topic['controlled'] ) ) {
            $this->controlled = $topic['controlled'] ;
        }
        
        if ( isset ( $topic['source'] ) ) {
            $this->source = $topic['source'] ;
        }
        
        $this->home_directory = $this->get_dir_structure ( $this->uid ) ;
        
        $info = pathinfo ( $path ) ;
        $this->filename = $info['filename'] ;
        $this->extension = isset ( $info['extension'] ) ? $info['extension'] : '' ;
        if ( isset ( $topic['extension'] ) ) {
            $this->extension = $topic['extension'] ;
        }
        
        $this->label = $this->create_label () ;

        if ( isset ( $topic['label'] ) ) $this->label = $topic['label'] ;
                
        if ( isset ( $topic['id'] ) ) $this->db_id = $topic['id'] ;
        
        
        if ( $fstat !== null ) {
            
            if ( isset ( $fstat['mtime'] ) )
                $this->timestamp = $this->spidered_timestamp = $fstat['mtime'] ;

            $this->fstat = $fstat ;
        }
    }
    
    // function inject_timestamp ( $timestamp ) { $this->spidered_timestamp = $timestamp ; }
    
    function inject ( $arr ) {
        foreach ( $arr as $idx => $value )
            if ( isset ( $this->$idx ) )
                $this->$idx = $value ;
    }
    
    function update_relative () {
        $this->relative_path = $this->relative_path ( $this->absolute_path, $this->source ) ;
    }
    
    function update_label () {
        $this->label = $this->create_label ( null ) ;
    }
    
    function create_label ( $str = null ) {
        if ( $str === null )
            $str = $this->relative_path ;
        $e = explode ( '/', $str ) ;
        return trim ( end ( $e ) ) ;
    }
    
    function relative_path ( $file, $source = null ) {
        if ( $source === null )
            $source = $this->glob->config['dms']['source_folder'] ;
        $f = trim ( substr ( $file, strlen ( $source ) + 1 ) ) ;
        $f = trim ( substr ( $f, 0, -4) ) ;
        return $f ;
    }
    function relative_path_old ( $file ) {
        $f = trim ( substr ( $file, strlen ( $this->glob->config['dms']['source_folder'] ) + 1 ) ) ;
        $f = trim ( substr ( $f, 0, -4) ) ;
        return $f ;
    }

    function get_dir_structure ( $filename, $base_folder = '' ) {
        
        $size = strlen ( $filename ) ;
        $dest = '' ;
        if ( $size > 3 ) {
            $q = $w = '/' ;
            $q .= $filename[0] ;
            $q .= $filename[1] ;
            $w .= $filename[2] ;
            $w .= $filename[3] ;
            $dest = $q . $w ;
        }
        return $base_folder . $dest ;
    }
    
}
