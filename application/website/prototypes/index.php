<?php

    class xs_action_instance extends \xs\Action\Webpage {

        public $page = array (
            'title' => 'Prototypes',
            'template' => '/layouts/col-3-breadcrumbs'
        ) ;

        function ___action () {

            // no real actions here; the index page uses the col3 template
            // which we populate with various widgets.

        }
        
        function ___gui_section0 () {
            return "<b>test0</b>" ;
        }

        function ___gui_section1 () {
            return "<b>test1</b>" ;
        }

        function ___gui_section2 () {
            return "<b>test2</b>" ;
        }

        function ___gui_section3 () {
            return "<b>test3</b>" ;
        }

    }
