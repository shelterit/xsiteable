<?php

    class xs_action_instance extends \xs\Action\Webpage {

        public $page = array (
            'title' => 'Generic content',
            'template' => '/layouts/col-1-generic-content'
        ) ;

        private $resource_id = null ;
        private $found_sub = null ;
        
        private $running = false ;

        function _http_action () {
            $this->running = true ;
            // echo "[generic_action] " ;
        }
        
        function ___output () {
            
            if ( ! $this->running )
                return ;
            
            // Call security!
            $security = $this->_get_module ( 'security' ) ;
            
            // Get some help with creating HTML
            $html = $this->glob->html_helper ;
            
            if ( $security->has_access ( 'page:edit' ) )
                $this->in_the_box .= $html->func_menu_item ( 'Edit', '{$request/q}?_edit=true', 'edit.png' ) ;
            
            if ( $security->has_access ( 'page:new' ) )
                $this->in_the_box .= $html->func_menu_item ( 'New page', array('create_new_page()'), 'edit_add.png' ) ;
        }
        
        function ___action () {
            
            if ( isset ( $this->_topic['id'] ) ) {
                
                $id = $this->_topic['id'] ;
                
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

                $comments = $this->_get_module ( 'comments' ) ;
                
                $this->glob->stack->add ( 'xs_comments', $comments->get_for_topic ( $id ) ) ;

                $this->glob->stack->add ( 'xs_document', $this->_topic ) ;
            
                // debug_r ( $id ) ;
                
                /*
                $t = new \xs\TopicMaps\Assoc ( 
                    $this->glob->tm->query_assoc ( array ( 
                        'type' => $this->_type->has_tag, 
                        'member_id' => $id 
                ) ) ) ;
                $t->inject ( array ( 'type' => $this->_type->has_tag ) ) ;
                $t->member_resolve () ;
                $this->glob->stack->add ( 'xs_assoc_tags', $t->__get_array () ) ;
                
                $t = new \xs\TopicMaps\Assoc ( 
                    $this->glob->tm->query_assoc ( array ( 
                        'type' => $this->_type->has_controlled_tag, 
                        'member_id' => $id 
                ) ) ) ;
                $t->inject ( array ( 'type' => $this->_type->has_controlled_tag ) ) ;
                $t->member_resolve () ;
                $this->glob->stack->add ( 'xs_assoc_controlled_tags', $t->__get_array () ) ;
                
                */
                
            }
        }
        
        function ___gui_section0 () {
            
            if ( ! $this->running )
                return ;
            
            $ret = '' ;
            
            if ( ! isset ( $this->_topic['name'] ) )
                return ;
            
            $this_topic = $this->_topic ;
            $data = $this->glob->tm->query ( array (
                'select' => 'id,name,label',
                'type' => $this->_type->_page,
                'return' => 'topics',
                'name:like' => $this_topic['name'] . '%'
            ) ) ;
            $this_count = substr_count ( $this_topic['name'], '|' ) ;
            // debug ( $this->_type->_page ) ;
            
            // debug_r ( $data ) ;
            
            $res = array () ;
            foreach ( $data as $idx => $topic ) {
                // echo "[{$topic['id']} / {$this_topic['id']}]   " ;
                if ( $topic['id'] != $this_topic['id'] ) {
                    $count = substr_count ( $topic['name'], '|' ) ;
                    // echo "[$this_count / $count]" ;
                    // print_r ( $topic ) ;
                    $href = $this->glob->dir->home.'/'.str_replace ( '|', '/', substr ( $topic['name'], 22 ) ) ;
                    if ( $count > $this_count && $count < $this_count + 2 )
                        $res[$href] = htmlentities($topic['label']) ;
                }
            }
            // debug_r ( $res ) ;
            
            if ( count ( $res ) > 0 )
                $this->found_sub = $res ;
            
            if ( $this->found_sub != null ) {
                $ret .= "<div class='sub-menu'>" ;
                $ret .= "<h3>Found subpages</h3> " ;
                $ret .= $this->glob->html_helper->create_linked_list ( $this->resource_id, $this->found_sub, null, 'menu-li' ) ;
                $ret .= "</div>" ;
            }
            
            
            return $ret ;  
        }
        function ___gui_section1 () {
            
            if ( ! $this->running )
                return ;
            
            $this->set_template ( '/layouts/col-1-generic-content' ) ;
            if ( $this->glob->request->_edit == 'true' )
                $this->set_template ( '/layouts/col-1-edit' ) ;
            
            $content = 'No content found.' ;
            
            if ( isset ( $this->_topic['pub_full'] ) )
                $content = $this->_topic['pub_full'] ;
            
            if ( $this->found_sub != null )
                $content = str_replace ( '{list:sub-pages}', 
                    $this->glob->html_helper->create_linked_list ( $this->resource_id, $this->found_sub, null, '' ), 
                    $content 
                ) ;
            
            return $content ;
        }
        
        function POST ( $arg = null ) {
            // echo "POST!" ;
            $fields = $this->glob->request->__get_fields () ;
            // print_r ( $fields ) ;
            // die () ;
        }
        
        // Just temporary here, probably is better off in the content.module
        function ___gui_js () {
            return ; // ' <script src="{$dir/static}/tiny_mce/jquery.tinymce.js" ></script>' ;
        }
        /*
        function ___gui_section_page_functionality () {
            
            $security = $this->_get_module ( 'security' ) ;

            $functions = 0 ;
            
            $ret = "<div style='float:right;padding:5px 10px;margin:0 0 15px 15px;border:solid 3px #ddc;background-color:#f92;'>" ;
            
            if ( $security->has_access ( 'page:edit' ) ) {
                $ret .= '<a style="margin:5px 10px;padding:0;float:right;" href="{$dir/home}/{$request/q}?_edit=true"><img src="{$dir/images}/icons/24x24/actions/edit.png" alt="edit" style="margin:0;padding:0;" /><br/>edit</a>' ;
                $functions++ ;
            }
            if ( $security->has_access ( 'page:new' ) ) {
                $ret .= '<a style="margin:5px 10px;padding:0;float:right;" href="#" onclick="create_new_page();"><img src="{$dir/images}/icons/24x24/actions/edit_add.png" alt="create" style="margin:0;padding:0;" /><br/>new<br/>page</a>' ;
                $functions++ ;
            }
            $ret .= "</div>" ;
            
            if ( $functions == 0 )
                return '' ;
            
            return $ret ;
        } */
        
        function ___register_functionality () {

            // $sec = $this->_get_module ( 'security' ) ;
            
            if ( $this->glob->request->_edit == 'true' ) {
                
            // echo "!!!" ;
                $this->set_template ( '/layouts/col-1-edit' ) ;
                $this->_register_functionality ( 'Save', 'form:submit', 'role:editor' ) ;
                $this->_register_functionality ( 'Cancel', 'page:previous' ) ;
                
            } else {
                
            // echo "***" ;
                $this->set_template ( '/layouts/col-1-generic-content' ) ;
                $this->_register_functionality ( 'Edit', 'page:edit', 'role:editor' ) ;
                // $this->_register_functionality ( 'Move', 'page:move', 'role:admin' ) ;
                // $this->_register_functionality ( 'Delete', 'page:delete', 'role:admin' ) ;
                $this->_register_functionality ( 'New page', 'page:new', 'role:editor' ) ;
            }
            
        }

    }
