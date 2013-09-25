<?php

    class xs_action_instance extends xs_Action_Webpage {

        public $page = array (
            'title' => 'Homepage',
            'template' => '/layouts/col-3'
        ) ;

        function ___action () {

            // no real actions here; the index page uses the col3 template
            // which we populate with various widgets.

        }

    }
