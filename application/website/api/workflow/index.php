<?php

class xs_action_instance extends \xs\Action\Generic {

    public $metadata = array(
        'name' => "Workflow",
    );

    function ___action() {

        var_dump ( $this->glob->breakdown->__get_array () ) ;

        if ( $this->glob->breakdown->id != '' ) {

            $id = $this->glob->breakdown->selector ;

            echo "[$id] ". $this->glob->request->get_method () ;

            $fields = $this->glob->request->__get_fields () ;

            var_dump ( $fields ) ;

            switch ( $this->glob->request->get_method () ) {

                case 'GET' :

                    // display FORM

                    break ;

                case 'POST' :

                    // process FORM

                    // new ITEM, return ID

                    break ;

                case 'PUT' :

                    // process FORM

                    // update existing ITEM by ID

                    break ;

                case 'DELETE' :

                    // process FORM

                    // delete existing ITEM by ID

                    break ;
            }




        }
    }

}
