<?php

class xs_action_instance extends xs_Action_Webpage {

    public $page = array(
        'title' => 'Search'
    );

    function ___action () {
        
        $dms = $this->_get_module ( 'dms' ) ;
        $dms->_preamble_search () ;
        
        $relative_paths = $dms->get_relative_lut () ;
        
        $lut = array () ;
        $tmp = $dms->all_documents ;
        if ( is_array ( $tmp ) ) 
            foreach ( $tmp as $topic_id => $topic )
                $lut[substr($topic['name'],9)][$topic_id] = $topic_id ;
        
        // echo count ( $tmp ) ;
        // debug_r ( $lut ) ;
        
        $this->glob->log->add ( 'search : In the begining ... ' ) ;

        $url = strtolower ( $this->glob->request->__fetch ( 'searchquery', '' ) ) ;
        
        $start_time = microtime ( true ) ;

        // Break up the text
        $c = explode(' ', $url);
        $terms = array();
        $fin = array();
        $l = '' ;

        // Clean up each chunk of text
        foreach ($c as $d) {
            $r = strtolower ( trim($d) ) ; //PorterStemmer::CleanUp(' ' . $d . ' ');
            if ($r !== '' ) {
                $terms[] = $r;
                $l .= $r.' ' ;
            }
        }

        // Sort the result alphabetically
        sort($terms);

        $final = array () ;

        $appendix = $this->_get_module ( 'appendix' ) ;
        
        $appendix->load_index_from_terms ( $terms ) ;
        
        $found = $appendix->find ( $terms ) ;
        
        $this->glob->log->add ( 'search : loaded indeces and merged, terms found.' ) ;

        $finals = array () ;
        $score  = array () ;

        foreach ( $found as $term => $uids )
            foreach ( $uids as $id => $count )
                $finals[$id][$term] = $count ;

        foreach ( $finals as $id => $arr ) {
            $tot = 0 ;
            $co = count ( $arr ) ;
            foreach ( $arr as $sc ) {
                $tot = $tot + ( ( $sc * $co ) * ( $co * 999 ) ) ;
            }
            $score[$id] = $tot ;
        }
        
        arsort ( $score ) ;

        // echo "<pre>" ; print_r ( $finals ) ; echo "</pre>" ;
        // echo "<pre>" ; print_r ( $score ) ; echo "</pre>" ;

        $this->glob->log->add ( 'search : scored and sorted, small search done.' ) ;





        // echo "<pre>" ; print_r ( $score ) ; echo "</pre>" ; die() ;
        // echo "<pre>" ; print_r ( $finals ) ; echo "</pre>" ; die() ;
        /*
        foreach ($cmd as $cmd_idx => $f) {

            $words = $f['raw'] ;

            foreach ( $res as $find ) {
                if ( isset ( $words[$find] ) || isset ( $words[$find.'s'] ) ) {
                    $e = htmlentities ( $f['uid'] ) ;
                    if ( !isset ( $final[$e][$find] ) )
                        $final[$e][$find] = 0 ;
                    $w = 1 ;
                    if ( isset ( $words[$find] ) ) $w = $words[$find] ;
                    $final[$e][$find] += $w ;
                }
            }
        }

        arsort ( $final ) ;

        */

        $result = array () ;
        $changes = false ;
        
        $max = count ( $relative_paths ) ;

        foreach ( $score as $uid => $points ) {
            
            if ( ! isset ( $lut[$uid] ) ) {
                unset ( $score[$uid] ) ;
                $max-- ;
                $changes = true ;
                // code here to delete UIDs from appendix
                
                
                
                
                
                
                // echo "UID [$uid] not found. " ;
            } else {
                // debug_r ( $lut[$uid], 'UID found' ) ;
            }

        }
        
        if ( $changes ) {
            // save appendix here
        }
        

        $pager = new xs_Paginator ( count($score), 10 ) ;

        $this->glob->page->current_page = $pager->getCurrentPage() ;
        $this->glob->page->total = count ( $score ) ;
        $this->glob->page->total_docs = $max ;
        
        // print_r ( $this->glob->page ) ;

        // $test = array ( 'start' => $start, 'max' => $max ) ;

        $counter = 0 ;
        
        // echo "<pre>" ; print_r ( $cmd ) ; echo "</pre>" ; die() ;
        $this->glob->log->add ( 'search : About to trawl them for snippets of text, second pass' ) ;

        $base_folder = $this->glob->config['dms']['destination_folder'] ;

        foreach ( $score as $uid => $points ) {
            
            if ( ! isset ( $lut[$uid] ) ) {
                // echo "UID [$uid] not found. " ;
            } else {
                // debug_r ( $lut[$uid], 'UID found' ) ;
            }

            if ( $counter >= $pager->start_item && $counter < $pager->start_item + $pager->items_pr_page ) {

                $home_directory = $dms->get_dir_structure ( $uid ) ;
                
                $filename = $base_folder . $home_directory . "/{$uid}.txt" ;

                if (file_exists ( $filename ) ) {
                    
                    $topics = $this->glob->tm->query ( array ( 'name' => 'document:'.$uid ) ) ;
                    $topic = reset ( $topics ) ;
                    // debug_r ( 'document:'.$uid, $filename ) ;
                    //if ( count ( $topic ) > 0 ) 
//                        $topic = end ( $topic ) ;
//                    else {
//                        // Hmm, no topic. 
//                    }
                    $co = file_get_contents( $filename ) ;

                    // $co = str_replace ( array("\n","\r",',','*','&', '<', '>'), '', $co ) ;
                    // $co = sanitize ( $co ) ;
                    // $co = str_replace ( array('  ','   ','    ','     ','      ', '       ', '        ', '         '), ' ', $co ) ;

                    // $co = trimText ( $co ) ;

                    $times = count ( $finals[$uid] ) ;
                    $length = 140 / $times ;
                    $res = array () ;

                    foreach ( $finals[$uid] as $word => $ccccc ) {
                        $p = stripos ( $co, $word ) ;
                        $r = substr ( $co, $p, $length ) ;
                        $thi = trim($word) ;
                        $tha = trim(' <b>'.$word.'</b> ') ;
                        $res[$word] = str_ireplace ( $thi, $tha, $r ) ;
                    }
                    $y = '' ;
                    foreach ( $res as $word ) {
                        $y .= $word . ' ... ' ;
                    }
                    // $extract = substr ( $co, 0, 500 ) ;

                    $e = array () ; $cc = 0 ;
                    if ( isset ( $topic['words_important'] ) && is_array ( $topic['words_important'] ) && count ( $topic['words_important'] ) > 0 )
                        foreach ( $topic['words_important'] as $t => $ccccc )
                            if ( $cc++ < 6) $e[] = $t ;

                    // debug_r($topic);
                    $result[$uid] = array (
                        'id' => $topic['id'],
                        'title' => isset ( $topic['label'] ) ? $topic['label'] : '-title not found-',
                        'url' => $home_directory . "/{$uid}.html",
                        'extract' => $y,
                        'score' => isset ( $score[$uid] ) ? $score[$uid] : 'n/a ('.$uid.')',
                        // 'raw' => $co,
                        'tags' => $e
                    ) ;
                    // echo '.' ;
                    
                } else {
                    
                    $result[$uid] = array (
                        'id' => $topic['id'],
                        'title' => isset ( $topic['label'] ) ? $topic['label'] : '-title not found-',
                        'url' => $home_directory . "/{$uid}.html",
                    ) ;
                }
            }

            $counter++ ;
        }

        $end_time = microtime ( true ) ;

        $this->glob->page->total_time = sprintf ( "%01.3f", $end_time - $start_time ) ;

        // echo "<pre>" ; print_r ( $result ) ; echo "</pre>" ; die() ;

        $this->glob->stack->add ( 'xs_result', $result ) ;
        // $this->glob->stack->add ( 'xs_result_score', $score ) ;

        $this->glob->stack->add ( 'xs_result_pages', $pager->getPages() ) ;

        $t = array (
            'search' => 'Search',
        ) ;

        if ( count ( $result ) > 0 )
            $t[trim(htmlentities($l))] = '"'.trim($l).'"' ;

        $this->glob->stack->add ( 'xs_facets', $t  ) ;

        $this->glob->stack->add ( 'xs_max', array (
            array ( 'label' => '10', 'value' => '10' ),
            array ( 'label' => '20', 'value' => '20' ),
            array ( 'label' => '30', 'value' => '30' ),
            array ( 'label' => '40', 'value' => '40' ),
        ) ) ;

        // echo "<pre>" ; print_r ( $final ) ; echo "</pre>" ;

        if ( $this->glob->page->total > 0 ) {
            // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {search} "'.$l.'" (found '. $this->glob->page->total .' docs)' ) ;
            $this->log ( 'READ', $l.'" (found '. $this->glob->page->total .' docs)' ) ;
        }
        
    }
}



function trimText($str)
{
    $str = trim($str);
    $str = preg_replace('/\h+/', ' ', $str);
    $str = preg_replace('/\v{3,}/', PHP_EOL.PHP_EOL, $str);

    return $str;
}