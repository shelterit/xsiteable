<?php

class _harvest extends _admin {

    function __construct ( $conf = array () ) {
        parent::__construct ( $conf ) ;
    }

    function this_action () {

        $filter = trim ( $this->glob->breakdown->id ) ;
        echo "<h1>Filter: '{$filter}'</h1>" ;
        
        $this->load () ;
        $this->load_all_index () ;

        $base_folder = $this->glob->config['dms']['destination_folder'] ;
        
        foreach ( $this->docs_repo as $idx => $file ) {

             // if ( @$counter++ > 10 ) break ;
             $test = true ;
                
             if ( $filter !== '' ) {
                 $test = stristr ( $file['original'], $filter ) ;
                 // echo "[".$file['original']."] [".$filter."] = [".var_export($test,true)."]<br>" ;
             }
             // var_dump ( $file['original'] ) ;
             
             if ( $test ) {

                $uid = $idx;
                echo "<h2>" ; print_r ( $idx ) ;
                echo " (".str_replace ( array (',','_','(',')','-','+'), ' ', $file['filename'] ).")</h2>" ;

                $co = strtolower ( file_get_contents( $file['txt'] ) ) ;

                for ( $n=0; $n<100; $n++ )
                    $co .= ' ' . strtolower ( str_replace ( array (',','_','(',')','-','+','/'), ' ', $file['filename'] ) ) ;

                // Break up the text
                $c = explode(' ', $co ) ;
                $res = array();
                $fin = array();

                echo "words_total=[".count($c)."] " ;




                // Clean up each chunk of text
                foreach ($c as $d) {
                    $e = trim ( $d ) ;
                    if ( $e != '' ) {
                        $r = PorterStemmer::CleanUp(' ' .$e. ' ') ;
                        if ($r !== null && $r != '' ) {
                            // echo "[$d]=[$r] " ;
                            $res[] = $r;
                        }
                    }
                }
                echo "words_clean=[".count($res)."] " ;

                // Sort the result alphabetically
                // sort($res);
                // echo "<pre>" ; print_r ( $res ) ; echo "</pre>" ;

                $this->docs[$idx]['wordcount'] = sizeof($res);
                $this->docs_repo[$idx]['wordcount'] = sizeof($res);


                $n = array();

                foreach ($res as $r)
                    if (isset($n[$r]))
                        $n[$r]++;
                    else
                        $n[$r] = 1;

                // arsort($n);
                // print_r ( $n) ;

                foreach ( $n as $keyword => $count ) {

                    if ( ! isset ( $this->idx[$keyword][$uid] ) )
                        $this->idx[$keyword][$uid] = $count ;
                    else
                        $this->idx[$keyword][$uid] += $count ;

                }

                // echo "<h1>[$keyword]</h1> <pre style='font-size:7px;'>" ; print_r ( $this->idx[$keyword] ) ; echo "</pre> " ;
                // $this->idx = array_merge ( $this->idx, $n ) ;

                $this->docs[$idx]['wordprune'] = sizeof($n);
                $this->docs_repo[$idx]['wordprune'] = sizeof($n);

                $m = array();
                $max = 1 ;
                $tot = 0 ;

                foreach ($n as $w => $c)
                    if ($c > 2) {
                        $m[$w] = $c;
                        if ( $c > $max ) $max = $c ;
                        $tot++ ;
                        // echo $w . " | " ;
                    }

                // ksort($m);


                echo "words_final=[".count($m)."] " ;

                $div = 250 / $max ;

                // echo "count[$tot] " ;
                echo "largest_word_count=[$max] " ;
                echo "factor=[$div] <br> <div style='font-size:7px;border:solid 1px #aaa;margin 10px;padding:10px;background-color:#efe;'>" ;

                foreach ($m as $w => $c) {
                    $cal = (int) 251 - (int) ( $div * $c ) ;
                    // $col = hex_color ( $cal ) ;
                    echo "<span style='color:rgb($cal,$cal,$cal);'>" . $w . "</span> | " ;
                }

                echo "</div> " ;

                $this->docs[$idx]['wordimportant'] = $m;
                $this->docs_repo[$idx]['wordimportant'] = $m;
                $this->docs_repo[$idx]['_harvested'] = true ;

                $res = $fin = array();

                // echo '['.$file['original'].']->' ;
                // echo '['.$this->glob->config['dms']['destination_folder'].']->' ;
                $f = trim ( substr ( $file['original'], strlen ( $this->glob->config['dms']['source_folder'] ) + 1 ) ) ;
                // echo "[$f]->" ;
                $f = trim ( substr ( $f, 0, strlen ( $f ) - 4 ) ) ;
                // echo "[$f]->" ;
                // $record['small'] = $s ;
                
                // $f = $file['small'] ;
                // $s = trim(substr($f, 0, strlen($f) - 9));
                // echo "[$s]  ***  " ;

                $this->docs[$idx]['facets'] = explode ( '/', $f ) ;
                $this->docs_repo[$idx]['facets'] = explode ( '/', $f ) ;


                //  echo "<pre style=''>" ; print_r ( $this->docs[$idx]['facets'] ) ; echo "</pre>" ;
                my_flush();
             }

        }

        // echo "<pre style=''>" ; print_r ( $this->idx ) ; echo "</pre>" ;


        $this->save () ;

        // echo "<pre>" ; print_r ( $this->idx ) ; echo "</pre>" ;


        $this->glob->log->add ( 'spider : save index' ) ;
        $this->save_index () ;



        // $this->glob->log->add ( 'spider : load index' ) ;
        // $this->load_index () ;




        echo "<h1>Okay, done!</h1>" ;
        
        // echo $this->glob->log->report() ;
        
        die() ;
    }

}
