<?php

class xs_action_instance extends \xs\Action\Webpage {

    public $page = array(
        'title' => "Keywords"
    );

    function ___action() {

        $r = $this->glob->request;
        $b = $this->glob->breakdown;



        // Parse the request URI a bit differently, looking for facets (f[n])
        $b->_parse ( '{concept}/{f1}/{f2}/{f3}/{f4}/{f5}/{f6}/{f7}/{f8}/{f9}/{f10}/{f11}/{f12}/{f13}/{f14}/{f15}/{f16}/{f17}/{f18}/{f19}/{f20}/{f21}/{f22}/{f23}/{f24}' ) ;
        $facets = array () ;
        $last = $add = $l = '' ;

        // Pull out all facets (well, first 24) from the request URI
        for ( $n=1; $n < 24; $n++) {
            $x = trim($b->__fetch ( "f$n", '' )) ;
            if ( $x != '' ) {
                $facets[urlsafe($x)] = $x ;
                $last = urlsafe($x) ;
                $add .= '/'.$last ;
            }
        }

        foreach ( $facets as $facet ) {
            $facets[$facet] = str_replace('/'.$facet, '', $add) ;
        }

        // Pop it on the stack
        $this->glob->stack->add ( 'xs_facets', $facets ) ;

        // Log results
        if ( count ( $facets ) > 0 ) {
            // $this->glob->logger->logInfo ( '['.$this->glob->user->username.'] {keywords} '.$add ) ;
            $this->log ( 'READ', $add ) ;
        }




        // Load and prepare our document data
        $keywords = new Keywords ( $this->glob ) ;
        $data = $keywords->data ;


        // $start = '/home/alexander/Documents/wc/l-drive/Public - Policy and Procedures';
        // $data  = unserialize(file_get_contents('application/datastore/cmd.arr'));


        $x = array() ;

        foreach ( $data as $idx => $doc ) {

            if ( isset ( $doc['wordimportant'] ) ) {

                foreach ( $doc['wordimportant'] as $item => $count ) {
                    if ( ! isset ( $facets[$item] ) ) {
                        if ( !isset ( $x[$item] ) ) $x[$item] = 0 ;
                        $x[$item]++ ;
                    }
                }
            }
        }

        arsort($x);
        

        $urladd = '' ;
        foreach ( $facets as $facet => $adr )
            $urladd .= '/' . $facet ;



        // Get the resource for the tagcloud service
        $cloud = $this->_get_resource ( '_api/services/keywords/cloud' ) ;

        $res = $cloud->POST ( array (
            'base' => $this->glob->dir->home . '/keyword' . $urladd,
            'list' => $x,
            'max' => 300
        ) ) ;

        $this->glob->stack->add ( 'xs_content', array (
            'keywords' => $res
        ) ) ;


        $final = array () ;

        $cmd = unserialize(file_get_contents('application/datastore/cmd.arr'));

        foreach ($cmd as $cmd_idx => $f) {

            $words = $f['wordimportant'] ;

            foreach ( $facets as $find => $blank ) {
                    // echo "[$find]" ;
                if ( isset ( $words[$find] ) || isset ( $words[$find.'s'] ) ) {
                    // echo "*" ;
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




        $result = array () ;

        $start = (int) $this->glob->request->__fetch ( 'start', '0' ) ;
        $max   = (int) $this->glob->request->__fetch ( 'max', '10' ) ;
        if ( $max < 1 ) $max = 10 ;

        $pager = new \xs\Data\Paginator ( count($final), $max, $start ) ;

        $this->glob->page->current_page = $pager->getCurrentPage() ;
        $this->glob->page->total = count ( $final ) ;

        // print_r ( $this->glob->page ) ;




        $test = array ( 'start' => $start, 'max' => $max ) ;

        $counter = 0 ;


        foreach ( $final as $uid => $words ) {

            if ( $counter >= $start && $counter < $start + $max ) {

                $file = $cmd[$uid] ;

                $co = @file_get_contents( $file['txt'] ) ;

                $co = str_replace ( array("\n","\r",',','*','&', '<', '>'), '', $co ) ;
                // $co = str_replace ( array('  ','   ','    ','     ','      ', '       ', '        ', '         '), ' ', $co ) ;

                $co = sanitize ( trimText ( $co ) ) ;

                $times = count ( $words ) ;
                $length = 140 / $times ;
                $res = array () ;

                foreach ( $words as $word => $ccccc ) {
                    $p = stripos ( $co, $word ) ;
                    $r = substr ( $co, $p, $length ) ;
                    $thi = sanitize(trim($word)) ;
                    $tha = trim('<b>'.$word.'</b>') ;
                    $res[$word] = str_ireplace ( $thi, $tha, $r ) ;
                }
                $y = '' ;
                foreach ( $res as $word ) {
                    $y .= $word . ' ... ' ;
                }
                // $extract = substr ( $co, 0, 500 ) ;

                $e = array () ; $cc = 0 ;
                foreach ( $file['wordimportant'] as $t => $ccccc )
                    if ( $cc++ < 6) $e[] = $t ;

                $result[$uid] = array (
                    'title' => sanitize(htmlentities ( $file['fileinfo']['filename'] )),
                    'url' => $file['small'],
                    'extract' => $y,
                    // 'raw' => $co,
                    'tags' => $e
                ) ;
                // echo '.' ;
            }

            $counter++ ;
        }

        $end_time = microtime ( true ) ;

        // $this->glob->page->total_time = sprintf ( "%01.3f", $end_time - $start_time ) ;

        // echo "<pre>" ; print_r ( $result ) ; echo "</pre>" ; die() ;

        $this->glob->stack->add ( 'xs_result', $result ) ;



        // Pop it on the stack
        // $this->glob->stack->add ( 'xs_results', $final ) ;







    }

}





function trimText($str)
{
    $str = trim($str);
    $str = preg_replace('/\h+/', ' ', $str);
    $str = preg_replace('/\v{3,}/', PHP_EOL.PHP_EOL, $str);

    return $str;
}