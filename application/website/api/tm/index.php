<?php

class xs_action_instance extends xs_Action {

    public $metadata = array (
        'template' => 'generic_data'
    ) ;
    
    // GET collections of vocabs

    function GET () {

        $tm = $this->glob->tm ;
        $br = $this->glob->breakdown ;

        $find = $tm->get_role_types () ;
        ksort ( $find ) ;
        
        $this->glob->stack->add ( 'xs_topicmap', $find ) ;


    }

}
