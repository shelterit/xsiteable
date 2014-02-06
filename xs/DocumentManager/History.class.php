<?php

namespace xs\DocumentManager ;

class History extends \xs\Store\Properties {

    private $path = null ;
    private $timestamp = null ;
    public $struct = array () ;
    
    function __construct ( $path = null ) {
        parent::__construct () ;
        $this->timestamp = microtime(true) ;
        $this->path = $path ;
        $this->load () ;
    }
    
    function get_versions () {
        $ret = array () ;
        foreach ( $this->struct as $idx => $item )
            $ret[$idx] = $idx ;
    }
    
    function get_drafts ( $version ) {
        if ( isset ( $this->struct[$version]['drafts'] ) )
            return $this->struct[$version]['drafts'] ;
        return null ;
    }
    
    function load () {
        if ( $this->path !== null ) {
            if ( file_exists ( $this->path ) ) {
                $this->struct = @unserialize ( file_get_contents ( $this->path ) ) ;
            }
        }
    }
    
    function is_sealed ( $version ) {
        return isset ( $this->struct[$version]['sealed'] ) ? true : false ;
    }
    
    function save () {
        file_put_contents ( $this->path, @serialize ( $this->struct ) ) ;
        // debug_r ( $this->struct, 'saved history struct' ) ;
    }
    
    function add_to_version ( $version, $func, $who, $when = null ) {
        if ( $when == null )
            $when = $this->timestamp ;
        if ( ! is_array ( $who ) )
            $who = array ( $who ) ;
        foreach ( $who as $w )
            $this->struct[$version][$func][$w] = $when ;
    }
    
    function get_from_draft ( $version, $draft, $func ) {
        if ( ! isset ( $this->struct[$version]['drafts'][$draft][$func] ) )
            return array () ;
        return $this->struct[$version]['drafts'][$draft][$func] ;
    }
    
    function copy_to_version_from_draft ( $newversion, $version, $draft, $func ) {
        if ( ! isset ( $this->struct[$version]['drafts'][$draft][$func] ) )
            return null ;
        foreach ( $this->struct[$version]['drafts'][$draft][$func] as $who => $when )
            $this->add_to_version ( $newversion, $func, $who, $when ) ;
    }
    
    function add_to_draft ( $version, $draft, $func, $who, $when = null ) {
        if ( $when == null )
            $when = $this->timestamp ;
        if ( ! is_array ( $who ) )
            $who = array ( $who ) ;
        foreach ( $who as $w )
            $this->struct[$version]['drafts'][$draft][$func][$w] = $when ;
    }
}
