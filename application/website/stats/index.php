<?php

class xs_action_instance extends \xs\Action\Webpage {

    public $metadata = array(
        'name' => "en:Admin",
        'template' => XS_PAGE_AUTO,
    );

    function ___action() {

        $this->glob->request->__set ( 'model', $this->glob->breakdown->__get ( 'section', 'a' ) ) ;

    }

}
