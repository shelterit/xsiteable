<?php
	
    namespace xs\Action ;
     
    class Webpage extends \xs\Action\Generic {

        // stuff to put in the functionality box
        protected $in_the_box = '' ;
        
        // the alignment of the functionality box
        protected $in_the_box_align = 'right' ;
        
        protected $parent = null ;

        function __construct () {

            // Go to parents constructor first, making this a xs_Action class
            parent::__construct() ;

            // Set default style
            $this->set_style ( $this->__if_set ( $this->glob->config['website']['style'], 'smoothness' ) ) ;
            // debugPrintCallingFunction ( '#def' ) ;
        }
        
        function ___gui_section_page_functionality () {
            
            $html = $this->glob->html_helper ;
            if ( trim ( $this->in_the_box ) == '' ) return ;
            return $html->func_menu ( $this->in_the_box_align, $this->in_the_box ) ;
            
        }
        
        function _set_parent ( $parent ) {
            $this->parent = $parent ;
        }
        
    }
