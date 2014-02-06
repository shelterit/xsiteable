<?php

    // A service that drives all manners of search and retreival

    class xs_module_generic_content extends \xs\Events\Module {

        public $meta = array (
            'name' => 'Generic Content module',
            'description' => 'A generic content controller',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        private $debug = false ;
        
        // html to text converter class
        private $html2text = null ;
        
        // tidy things up!
        private $tidy = null ;
        private $tidy_config = array (
            'indent'           => true,
            'output-xhtml'     => true,
            'show-body-only'   => true, 
            'wrap'             => 0,
            'numeric-entities' => false,            
        ) ;
        

        function ___modules () {

            // Define the main events we control and can trigger
            $this->_register_event ( XS_MODULE, 'on_content_create' ) ;
            $this->_register_event ( XS_MODULE, 'on_content_read' ) ;
            $this->_register_event ( XS_MODULE, 'on_content_update' ) ;
            $this->_register_event ( XS_MODULE, 'on_content_delete' ) ;
            
            // Gain control over a specific resource
            $this->_register_resource ( XS_MODULE, '_api/resources/content' ) ;

            $this->tidy = new \tidy;
        }
        
        function _http_action () {
            
            // Overwrite this one for all your action, otherwise it will try
            // to use the method functions instead

            $this->glob->log->add ( 'xs_Action : ACTION : Start' ) ;

            // echo "(".$this->_meta->class.") " ;

            // Get the HTTP method
            $m = $this->glob->request->method() ;

            // Create an indexed array of all this class' methods
            // $mm = array_keyify ( $this->_meta->methods ) ;

            // If any of the match the HTTP method, just call it, so for example
            // if the HTTP method is 'DELETE', and it exists, we call $this->DELETE()

            $this->$m() ;
            
            $this->glob->log->add ( 'xs_Action : ACTION : End' ) ;
        }
        
        function GET () {
            echo "GET!" ;
        }

        function callback___on_indexer_create ( $arr = array () ) {

        }
        
        // before we output, make sure no one is requesting a redirect
        
        function ___output_pre () {
            
            $redirect = urldecode ( $this->glob->request->__fetch ( '_redirect', 'zzz' ) ) ;
            
            if ( $redirect != 'zzz' ) {

                if ( $redirect == XS_ROOT_ID ) $redirect = '' ;
                
                $domain = $_SERVER['SERVER_NAME'] . $this->glob->dir->home ;

                $l = $redirect ;

                if ( $l != '' && $l[0] !== '/' && $l[0] !== '\\' ) {
                            $domain .= '/' ;
                        }

                if ( ! strstr ( $domain, 'http:' ) and ! strstr ( $domain, 'https:' ))
                    $domain = 'http://' . $domain ;

                if ( strstr ( $redirect, 'http:' ) or strstr ( $redirect, 'https:' ))
                    $domain = '' ;

                $redir = "Location: {$domain}{$redirect}" ;
                
                // if there are unseen alerts, cache them in session so they 
                // can get spat out on the other side of this redirect
                if ( count ( $this->glob->alerts ) > 0 )
                    $_SESSION['xs_alerts'] = serialize ( $this->glob->alerts ) ;
                
                if ( $this->debug ) { print_r ( $redir ) ; die () ; }
                // die() ;
                header ( $redir ) ;
            }
        }


        function POST ( $input = array () ) {

            $only_content = $this->glob->request->__fetch ( 'only_content', 'false' ) ;
            $content = $this->glob->request->__fetch ( 'content', '' ) ;
            $content_property = $this->glob->request->__fetch ( 'content_property', 'value' ) ;
            $name = $this->glob->request->__fetch ( 'name', '' ) ;
            $id = $this->glob->request->__fetch ( 'id', '' ) ;

            if ( $only_content == 'true' ) {
                
                $topics = null ;
                
                if ( $name != '' ) {

                    // lookup by name
                    $topics = $this->glob->tm->query ( array ( 'name' => $name ) ) ;
                    // debug_r($topics,'name='.$name);
                    
                } elseif ( $id != '' ) {
                    
                    // lookup by id
                    $topics = $this->glob->tm->query ( array ( 'id' => $id ) ) ;
                    // debug_r($topics,'id='.$id);
                    
                }
                
                $topic = null ;
                // debug ( $topics )  ;
                
                if ( is_array ( $topics ) && count ( $topics ) > 0 ) {
                    $topic = reset ( $topics ) ;
                    // debug_r($topic, ' ' ) ;
                }
                
                if ( $topic !== null ) {

                    // ok, we've got a topic
                    if ( isset ( $topic[$content_property] ) || $content_property == 'value' ) {
                        
                        
                        $c = $this->tidy ( $content ) ;
                        
                        $try = simplexml_load_string ( '<span>'.$c.'</span>' ) ;
                        if ( $try ) {
                            $topic[$content_property] = $c ;
                            $topic['who'] = $this->glob->user->id ;
                            $this->glob->tm->update ( $topic ) ;

                            echo $content ;
                        } else {
                            echo "Failed parsing content as XHTML. Bummer." ;
                        }
                    } else {
                        
                        echo "Couldn't update with property [{$content_property}]." ;
                    }
                } else {
                    echo "Topic not found; name=[{$name}] or id=[{$id}]." ;
                }
                
                return ;

            }
            
            $redirect = $this->glob->request->__get ( '_redirect', '' ) ;
            if ( trim ( $redirect == '' ) )
                $redirect = XS_ROOT_ID ;
                

            // Create a generic identifier for this resource
            $resource_id = $this->glob->data->create_id ( 
                XS_PAGE_DB_IDENTIFIER, 
                array ( 'uri' => $redirect ) 
            ) ;

            // echo " 0:" ; print_r ( $resource_id ) ;
            
            // Define the structure query
            $this->glob->data->register_query (

                // identifier for what data connection to use (xs: default xSiteable)
                'xs',

                // identifier for our query
                $resource_id,

                // the query in question
                array ( 'name' => $resource_id ),

                // the timespan of caching the result
                '+5 seconds'
            ) ;
            
            // get generic data for this URI (resource)
            $page_db_lookup = $this->glob->data->get ( $resource_id ) ;
            
            if ( $this->debug ) { echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[fetch_all, from ".debugPrintCallingFunction()."]" ; print_r ( $page_db_lookup ) ; echo "</div>" ; }

            $fields = $this->glob->request->__get_fields () ;
            if ( $this->debug ) { echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[fetch_all, from ".debugPrintCallingFunction()."]" ; print_r ( $fields ) ; echo "</div>" ; }
            
            // got something?
            if ( count ( $page_db_lookup ) > 0 ) {
                reset ( $page_db_lookup ) ;
                // echo " 2:[" ; print_r ( key ( $page_db_lookup ) ) ; echo "]" ;skumring
                
                $fields = $fields + current ( $page_db_lookup ) ;
            }
            
            if ( $this->debug ) { echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[fetch_all, from ".debugPrintCallingFunction()."]" ; print_r ( $fields ) ; echo "</div>" ; }
            
            $who = isset ( $this->glob->user->values['id'] ) ? $this->glob->user->values['id'] : 0 ;
            
            // print_r ( $who ) ;
            
            // echo "<pre>" ;
            // print_r ( $fields ) ;
            
            if ( isset ( $fields['type'] ) && strstr ( $fields['type'], 'xs::_' ) ) {
                
                // there's a type coming in, and they specify a known xSiteable content type
                
                $e = 0 ;
                // echo "[$e] " ;
                $p = substr ( $fields['type'], 4 ) ;
                $e = $this->_type->$p ;
                $fields['type1'] = $e ;
                
                // eval ( '$e = ' . $fields['type'] . ";" ) ;
                // echo "[$e] " ;
                // debug($fields) ; die () ;
                // echo "[$resource_id]" ;

                switch ( $fields['type'] ) {
                    case 'xs::_page' : 
                        $fields['name'] = $resource_id ; 
                        break ;
                    case 'xs::_news' : break ;
                    default : break ;
                }
                unset ( $fields['type'] ) ;
            }
            
            // print_r ( $fields ) ;
            // die() ;
            if ( ! isset ( $fields['pub_full'] ) ) {
                $fields['pub_full'] = '<b>This</b> is fresh new content. Hit the edit button to make it yours!' ;
                $fields['pub_full_type'] = 0 ;
            }
            
            $c = $this->tidy ( $fields['pub_full'] ) ;
            
            $try = simplexml_load_string ( '<span>'.$c.'</span>' ) ;
            
            if ( $try !== false ) {
                
                $fields['pub_full'] = $c ;
                
                // echo "<pre style='background-color:orange;margin:20px;padding:20px;'>Bad content, no save!</pre>" ;
                // echo "<pre style='background-color:yellow;margin:20px;padding:20px;'>".htmlentities($fields['pub_full'])."</pre>" ;
                // print_r ( $try ) ;
                // return ;
            }
            
            // $item->inject ( $fields ) ;
           $fields['who'] = $this->glob->user->id ;
            
            // is the ID there, and is it over 0? (meaning; this is probably an update)
            if ( isset ( $fields['id'] ) && $fields['id'] > 0 ) {
                
                if ( $this->debug ) echo "old" ;
                $w = $this->glob->tm->update ( $fields ) ;
                $this->alert ( 'notice', 'Okay', 'You have successfully updated this content.' ) ;

            } else {
                
                if ( $this->debug ) echo "new" ;
                $w = $this->glob->tm->create ( $fields ) ;
                $this->alert ( 'notice', 'Good news!', 'You have successfully created a new page. Now edit it!' ) ;
                
            }
            
            $this->glob->data->reset ( $resource_id ) ;
            
            // die() ;
        }

        function tidy ( $html ) {
            $this->tidy->parseString ( $html, $this->tidy_config, 'utf8' ) ;
            $this->tidy->cleanRepair () ;
            return $this->keephtml ( $this->tidy ) ; 
        }
        
        function keephtml($string){
            return
               str_replace ( 
                  array ( '&lt;', '&gt;', '&quot;', '&amp;', '&nbsp;' ),
                  array ( '<', '>', '"', '&', ' ' ),
                  htmlentities($string)
               ) ;
        }
        
        
        function convert_html_to_txt ( $html ) {
            
            if ( $this->html2text == null )
                $this->html2text = new html2text () ;
            
            $naughty = array ( '&nbsp;', '$', 'â' ) ;
            $html = str_replace( $naughty, ' ', $html, $count ) ;

            // inject the html into the converter
            $this->html2text->set_html ( $html ) ;

            // pull out the text
            $text = $this->html2text->get_text () ;

            // no content?
            if ( trim ( $text ) == '' )
                return $text ;
            
            // make sure the characters of the text is in a certain range
            $w = '' ;
            for ( $n=0; $n<strlen($text); $n++ ) {
                $q = ord ( $text[$n] ) ;
                if ( ( $q > 64 && $q < 91 ) || ( $q > 96 && $q < 123 ) || ( $q > 47 && $q < 58 ) || $q == 32 || $q == 13 )
                    $w .= $text[$n] ;
            }
            $text = strtolower ( $w ) ;

            // break up all words; BigMama of a trim!
            $all = explode ( ' ', $text ) ;
            $r = '' ;
            foreach ( $all as $l ) {
                $l = trim ( $l ) ;
                if ( strlen ( $l ) > 1 )
                    $r .= $l . ' ' ;
            }
            
            return $r ;
        }
        
    }
