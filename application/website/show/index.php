<?php

class xs_action_instance extends \xs\Action\Webpage {

    public $page = array(
        'title' => "Show document",
        'description' => 'Page that shows a document from the document store',
        'keywords' => 'show, document'
    );

    // function fill_levels
    function _action() {

        $b = $this->glob->breakdown;

        $uid     = trim ( $b->section ) ;
        $id      = null ;
        $comment = trim ( $b->id ) ;











        // Load and prepare our document data
        $keywords = new Keywords ( $this->glob ) ;

        $res = $keywords->by_uid ( $uid ) ;



        $document = null ;

        // Is this document in the Topic Map?

        $documents = $this->glob->tm->query ( array ( 'name' => 'document:' . $uid ) ) ;

        if ( count ( $documents ) > 0 ) {
// echo 'db';
            // Yes, in the Topic Map
            reset($documents);
            $key = key($documents);

            if ( $key !== null ) {
                $id = $key ;
                $document = $documents[$id] ;
            }

        } else {
// echo 'file';
            // No, so let's create one there

            $keywordstmp = '' ;
            foreach ( $res['wordimportant'] as $word => $count )
                $keywordstmp .= "$word ($count) " ;

            // Create a new topic for this user
            $id = $this->glob->tm->create ( array (
                'label' => end ( $res['facets'] ),
                'name' => 'document:' . $uid,
                'type1' => __DOC,
                'original_path' => $res['original'],
                'keywords' => $keywordstmp,
                'serialized' => serialize ( $res )
            ) ) ;

            // print_r ( $record ) ;
            // print_r ( $res ) ;

            // $fields['type1'] = __NEWS ;
            // $fields['pub_full'] = str_replace( array("\r\n", "\n", "\r"), '<br />', $fields['pub_full'] ) ;

            // $news_item->inject ( array ( ) ) ;


        }


        // print_r ( $documents ) ;
        // print_r ( $document ) ;
        // echo "[$id]" ;
        
        $facets = array() ;
        
        if (isset ( $keywords->data[$uid] ) && isset ( $keywords->data[$uid]['facets'] ) ) {
            $facets = $keywords->data[$uid]['facets'] ;
        }

        $struct = array ( 'browse' => 'Browse' ) ;
        $count = 0 ;

        foreach ( $facets as $facet ) {
            $path = '' ;
            $smallcount = 0 ;
            foreach ( $facets as $f ) {
                if ( $smallcount <= $count ) {
                    if ( $path != '') $path .= '/' ;
                    $path .= $f ;
                }
                $smallcount++ ;
            }
            $struct['browse/'.urlsafe($path)] = $facet ;
            $count++ ;
        }
        // echo "<pre>" ; print_r ( $struct ) ; echo "</pre>" ; die() ;

        $this->glob->stack->add ( 'xs_facets',
            $struct
        ) ;


        $this->glob->stack->add ( 'xs_document', array (
            'uid' =>  $res['uid'],
            'html' => $res['html'],   'html_web' => $res['html_web'],
            'pdf'  => $res['pdf'],    'pdf_web'  => $res['pdf_web'],
            'txt'  => $res['txt'],    'txt_web'  => $res['txt_web'],
            'words' => $res['wordcount'],
            'pruned' => $res['wordprune'],
        ) ) ;


        // $keywords->data = $keywords->by_uid


       // Find all important words in all documents
        $tmp = $res['wordimportant'] ;
        if ( !is_array ( $tmp ) )
            $tmp = array () ;

        $this->log ( 'READ', implode ( '/', $facets ) . '.pdf' ) ;
        // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {show} ' .  implode ( '/', $facets ) . '.pdf'       ) ;




        // Sort them, retain keys
        arsort ( $tmp ) ;

        $tmp = array_slice($tmp, 0, 20);

        // print_r ( $tmp ) ;
        $this->glob->log->add ( "/show : keyword data" ) ;

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



        // is this an incoming comment?

        $fields = $this->glob->request->__get_fields () ;

        switch ( isset ( $fields['_item'] ) ? $fields['_item'] : null ) {

            case 'comment' :

                // echo "comment" ;

                $fields['type1'] = $this->_type->_comment ;
                $fields['parent'] = $id ;
                $fields['value'] = str_replace( array("\r\n", "\n", "\r"), '<br />', $fields['value'] ) ;

                if ( trim ( $fields['value'] ) == '' ) {
                    $this->alert ( 'notice', 'Oops!', 'You added a blank comment.' ) ;
                    break ;
                }

                $fields['who'] = $this->glob->user->id ;
                $w = $this->glob->tm->create ( $fields ) ;
                $this->alert ( 'notice', 'Goodie!', 'You successfully added a comment.' ) ;
                $fish =$this->glob->tm->query ( array ( 'id' => $id ), false ) ;
                $title = $fish[$id]['label'] ;
                // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {news-comment} '.$id.' title='.$title ) ;

                $this->log ( 'CREATE', "New comment on document [$id]" ) ;

                break ;
        }











        // comments?
        $comments = new \xs\TopicMaps\Collection (
           $this->glob->tm->query ( array ( 'parent' => $id, 'type1' => $this->_type->_comment ), false )
        ) ;

        $comments->resolve_topics ( xs_TopicMaps::$resolve_author ) ;

        $this->glob->stack->add ( 'xs_comments', $comments ) ;



        
        $this->glob->log->add ( "/show : keyword cloud" ) ;
        
    }
}
