<?php

    class xs_module_comments extends \xs\Events\Module {

        public $meta = array (
            'name' => 'Comments module',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // if types are to be defined and used
        protected $___register_types = array ( 

            // basic standard tag types
            '_comment' => 'A tag',
            
        ) ;
        
        protected $resource = null ;
        
        function ___modules () {
            
            // we support a RESTful API at this URI
            $this->_register_resource ( XS_MODULE, '_api/module/comments' ) ;
            
        }
        
        function _http_action ( $in = null ) {
            $method = $this->glob->request->get_method () ;
            $this->$method () ;
        }
        
        function get_for_topic ( $id ) {
            
            // Get a forum item of that id
            $comments = new \xs\TopicMaps\Collection (
                $this->glob->tm->query ( array ( 
                    'parent' => $id, 
                    'type1' => $this->_type->_comment 
                ), false )
            ) ;

            $comments->resolve_topics ( \xs\TopicMaps\Engine::$resolve_author ) ;
            
            return $comments ;
        }
        
        function redirect ( $redirect = null ) {
            
            if ( $redirect === null )
                $redirect = urldecode ( $this->glob->request->__fetch ( '_redirect', '' ) ) ;
            
            if ( $redirect != '' ) {
                
                // if there are unseen alerts, cache them in session so they 
                // can get spat out on the other side of this redirect
                if ( count ( $this->glob->alerts ) > 0 ) {
                    $_SESSION['xs_alerts'] = (array) $this->glob->alerts ;
                }
                
                if ( $redirect == XS_ROOT_ID ) $redirect = '' ;
                
                $domain = $_SERVER['SERVER_NAME'] . $this->glob->dir->home ;

                $l = $redirect ;

                if ( sizeof ( $l ) > 0 && $l[0] !== '/' && $l[0] !== '\\' ) {
                    $domain .= '/' ;
                }

                if ( ! strstr ( $domain, 'http:' ) and ! strstr ( $domain, 'https:' ))
                    $domain = 'http://' . $domain ;

                if ( strstr ( $redirect, 'http:' ) or strstr ( $redirect, 'https:' ))
                    $domain = '' ;

                $redir = "Location: {$domain}{$redirect}" ;
                
                // debug_r ( $redir ) ; die () ;
                // if ( ! $this->debug ) { print_r ( $redir ) ; die () ; }
                // die() ;
                header ( $redir ) ;
                die () ;
            }
        }
        
        function GET () {
            
            // get comments of a topic
            
            $topic = $this->glob->request->topic ;
            
            if ( trim ( $topic ) != '' && is_numeric ( $topic ) ) {
                $comments = $this->get_for_topic ( $topic ) ;
            }
            
        }
        
        function POST () {
            
            // make a comment on a topic
            
            $topic   = $this->glob->request->topic ;
            $user_id = $this->glob->request->user_id ;
            $comment = $this->glob->request->comment ;
            
            if ( (int) $topic < 1 ) {
                $this->alert ( 'notice', 'Oops!', 'Don\'t know what topic this comment was meant for?' ) ;
                $this->redirect ( $this->glob->request->_redirect ) ;
                die() ;
            }
            
            if ( (int) $user_id < 1 ) {
                $this->alert ( 'notice', 'Oops!', 'Don\'t know what user made this comment?' ) ;
                $this->redirect ( $this->glob->request->_redirect ) ;
                die() ;
            }
            
            $fields = array (
                'type1' => $this->_type->_comment,
                'parent' => $topic,
                'who' => $user_id,
                'value' => trim ( str_replace( array("\r\n", "\n", "\r"), '<br />', $comment ) ),
            ) ;
            
            // missing comment content?
            if ( $fields['value'] == '' ) {
                $this->alert ( 'notice', 'Oops!', 'You tried to add a blank comment.' ) ;
                $this->redirect ( $this->glob->request->_redirect ) ;
                die() ;
            }

            // debug_r ( $fields ) ; die () ;
            
            $w = $this->glob->tm->create ( $fields ) ;
            
            $this->alert ( 'notice', 'Goodie!', 'You successfully added a comment.' ) ;
            $this->redirect ( $this->glob->request->_redirect . '?_comment=true#comment-' . $w ) ;
            // $this->log ( 'CREATE', "New comment on document [$topic]" ) ;

        }
    }
