<?php

class xs_DocumentManager_Controlled_Document extends \xs\Store\Properties {

    // the UID of the document
    public $uid = null ;
    
    // name-matching look-up table
    public $name_lut = null ;
    
    // the documents' topic representation
    public $topics = null ;
    
    // structure $document->versions->drafts
    private $structure = array () ;
    
    function __construct ( $topic = null ) {
        
        if ( $topic === null )
            return ;
        
        $this->add_topic ( $topic ) ;
    }
    
    function add_topic ( $topic ) {
        if ( isset ( $topic['id'] ) )
            $this->topics[$topic['id']] = $topic ;
    }
    
    function get_topic ( $id = null ) {
        
        if ( $id === null )
            return reset ( $this->topics ) ;
        
        if ( isset ( $this->topics[$id] ) )
            return $this->topics ;
        
        return null ;
    }
    
    
}
