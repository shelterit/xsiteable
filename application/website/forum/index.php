<?php

    class xs_action_instance extends xs_Action_Webpage {

        // Local shortcut to the Topic Maps database
        private $db = false ;

        private $forum = null ;

        public $page = array ( 'title' => 'Forum' ) ;

        function __construct () {
            parent::__construct() ;
            // create a quick short-cut to the Topic Map object
            $this->db = $this->glob->tm ;
        }

        function get_latest_forum ( $items = 15 ) {
            if ( !$this->forum )
                $this->forum = $this->glob->data->get ( 'forum-top-20' ) ;
            return $this->forum ;
        }

        function ___register_functionality () {
            $this->_register_functionality ( 'Forum', 'forum:*', 'role:editor' ) ;
            $this->_register_functionality ( 'Forum', 'forum:new', 'role:editor' ) ;
            $this->_register_functionality ( 'Forum', 'forum:edit', 'role:editor' ) ;
            $this->_register_functionality ( 'Forum', 'forum:delete', 'role:editor' ) ;
        }
        
        // The forum section's action starts here

        function ___action () {

            // Call security!
            $security = $this->_get_module ( 'security' ) ;
            
            // Get the id from the 'section' URI component
            $id = $this->glob->breakdown->section ;

            // Get some help with creating HTML
            $html = $this->glob->html_helper ;
            
            // If the id is ...
            switch ( $id ) {

                case '' : // Index, the forum front page, and gateway to the archive

                    // Enforce the local template 'index'
                    $this->set_template ( 'index' ) ;

                    if ( $security->has_access ( 'forum:new', false ) ) {
                        $this->in_the_box .= $html->func_menu_item ( 'Create new', 'forum/add', 'edit_add.png' ) ;
                        $this->in_the_box_align = 'left' ;
                    }
                    
                    switch ( $this->glob->request->method() ) {

                        // a GET ; we're just looking at the index page

                        case 'GET' :

                            $this->log ( 'READ', 'Index' ) ;

                            break ;

                        // POST ; we're posting a new forum item to our database

                        case 'POST' :

                            $forum_item = new xs_TopicMaps_Topic () ;

                            $fields = $this->glob->request->__get_fields () ;

                            $fields['type1'] = $this->_type->_forum_item ;
                            $fields['pub_full'] = str_replace( array('&nbsp;'), ' ', $fields['pub_full'] ) ;

                            $forum_item->inject ( $fields ) ;

                            if ( !isset ( $fields['label'] ) ) {

                                // Stuff didn't get through. Hmm?
                                $this->alert ( 'notice', 'Oops!', 'The forum creation was unsuccessful. Contact IS to punish them for their foolish sloppy work!' ) ;

                            } else {
                                if ( trim ( $fields['label'] ) != '' ) {

                                   if ( @simplexml_load_string ( $fields['pub_full'] ) ) {

                                        $fields['who'] = $this->glob->user->id ;
                                        $w = $this->db->create ( $fields ) ;

                                        $this->glob->data->reset ( 'forum-top-20' ) ;

                                        $this->alert ( 'notice', 'Good news!', 'You have created a forum item successfully.' ) ;

                                        @$this->log ( 'CREATE', '('.print_r ( $w, true ).': '.$fields['label'].')' ) ;

                                   } else {
                                        $this->alert ( 'notice', 'Oops!', 'The forum item had some formatting problems with it.' ) ;
                                   }
                                } else {
                                    $this->alert ( 'notice', 'Oops!', 'Your title was blank, so no can do.' ) ;
                                }
                            }
                    
                            break ;
                    }

                    // Get the latest forum items
                    $this->glob->stack->add ( 'xs_forum', $this->get_latest_forum () ) ;

                    // breadcrumbs
                    $this->glob->stack->add ( 'xs_facets', array ( 'forum' => 'Forum' ) ) ;

                    break ;

                // We want to see the 'add forum item' page, so give 'em that template

                case 'add' :
                    // Use the 'add.xml' template for adding a forum item
                    $this->set_template ( 'add' ) ;
                    break ;

                default : // Any other id means some forum item

                    switch ( $this->glob->request->method() ) {

                        // That was a bad forum item. Let's delete it!
                        case 'DELETE' :
                            $this->db->delete ( $id ) ;
                            $this->alert ( 'notice', 'Okay', 'You successfully deleted the forum item.' ) ;
                            // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {forum} DELETED ('.$id.')' ) ;
                            $this->log ( "DELETE", "[$id]" ) ;
                            $this->glob->data->reset ( 'forum-top-20' ) ;
                            break ;

                        // if we're POSTing to a forum item, it means we're adding a comment or updating
                        case 'POST' :
                            $fields = $this->glob->request->__get_fields () ;

                            switch ( ! isset ( $fields['item'] ) ? 'comment' : 'item' ) {
                                case 'item' :
                                    unset ( $fields['item'] ) ;
                                    // echo "ITEM" ;
                                    // Get a forum item of that id
                                    $item = $this->db->query ( array ( 'id' => $id ) ) ;
                                    // var_dump ( $fields ) ;

                                    foreach ( $fields as $field => $value )
                                        $item[$id][$field] = $value ;

                                    $item[$id]['who'] = $this->glob->user->id ;
                                    $item[$id]['id'] = $id ;
                                    
                                    // var_dump ( $item ) ;
                                    $w = $this->db->update ( $item[$id] ) ;
                                    $this->glob->data->reset ( 'forum-top-20' ) ;
                                    
                                    // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {forum} UPDATED "'.$title.'" ('.$id.')' ) ;
                                    $this->log ( "UPDATE", "[$id]" ) ;
                                    $this->alert ( 'notice', 'Okay', 'You successfully updated the forum item.' ) ;

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
                                    // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {forum-comment} '.$id.' title='.$title ) ;

                                    $this->log ( 'CREATE', "New comment on forum-item [$id]" ) ;
                                    */
                                    break ;
                            }
                            break ;
                    }


                    // Get a forum item of that id
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
                    $this->glob->stack->add ( 'xs_forum', $collection ) ;

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
                    if ( $this->glob->breakdown->id == 'edit' )

                       $this->set_template ( 'edit' ) ;

                    else {

                        $comments = $this->_get_module ( 'comments' ) ;
                        $this->glob->stack->add ( 'xs_comments', $comments->get_for_topic ( $id ) ) ;
                    }

                    // we've got functionality. Have we also got access?
                    if ( $security->has_access ( 'forum:edit', false ) ) {
                        $this->in_the_box .= $html->func_menu_item ( 'Edit', 'forum/{$item/id}/edit', 'edit.png' ) ;
                    }
                    if ( $security->has_access ( 'forum:delete', false ) ) {
                        $this->in_the_box .= $html->func_menu_item ( 'Delete', array ('dia_del({$item/id},0)'), 'editdelete.png' ) ;
                    }
                    
                    // Let's create an array that will be our breadcrumb
                    $t = array ( 'forum' => 'Forum' ) ;

                    $title = 'unknown' ;

                    // print_r ( $collection->topics[$id]->get ( 'label' ) ) ;
                    
                    if ( isset ( $collection->topics[$id] ) )
                        $title = $collection->topics[$id]->get ( 'label' ) ;

                    $t['forum/'.$id] = $title ;

                    $this->glob->stack->add ( 'xs_facets', $t  ) ;

                    // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {forum} "'.$title.'" ('.$id.')' ) ;
                    $this->log ( 'READ', $title ) ;

                    break ;
            }
        }
    }
