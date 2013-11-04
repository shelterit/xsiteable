<?php

class xs_action_instance extends \xs\Action\Generic {

    function ___action () {

        $topic_id = $this->glob->request->topic_id ;
        $topic_id_lut = null ;
        
        if ( trim ( $topic_id ) !== '' )
            $topic_id_lut = $this->glob->tm->query ( array ( 'id' => $topic_id ) ) ;
        
        if ( count ( $topic_id_lut ) > 0 ) {
            echo "<p style='border:dotted 1px #999;'><h4>Found Topic by ID</h4><pre>" ;
            var_dump ( $topic_id ) ;
            print_r ( $topic_id_lut ) ;
            echo "</pre></p>" ;
            
            
            
            
            $topic = null ;
            foreach ( $topic_id_lut as $idx => $t )
                $topic = new \xs\TopicMaps\Topic ( $t ) ;
            
            $topic->inject ( array ( 'next_review_date' => $this->glob->request->next_review_date ) ) ;
            print_r ( $topic ) ;
            
            $this->glob->tm->update ( $topic->get_as_array (), true ) ;
            
            // print_r ( $this->glob->request ) ;
            
            
            
            
        }

    }
}






