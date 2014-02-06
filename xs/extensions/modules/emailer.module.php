<?php

    class xs_module_emailer extends \xs\Events\Module {

        public $meta = array (
            'name' => 'Emailer module',
            'description' => 'Email stuff',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // Shortcut for our API
        private $resource_base = '_api/module/emailer' ;

        // emailer configuration section
        private $config = null ;
        
        function ___modules () {
            
            $this->config = $this->glob->config['emailer'] ;
            
            foreach ( $this->config as $event => $item ) 
                $this->_register_event_listener ( XS_MODULE, $event, 'send_email' ) ;
            
        }
        
        function send_email ( $topic = null ) {
            
            if ( $topic == null )
                return ;
            
            $tm = $this->_get_module ( 'topic_maps' ) ;
            
            $event = isset ( $topic['_event'] ) ? $topic['_event'] : '' ;
            // debug_r ( $topic ) ;
            // debug_r ( $this->config ) ;
            
            if ( isset ( $this->config[$event] ) ) {
                
                $config = $this->config[$event] ;
                
                $template = isset ( $config['template'] ) ? $config['template'] : '' ;
                $temp = $this->glob->tm->query ( array ( 'id' => $template ) ) ;
                $temp = reset ( $temp ) ;
                
                $to       = isset ( $config['to'] ) ? $config['to'] : '' ;
                
                $users = array () ;
                
                $user_groups = explode ( '|', $to ) ;
                
                foreach ( $user_groups as $group ) {
                    
                    $token = explode ( ':', $group ) ;
                    
                    switch ( $token[0] ) {
                        
                        case 'user' :
                            if ( isset ( $token[1] ) ) 
                                $users[$token[1]] = 'user:'.$token[1] ;
                            else
                                $users[$this->glob->user->id] = 'user:'.$this->glob->user->id ;
                            break ;
                            
                        case 'document' :
                            if ( isset ( $token[1] ) ) {
                                // $users[$token[1]] = 'user:'.$token[1] ;
                                $find = array () ;
                                
                                if ( $token[1] == 'owner' ) {
                                    $owners = $tm->get_assoc ( array (
                                        'lookup' => $topic['id'],
                                        'type' => $this->_type->has_owner,
                                        'filter' => $this->_type->_user,
                                    ) ) ;
                                    foreach ( $owners['members'] as $member => $data )
                                        $find[$member] = $member ;
                                }
                                if ( $token[1] == 'owner' ) {
                                $authors = $tm->get_assoc ( array (
                                        'lookup' => $topic['id'],
                                        'type' => $this->_type->has_author,
                                        'filter' => $this->_type->_user,
                                    ) ) ;
                                    foreach ( $authors['members'] as $member => $data )
                                        $find[$member] = $member ;
                                }
                                if ( count ( $find ) > 0 ) {
                                    $name_finder = $this->glob->tm->lookup_topics ( $find ) ;
                                    foreach ( $name_finder as $t )
                                        $users[$t['name']] = $t['name'] ;
                                }
                            }
                            break ;
                            
                        case 'role' :
                            $c = $this->glob->config['user_roles'] ;
                            if ( isset ( $c[$token[1]] ) ) {
                                $u = explode (',', $c[$token[1]] ) ;
                                foreach ( $u as $user ) {
                                    $users[$user] = 'user:'.$user ;
                                }
                            }
                            break ;
                            
                    }
                }
                
                // debug_r ( $users, 'users' ) ;
                $user_topics = $this->glob->tm->query ( array ( 'name' => $users ) ) ;
                // debug_r ( $user_topics, 'users' ) ;
                
                
                foreach ( $user_topics as $user ) {
                    if ( isset ( $temp['pub_full'] ) ) {

                        // debug_r ( $user ) ;
                        
                        $content = $this->parse_fields ( $temp['pub_full'], $topic, $user ) ;
                        
                        $username = substr ( $user['name'], 5 ) ;
                        $domain = $this->glob->config['user_management']['profile_email_domain'] ;
                        
                        $email = $username . '@' . $domain ;
                        
                        if ( isset ( $user['email'] ) && trim ( $user['email'] ) !== '' ) 
                            $email = $user['email'] ;
                        
                        $test = mail ( $email, $temp['label'], $content ) ;
                        // debug_r ( $content, 'email : '.$email ) ;
                        // debug_r ( $test, 'email sender' ) ;
                    }
                }
            }
            
        }
        
        function lookup_variable ( $variable, $topic, $user ) {
            
            $inside = false ;
            for ( $n=0; $n<strlen($variable); $n++ ) {
                if ( $variable[$n] == '[' ) {
                    $inside = true ;
                } else if ( $variable[$n] == ']' ) {
                    $inside = false ;
                } else  if ( $variable[$n] == '/' ) {
                    if ( $inside )
                        $variable[$n] = '^' ;
                }
            }
            $items = explode ( '/', $variable ) ;
            
            if ( isset ( $items[0] ) ) {
                if ( $items[0] == 'dir' ) {
                    return $this->glob->dir->$items[1] ; ;
                }
                if ( $items[0] == 'request' ) {
                    return $this->glob->request->$items[1] ; ;
                }
                if ( $items[0] == 'topic' ) {
                    // debug_r ( count ( $items ) ) ;
                    if ( count ( $items ) > 2 ) {
                        $check = $this->glob->tm->query ( array ( 'id' => $topic[$items[1]] ) ) ;
                        $first = reset ( $check ) ;
                        if ( isset ( $first['id'] ) )
                            return $first[$items[2]] ;
                    }
                    return $topic[$items[1]] ;
                }
                if ( $items[0] == 'user' ) {
                    return $user[$items[1]] ;
                    $resolve = explode ( '[', $items[1] ) ;
                    // if ( count ( $resolve ) == 1 )
                        // debug_r ( $resolve, 'resolve : == 1' ) ;
                    // return $this->glob->dir->$items[1] ; ;
                }
            }
            return null ;
        }
        
        function parse_fields ( $content, $topic, $user ) {

            $regex='/\[\$(.+?)(?:\[(.+)\])?\]/';
            $matches = array();
            preg_match_all ( $regex, $content, $matches ) ;

            // debug_r($matches);

        if ( isset ( $matches[1] ) && is_array ( $matches[1] ) ) {
            $idx = array () ;
            foreach ( $matches[1] as $v )
                $idx[$v] = $v ;
            // debug_r ( $idx ) ;
            foreach ( $idx as $variable ) {
                $check = $this->lookup_variable ( $variable, $topic, $user ) ;
                if ( $check !== null )
                    $content = str_replace ( '[$'.$variable.']', $check, $content ) ;
                else
                    $content = str_replace ( '[$'.$variable.']', "<b style='color:#bdd;'>[{$variable} not found]</b>", $content ) ;
            }
        }

        return $content ;
    }

    }
