<?php

    class xs_action_instance extends xs_Action_Webpage {

        // Local shortcut to the Topic Maps database
        private $db = false ;

        private $news = null ;

        public $page = array ( 'title' => 'News' ) ;

        
        function __construct () {
            parent::__construct() ;
            // create a quick short-cut to the Topic Map object
            $this->db = $this->glob->tm ;
        }
        function get_latest_news () {
            if ( ! $this->news )
                $this->news = $this->glob->data->get ( 'news-top-20' ) ;
            return $this->news ;
        }

        function ___register_functionality () {
            $this->_register_functionality ( 'News', 'news:*', 'role:editor' ) ;
            $this->_register_functionality ( 'News', 'news:new', 'role:editor' ) ;
            $this->_register_functionality ( 'News', 'news:edit', 'role:editor' ) ;
            $this->_register_functionality ( 'News', 'news:delete', 'role:editor' ) ;
        }
        

        // The news section's action starts here
        function ___action () {

            // Call security!
            $security = $this->_get_module ( 'security' ) ;
            
            // Get the id from the 'section' URI component
            $id = $this->glob->breakdown->section ;
            
            // Get some help with creating HTML
            $html = $this->glob->html_helper ;

            // If the id is ...
            switch ( $id ) {

                case '' : // Index, the news front page, and gateway to the archive

                    // Enforce the local template 'index'
                    $this->set_template ( 'index' ) ;

                    if ( $security->has_access ( 'news:new', false ) ) {
                        $this->in_the_box .= $html->func_menu_item ( 'Create new', 'news/add', 'edit_add.png' ) ;
                        $this->in_the_box_align = 'left' ;
                    }

                    switch ( $this->glob->request->method() ) {

                        // a GET ; we're just looking at the index page

                        case 'GET' :

                            $this->log ( 'READ', 'Index' ) ;

                            break ;

                        // POST ; we're posting a new news item to our database

                        case 'POST' :

                            $news_item = new xs_TopicMaps_Topic () ;

                            $fields = $this->glob->request->__get_fields () ;

                            $fields['type1'] = $this->_type->_news_item ;
                            $fields['pub_full'] = str_replace( array('&nbsp;'), ' ', isset ( $fields['pub_full'] ) ? $fields['pub_full'] : null ) ;

                            $news_item->inject ( $fields ) ;

                            if ( !isset ( $fields['label'] ) ) {

                                // Stuff didn't get through. Hmm?
                                $this->alert ( 'notice', 'Oops!', 'The news creation was unsuccessful. Contact IS to punish them for their foolish sloppy work!' ) ;

                            } else {
                                if ( trim ( $fields['label'] ) != '' ) {

                                   if ( @simplexml_load_string ( trim ( '<span>'.$fields['pub_full'] ).'</span>' ) !== false ) {

                                        $fields['who'] = $this->glob->user->id ;
                                        $w = $this->db->create ( $fields ) ;

                                        $this->glob->data->reset ( 'news-top-20' ) ;

                                        $this->alert ( 'notice', 'Good news!', 'You have created a news item successfully.' ) ;

                                        @$this->log ( 'CREATE', '('.print_r ( $w, true ).': '.$fields['label'].')' ) ;

                                   } else {
                                        $this->alert ( 'notice', 'Oops!', 'The news item had some formatting problems with it.' ) ;
                                   }
                                } else {
                                    $this->alert ( 'notice', 'Oops!', 'Your title was blank, so no can do.' ) ;
                                }
                            }
                    
                            break ;
                    }
                    
                    // Get the latest news items
                    $this->glob->stack->add ( 'xs_news', $this->get_latest_news () ) ;

                    // breadcrumbs
                    $this->glob->stack->add ( 'xs_facets', array ( 'news' => 'News' ) ) ;

                    break ;

                // We want to see the 'add news item' page, so give 'em that template

                case 'add' :
                    // Use the 'add.xml' template for adding a news item
                    $this->set_template ( 'add' ) ;
                    break ;

                default : // Any other id means some news item

                    switch ( $this->glob->request->method() ) {

                        // That was a bad news item. Let's delete it!
                        case 'DELETE' :
                            $this->db->delete ( $id ) ;
                            $this->alert ( 'notice', 'Okay', 'You successfully deleted the news item.' ) ;
                            // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {news} DELETED ('.$id.')' ) ;
                            $this->log ( "DELETE", "[$id]" ) ;
                            $this->glob->data->reset ( 'news-top-20' ) ;
                            break ;

                        // if we're POSTing to a news item, it means we're adding a comment or updating
                        case 'POST' :
                            $fields = $this->glob->request->__get_fields () ;

                            switch ( ! isset ( $fields['item'] ) ? 'comment' : 'item' ) {
                                case 'item' :
                                    unset ( $fields['item'] ) ;
                                    // echo "ITEM" ;
                                    // Get a news item of that id
                                    $item = $this->db->query ( array ( 'id' => $id ) ) ;
                                    // var_dump ( $fields ) ;

                                    foreach ( $fields as $field => $value )
                                        $item[$id][$field] = $value ;

                                    $item[$id]['who'] = $this->glob->user->id ;
                                    $item[$id]['id'] = $id ;
                                    
                                    // var_dump ( $item ) ;
                                    $w = $this->db->update ( $item[$id] ) ;
                                    $this->glob->data->reset ( 'news-top-20' ) ;
                                    
                                    // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {news} UPDATED "'.$title.'" ('.$id.')' ) ;
                                    $this->log ( "UPDATE", "[$id]" ) ;
                                    $this->alert ( 'notice', 'Okay', 'You successfully updated the news item.' ) ;

                                    break ;

                                default :
                                    // echo "COMMENT" ;
                                    /*
                                    $fields['type1'] = $this->_type->_comment ;
                                    $fields['parent'] = $id ;
                                    $fields['value'] = str_replace( array("\r\n", "\n", "\r"), '<br />', $fields['value'] ) ;
                                    $fields['who'] = $this->glob->user->id ;

                                    if ( trim ( $fields['value'] ) == '' ) {
                                        $this->alert ( 'notice', 'Oops!', 'You added a blank comment.' ) ;
                                        break ;
                                    }

                                    $w = $this->db->create ( $fields ) ;
                                    $this->alert ( 'notice', 'Goodie!', 'You successfully added a comment.' ) ;
                                    $fish = $this->db->query ( array ( 'id' => $id ), false ) ;
                                    $title = $fish[$id]['label'] ;
                                    // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {news-comment} '.$id.' title='.$title ) ;

                                    $this->log ( 'CREATE', "New comment on news-item [$id]" ) ;
                                    */
                                    break ;
                            }
                            break ;
                    }


                    // Get a news item of that id
                    $collection = new xs_TopicMaps_Collection (
                       $this->db->query ( array ( 'id' => $id ) )
                    ) ;

                    // resolve some topics in that query result
                    $collection->resolve_topics ( array (
                       'm_p_who' => array (
                           'label' => false,
                           'name' => 'username=substr($in,5)'
                       ),
                       'm_u_who' => array (
                           'label' => false,
                           'name' => 'username=substr($in,5)'
                       )
                    ) ) ;

                    // Pop it on the stack
                    $this->glob->stack->add ( 'xs_news', $collection ) ;

                    // This is an item, so use the item.xml template
                    $this->set_template ( 'item' ) ;
                    
                    $tm = $this->_get_module ( 'topic_maps' ) ;
                
                    // add tags
                    $this->glob->stack->add ( 'xs_assoc_tags', $tm->get_assoc ( array (
                        'lookup' => $id,
                        'type' => $this->_type->has_tag,
                        'filter' => $this->_type->_tag,
                    ) ) ) ;

                    // and controlled tags
                    $this->glob->stack->add ( 'xs_assoc_controlled_tags', $tm->get_assoc ( array (
                        'lookup' => $id,
                        'type' => $this->_type->has_controlled_tag,
                        'filter' => $this->_type->_controlled_tag,
                    ) ) ) ;

                    

                    // unless we're asked for an editable version, in that case ...
                    if ( $this->glob->breakdown->id == 'edit' ) {

                        $this->set_template ( 'edit' ) ;

                    } else {

                        // Get a news item of that id
                        $comments = new xs_TopicMaps_Collection (
                           $this->db->query ( array ( 'parent' => $id, 'type1' => $this->_type->_comment, 'sort_by' => 'm_c_date ASC' ), false )
                        ) ;

                        $comments->resolve_topics ( xs_TopicMaps::$resolve_author ) ;

                        $this->glob->stack->add ( 'xs_comments', $comments ) ;
                    }

                    // we've got functionality. Have we also got access?
                    if ( $security->has_access ( 'news:edit', false ) ) {
                        $this->in_the_box .= $html->func_menu_item ( 'Edit', 'news/{$item/id}/edit', 'edit.png' ) ;
                    }
                    if ( $security->has_access ( 'news:delete', false ) ) {
                        $this->in_the_box .= $html->func_menu_item ( 'Delete', array ('dia_del({$item/id},0)'), 'editdelete.png' ) ;
                    }
                    
                    // Let's create an array that will be our breadcrumb
                    $t = array ( 'news' => 'News' ) ;

                    $title = 'unknown' ;

                    // print_r ( $collection->topics[$id]->get ( 'label' ) ) ;
                    
                    if ( isset ( $collection->topics[$id] ) )
                        $title = $collection->topics[$id]->get ( 'label' ) ;

                    $t['news/'.$id] = $title ;

                    $this->glob->stack->add ( 'xs_facets', $t  ) ;

                    // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {news} "'.$title.'" ('.$id.')' ) ;
                    $this->log ( 'READ', $title ) ;

                    break ;
            }
        }
    }
