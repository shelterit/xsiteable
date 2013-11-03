<?php

class xs_action_instance extends \xs\Action {

    public $metadata = array (
        'template' => 'generic_data'
    ) ;
    
    // GET collections of vocabs

    function GET () {

        $tm = $this->glob->tm ;
        $br = $this->glob->breakdown ;
        
        $this->glob->stack->add ( 'xs_topicmap',
                $tm->get_topic_by_id ( $br->selector )
        ) ;

    }

}
