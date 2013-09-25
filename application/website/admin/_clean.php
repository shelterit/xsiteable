<?php

class _clean extends _admin {

    function __construct ( $conf = array () ) {
        parent::__construct ( $conf ) ;
    }

    function this_action () {

        $this->load () ;

        $tr = new html2text ();

        $id = $this->glob->breakdown->id ;

        $filter = trim ( $this->glob->breakdown->id ) ;
        echo "<h1>Filter: '{$filter}'</h1>" ;

        foreach ( $this->docs_repo as $idx => $file ) {
/*
            $uid = $file['uid'] ;
            if ( $id != '' ) {
                if ( $id !== $uid )
                    break ;
            }
 * 
 */
            // if ( $counter++ > 10 ) break ;
             $test = true ;
                
             if ( $filter !== '' ) {
                 $test = stristr ( $file['original'], $filter ) ;
                 // echo "[".$file['original']."] [".$filter."] = [".var_export($test,true)."]<br>" ;
             }
             
             if ( $test ) {

                echo "<pre style='background-color:yellow;'> [$idx] " . print_r ( $file['original'], true ) . "</pre>";

                // Eat the file as a text stream
                $co = file_get_contents( $file['html'] ) ;

                echo "<pre style='background-color:gray;'> " . strlen ( $co ) . "</pre>";

                echo "[bgcolor" ; $co = str_replace ( 'bgcolor="#A0A0A0"', 'bgcolor="#efefff"', $co, $count ) ; echo "] " ;
                echo "[body" ; $co = str_replace ( '<BODY', '<body', $co, $count ) ; echo "] " ;
                echo "[/body" ; $co = str_replace ( '</BODY>', '</body><link rel="stylesheet" href="/wc/pdf.css?v=1"> ', $co, $count ) ; echo "] " ;

                echo "[save_html" ; file_put_contents($file['html'], $co); echo "] " ;

                echo "[nbsp" ; $co = str_replace( array ('&nbsp;', '$', 'â'), ' ', $co, $count ) ; echo "] " ;


                // convert HTML to text
                echo "[html2txt" ; $tr->set_html($co);
                $line = $tr->get_text(); echo "] " ;
                if ( trim ( $line ) == '' )
                    echo "<h1 style='color:red'>AAAAARGH! No content!</h1><p>Permission problems, or content protection (DRM)?</p>" ;
                echo "] " ;

                echo "[misc" ; $w = '' ;
                for ( $n=0; $n<strlen($line); $n++ ) {
                    $q = ord ( $line[$n] ) ;
                    if ( ( $q > 64 && $q < 91 ) || ( $q > 96 && $q < 123 ) || ( $q > 47 && $q < 58 ) || $q == 32 || $q == 13 )
                        $w .= $line[$n] ;
                }
                $line = $w ;


                echo "[explode" ; $all = explode ( ' ', $line ) ; echo "] " ;
                $r = '' ;

                foreach ( $all as $l ) {
                    $l = trim ( $l ) ;
                    if ( strlen ( $l ) > 1 )
                        $r .= $l . ' ' ;
                }

                echo "[save_txt" ; file_put_contents($file['txt'], $r ) ; echo "] " ;
                // file_put_contents($file['txt'].'.txt', $r ) ;

                // echo 'removed_blanks=['.count($count)."]ones=[$c1]twos=[$c2]numbers=[$c3]weird=[$c4] \n" ;
                echo 'apparent_words=['.count($all)."] \n" ;
                echo 'apparent_words_cleaned=['.count(explode(' ',$r))."] \n" ;

                $this->docs_repo[$idx]['_cleaned'] = $file['_copied'] ;
                my_flush();
             }
        }

        echo "<h1>Okay, done!</h1>" ;

        $this->save () ;
        
        die() ;

    }

}
