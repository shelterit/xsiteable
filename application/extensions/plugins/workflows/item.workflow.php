<?php

    class xs_workflow_item extends \xs\Action\Generic {

        public $metadata = array (
            'name' => 'ADOdb database abstraction plugin',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        public $id = null ;

        public $state = null ;


        function ___settings () {

            $workflow = array (
                'display form',
                'process incoming form',
                'redirect to original page'
            ) ;

        }

        // Note that __init is a stateless start of the workflow
        // If no further action is done, ie. someone POSTs to
        // a resource by id, the workflow won't be instantiated
        // in the database

        function ___workflow__init () {
            // display FORM
        }

        // only two methods are given up front; begin() and end(), the rest needs
        // to be mapped by the workflow composer

        function ___workflow__begin () {
            // first item in workflow that creates a workflow instantiation in the database
        }

        function ___workflow__end () {
            // last item in the workflow, deletes the instantiation
        }

    }
