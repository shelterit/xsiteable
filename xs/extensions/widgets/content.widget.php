<?php

    class xs_widget_content extends xs_Action_Widget_Controller {
        
        public $meta = array (
            'name' => 'Content widget',
            'description' => 'A simple widget that either edits as its own content block, or imports some other page',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
            'category' => 'content'
        ) ;

        public $settings = array (
            'title' => '',
            'color' => 'color-blue',
        ) ;

        private $key = null ;
        
        private $running = false ;

        
        function ___this_instance () {
            $this->running = true ;
        }

        function GET () {
            
            $name = $this->glob->request->name ;

            if ( $name != '' )
                $this->load_widget ( $name ) ;

            $inst = $this->get_instance ( $name ) ;
            
            $content = 'No content.' ;
            
            $cnt = $this->_get_module ( 'generic_content' ) ;
            
            if ( isset ( $inst->_topic['value'] ) ) {
                $content = $cnt->tidy ( $inst->_topic['value'] ) ;
            }
            
            if ( strpos ( $content, '{list:sub-pages}' ) !== null ) {
            
                $content = str_replace ( '{list:sub-pages}', 
                    $this->glob->html_helper->create_linked_list ( $this->resource_id, $this->found_sub, null, '' ), 
                    $content 
                ) ;
                
            }
            
            echo $content ;
        }
                

        // Default output
        function GET_content ( $params = null, $name = null ) {
            
            $inst = $this->get_instance ( $name ) ;
            
            // debug_r ( $inst, $name ) ;
            
            $class = 'xs-content' ;
            $content = '' ;
            
            $cnt = $this->_get_module ( 'generic_content' ) ;
            
            if ( isset ( $inst->_topic['value'] ) ) {
                $content = $cnt->tidy ( $inst->_topic['value'] ) ;
            }
            
            $html = '<div><div id="'.$name.'-content" class="'.$class.'">'.$content.'</div>' ;
            
            // Call security!
            $security = $this->_get_module ( 'security' ) ;
            
            if ( $security->has_access ( 'widget:content:edit', false ) ) {
                
                $html .= '<button style="float:right;margin-left:6px;font-size:0.8em;" type="button" onclick="xs_generic_content(\''.$name.'\');">Edit</button>' ;
             }
            
            $html .= '</div>' ;
            
            return $this->prepare ( $html ) ;
            
        }

    }
