<?php

class xs_action_instance extends \xs\Action\Generic {

    function ___action () {

        // header('Cache-Control: no-cache, must-revalidate');
        // header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        // header('Content-type: application/json');
/*
        $assoc = new \xs\TopicMaps\Assoc (
           $this->glob->tm->query_assoc ( array ( 
               'id' => $this->glob->request->id 
           ) )
        ) ;
        
        $assoc = new \xs\TopicMaps\Assoc (
           $this->glob->tm->query_assoc ( array ( 
               'type' => $this->_type->_has_owner, 
               'member_id' => $id 
           ) )
        ) ;
  
 * 
 */      
            echo "<p style='border:dotted 1px #999;'><h4>Request</h4><pre>" ;
            print_r ( $this->glob->request->__get_array () ) ;
            // print_r ( $this->glob->breakdown ) ;
            echo "</pre></p>" ;
            
        $items = array () ;
        
        foreach ( $this->glob->request->__get_array () as $idx => $value ) {
            if ( stristr ( $idx, 'as_values_' ) )
                    foreach ( @explode ( ',', $value ) as $item )
                       if ( trim ( $item ) !== '' )
                          $items[$item] = $item ;
        }
        
        // print_r ( $items ) ;
        
        
        $assoc_id = $this->glob->request->assoc_id ;
        $assoc_type = $this->glob->request->assoc_type ;
        $assoc_member_id = $this->glob->request->assoc_member_id ;

        if ( ! is_numeric ( $assoc_type ) ) {
            $find = $this->glob->tm->lookup_names ( array ( $assoc_type=>$assoc_type ) ) ;
            if ( $find ) {
                $t = end ( $find ) ;
                if ( is_array ( $t ) && isset ( $t['id'] ) )
                    $assoc_type = $t['id'] ;
            }
        }
        
        $assoc_id_lut = $assoc_type_lut = $assoc_member_id_lut = null ;
        
        $memtopics = $this->glob->tm->lookup_topics ( $items ) ;
        
        if ( trim ( $assoc_id ) !== '' )
            $assoc_id_lut = $this->glob->tm->lookup_assocs ( array ( $assoc_id=>$assoc_id ) ) ;
        
        if ( trim ( $assoc_type ) !== '' )
            $assoc_type_lut = $this->glob->tm->lookup_topics ( array ( $assoc_type=>$assoc_type ) ) ;
        
        if ( trim ( $assoc_member_id ) !== '' )
            $assoc_member_id_lut = $this->glob->tm->lookup_topics ( array ( $assoc_member_id=>$assoc_member_id ) ) ;
            
        if ( count ( $assoc_id_lut ) > 0 ) {
            echo "<p style='border:dotted 1px #999;'><h4>Found Assoc by ID</h4><pre>" ;
            var_dump ( $assoc_id ) ;
            print_r ( $assoc_id_lut ) ;
            echo "</pre></p>" ;
        }
        
        if ( count ( $assoc_type_lut ) > 0 ) {
            echo "<p style='border:dotted 1px #999;'><h4>Found Assoc Type topic</h4><pre>" ;
            var_dump ( $assoc_type ) ;
            print_r ( $assoc_type_lut ) ;
            echo "</pre></p>" ;
        }
        
        if ( count ( $assoc_member_id_lut ) > 0 ) {
            echo "<p style='border:dotted 1px #999;'><h4>Found Member topic</h4><pre>" ;
            var_dump ( $assoc_member_id ) ;
            print_r ( $assoc_member_id_lut ) ;
            
            echo "</pre></p>" ;
        }
        
        echo "<p style='border:dotted 1px #999;'><h4>Incoming topics</h4><pre>" ;
        print_r ( $memtopics ) ;
        echo "</pre></p>" ;
        
        if ( $assoc_type !== '' ) {
            $assoc1 = $this->glob->tm->query_assoc ( array ( 
                'type' => $assoc_type, 
                'member_id' => $assoc_member_id
            ) ) ;
            echo "<p style='border:dotted 1px #999;'><h4>Looking up [type] and [member_id]</h4><pre>" ;
            print_r ( $assoc1 ) ;
            echo "</pre></p>" ;
            if ( count ( $assoc1 ) > 0 ) {
                foreach ( $assoc1 as $topic )
                    $assoc_id = $topic['id'] ;
                echo "<p>Found assoc {$assoc_id}.</p>" ;
            }
        }
        
        $all = array_merge ( $memtopics, $assoc_member_id_lut ) ;
        echo "<p style='border:dotted 1px #999;'><h2>All members to go into this Assoc</h2><pre>" ;
        print_r ( $all ) ;
        echo "</pre></p>" ;
        
        if ( trim ( $assoc_id ) !== '' ) {
            
            echo "<h3>existing found; UPDATE</h3>" ;
            
            // first, kill old members; we'll create new ones
            $this->glob->tm->assoc_delete_all_members ( $assoc_id ) ;
            
            // ID exists; there's already an association
            $a = $this->glob->tm->query_assoc ( array ( 
                    'id' => $assoc_id,
                ) ) ;

            // $this->glob->tm->assoc_delete_members ( $assoc_id, array ( $this->_type->_user, $this->_type->_user_group ) ) ;
            
            echo "<p style='border:dotted 1px #999;'><h6>Found Assoc in DB</h6><pre>" ;
            print_r ( $a ) ;
            echo "</pre></p>" ;
            
            $members = array () ;

            if ( count ( $all ) > 0 )
                foreach ( $all as $topic )
                    $members[] = array ( 'topic' => $topic['id'], 'role' => $topic['type1'] ) ;
            
        echo "<p style='border:dotted 1px #999;'><h6>Let's create new ones:</h6><pre>" ;
        print_r ( $members ) ;
        echo "</pre></p>" ;
        
            if ( $members > 0 )
                $this->glob->tm->assoc_create_members ( $assoc_id, $members ) ;
            
            
            $a = $this->glob->tm->query_assoc ( array ( 
                    'id' => $assoc_id,
                ) ) ;
            $assoc = new \xs\TopicMaps\Assocs ( $a ) ;
            
            
            
            echo "<p style='border:dotted 1px #999;'><h4>Newly minted members</h4><pre>" ;
            print_r ( $a ) ;
            echo "</pre></p>" ;
            // $assoc->delete_members_of_type ( array ( $this->_type->_user, $this->_type->_user_group ) ) ;
            // print_r ( $assoc ) ;
            
            if ( count ( $memtopics ) > 0 ) {
                
                
            }
            
        } else {
            
            echo "CREATE" ;
            // No ID; new association
            
            $members = array () ;

            if ( count ( $assoc_member_id_lut ) > 0 )
                foreach ( $assoc_member_id_lut as $topic )
                    $members[] = array ( 'topic' => $topic['id'], 'role' => $topic['type1'] ) ;
            
            if ( count ( $memtopics ) > 0 )
                foreach ( $memtopics as $topic )
                    $members[] = array ( 'topic' => $topic['id'], 'role' => $topic['type1'] ) ;
            
            echo "[$assoc_type]" ;
            print_r ( $members ) ;
            
            
            $this->glob->tm->assoc_create ( array ( 'type' => $assoc_type, 'members' => $members ) ) ;
            
            
        }
        
       // $a = $this->glob->tm->lookup_assocs_with_one_or_less_member () ;
       // debug_r($a,'c');
        
        
        // print_r ( $this->glob->request ) ;
        
        // $items = explode ( ',', $this->glob->request->items ) ;
        
        // print_r ( $items ) ;
        
        // echo @json_encode($result);
        die();

        
        
        
        
    }
}

function trim_value(&$value) {
    $value = trim($value);
}








