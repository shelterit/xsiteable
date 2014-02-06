<?php

class xs_action_instance extends \xs\Action\Generic {

    function flop ( $arr ) {
        $ret = array () ;
        foreach ( $arr as $z ) 
            $ret[$z] = $z ;
        return $ret ;
    }
    function ___action () {

        $dms = $this->_get_module ( 'dms' ) ;
        $function = '' ;
   
        $aColumns = $this->flop ( array ( 'id','label', 'm_c_date', 'm_p_date', 'm_u_date' ) ) ;
        $bColumns = $this->flop ( array ( 'extension', 'relative_path', 'next_review_date' ) ) ;
        $allCols  = array ( 'id', 'label', 'extension', 'relative_path', 'm_c_date', 'm_p_date', 'm_u_date', 'next_review_date' ) ;
        
        $lut = array () ;
        foreach ( $bColumns as $col ) {
            $tmp = $dms->find_db_properties ( $this->_type->doc, $col ) ;
            if ( is_array ( $tmp ) ) {
                $lut[$col] = $tmp ;
            }
        }
        

	/* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
			intval( $_GET['iDisplayLength'] );
	}
        $start = (int) isset ( $_GET['iDisplayStart'] ) ? $_GET['iDisplayStart'] : 0 ;
        $items = (int) isset ( $_GET['iDisplayLength'] ) ? $_GET['iDisplayLength'] : 20 ;
        $dir = isset ( $_GET['sSortDir_0'] ) ? $_GET['sSortDir_0'] : 'asc' ;
        $function .= 'dir=['.$dir.'] ' ;
	
        $field_idx = (int) isset ( $_GET['iSortCol_0'] ) ? $_GET['iSortCol_0'] : 0 ;
        $function .= 'field_idx=['.$field_idx.'] ' ;
        
        // $all = array_merge ( $aColumns, $bColumns ) ;
        $field = isset ( $allCols[$field_idx] ) ? $allCols[$field_idx] : 0 ;
        $function .= 'field=['.$field.'] ' ;
	
	/*
	 * Ordering
	 */
        
        // debug_r ( $all, $all[ $_GET['iSortCol_0'] ] ) ;
        
        // debug_r ( $this->glob->request ) ;
        
        $sOrder = "ORDER BY ";
        for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ ) {

            if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" ) {

                $sOrder .= $allCols[ $_GET['iSortCol_0'] ]." ASC ";
            }
        }

        
        $tm = $this->glob->tm ;
        
        $sql = "SELECT * " . //t.id,t.label,t.m_c_date,t.m_u_date,t.m_p_date,p.type_name,p.value,count(p.id) as ps
               "FROM xs_topic t
                WHERE t.type1 = {$this->_type->doc} " ;

        // the results array
        $res = array () ;
        
        // debug_r ( $field ) ;
        
            
        // is the field in the a group? Use SQL to sort and limit
        if ( isset ( $aColumns[$field] ) ) {

            $function .= 'sql ' ;
            
            $sql .= ' ' . $sOrder . ' ' . $sLimit ;
            $res = $tm->fetchAll ( $sql ) ;
            
            foreach ( $res as $idx => $topic ) {
                $id = $topic['id'] ;
                foreach ( $bColumns as $col ) {
                    $res[$idx][$col] = '' ;
                    if ( isset ( $lut[$col] ) ) {
                            // $res[$idx][$col] = $idx ;
                        if ( isset ( $lut[$col][$id] ) ) {
                            $res[$idx][$col] = $lut[$col][$id]['value'] ;
                        }
                    }
                }
            }



        // is the field in the b group? Manual intervention!
        } elseif ( isset ( $bColumns[$field] ) ) {

            $function .= 'lut ' ;
            
            // 1. get and sort the column in question
            $tmp = $lut[$field] ;
            natsort2d ( $tmp, 'value', $dir ) ;
            
            if ( $dir == 'desc' )
                $tmp = array_reverse ( $tmp, true ) ;
            
            // 2. limit the result
            $count = 0 ;
            $topics = array () ;
            foreach ( $tmp as $idx => $value )
                if ( $count++ >= $start && $count <= $start + $items ) 
                    $topics[$idx] = $idx ;

            // 3. fetch topics for the result
            $res = $tm->query ( array ( 'id' => $topics ) ) ;

            natsort2d ( $res, $field, $dir ) ;

            // debug_r ( $res ) ;

        }

        if ( $dir == 'desc' )
            $res = array_reverse ( $res, true ) ;
    
        
        // debug_r ( $sql ) ;
        // debug_r ( $res ) ;
        
        // die () ;
        
	/*
	 * Output
	 */
	$output = array(
                "function" => $function,
                "sOrder" => $sOrder,
		"sEcho" => intval(isset($_GET['sEcho'])?$_GET['sEcho']:''),
		"iTotalRecords" => count($res),
		"aaData" => array(),
	);
	
        // $allCols = array_merge ( $aColumns, $bColumns ) ;
        // debug_r ( $allCols ) ;
        
	foreach ( $res as $aRow )
	{
            // debug_r ( $aRow ) ;
		$row = array();
		for ( $i=0 ; $i<count($allCols) ; $i++ ) {
                    $row[] = isset ( $aRow[ $allCols[$i] ] ) ? $aRow[ $allCols[$i] ] : '' ;
		}
		$output['aaData'][] = $row;
	}
	
	echo php2js( $output );        
        
        die () ;
        
    }
}






