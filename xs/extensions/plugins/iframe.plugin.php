<?php

/*
 * This plugin allows you to create an iframe inside one of the main tabs on the
 * main menu.
 */

    class xs_plugin_iframe extends \xs\Action\Generic {
        
        public $meta = array (
            'name' => 'iframe plugin',
            'description' => 'Basic iframe in tabs',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // Holds the reference to the menu plugin instance, for API access
        private $menu = null ;

        // Holds our structures (iframe items)
        private $struct = array () ;

        function ___settings () {
            // $this->_page->template = 'test' ;
        }
        
        function ___on_core_process_request () {
            
            $uri = $this->glob->request->_uri ;
            
            if ( isset ( $this->glob->website['main'][$uri] ) ) {
                
                if (  isset ( $this->glob->website['main'][$uri]['@iframe'] ) ) {
                
                    $inst = new \xs\Action\Webpage () ;

                    // add 'page', 'widgets' and 'menus' events just after the 'XS_DISPATCH' event
                    $inst->_add_event ( 'XS_DISPATCHER', 'XS_PAGE' ) ;
                    $inst->_add_event ( 'XS_DISPATCHER', 'XS_WIDGETS' ) ;
                    $inst->_add_event ( 'XS_DISPATCHER', 'XS_MENUS' ) ;
                    
                    // Set the URI we're using
                    $inst->_page->uri = $this->glob->website['main'][$uri]['@iframe'] ;

                    // Set the title of the page
                    $inst->_page->title = $this->glob->website['main'][$uri]['@label'] ;

                    // Set the template of the page
                    $inst->_page->template = 'templates/pages/iframe' ;

                    \xs\Core::$glob->log->add ( "iframe.plugin: dispatched iFrame(".$uri.")" ) ;
                    
                    return array ( 'iframe', $inst ) ;
                }
                
            }
            
            return null ;
        }

    }
