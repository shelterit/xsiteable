<?php
  
class _spider extends _admin {

    function __construct ( $conf = array () ) {
        parent::__construct ( $conf ) ;
    }

    function find_status ( $files ) {

        $status = array () ;
        $old = $new = array () ;

        foreach ( $this->docs as $idx => $item ) {
            if ( !isset ( $old[$item['original']] ) )
                $old[$item['original']] = $idx ;
            else
                $this->docs[$idx]['duplicate'] = $old[$item['original']] ;
        }

        foreach ( $files as $file => $stat ) {
            $status[$file] = true ;
            if ( isset ( $old[$file] ) ) {
                $status[$file] = $old[$file] ;
                unset ( $old[$file] ) ;
            }
            // print_r ( $stat['mtime'] ) ;
        }

        foreach ( $old as $idx => $item )
            $status[$idx] = false ;

        return $status ;
    }

    function find_duplicates () {
        $dup = array () ;
        foreach ( $this->docs as $idx => $value )
                if ( isset ( $value['duplicate'] ) )
                    $dup[$value['original']][$idx] = $value['duplicate'] ;
        return $dup ;
    }

    function find_hash ( $path ) {
        foreach ( $this->docs as $idx => $doc )
                if ( isset ( $doc['original'] ) )
                    if ( $doc['original'] == $path )
                        return $idx ;
        return 'NOT FOUND' ;
    }

    function this_action () {

        
        
        

        $debug = false ;
        
        $filter = trim ( $this->glob->breakdown->id ) ;
        echo "<h1>Filter: '{$filter}'</h1>" ;
        
        echo "summary<br>----------<br>from=[$this->source] <br>" ;
        echo "to=[$this->target] <br>" ;
        echo "browsable=[$this->web] <br>" ;

        $this->docs = array () ;
        
        $this->load () ;

        $deleted = $old = $created = 0 ;

        $files = $this->process_dir ( $this->source, true ) ;

        // $debug = false ;
        
        if ( $debug ) $files = array_slice ( $files, 0, 15) ;

        $this->glob->log->add ( 'spider : status quo' ) ;

        $status = $this->find_status ( $files ) ;

        $duplicates = $this->find_duplicates () ;

        $this->glob->log->add ( 'spider : duplicates' ) ;

        echo "found_files=[".count($files)."] found_status=[".count($status)."] found_duplicates=[".count($duplicates)."] <br><br><hr><br>" ;

        // echo "<pre>[" ; print_r ( $status ) ; echo "]</pre>" ;
        // var_dump ( $this->docs ) ;
        
        foreach ( $duplicates as $file => $dupes ) {

            echo "File=[$file] deleting: <div style='padding:6px;border:solid 1px #aaa;'>" ;
            foreach ( $dupes as $record_id => $duped_id ) {
                if ( !$debug ) unset ( $this->docs[$record_id] ) ;
                echo "[$record_id] " ;
            }
            echo "</div> " ;

        }

        if ( $debug ) {
            // var_dump ( $files ) ; var_dump ( $status ) ; var_dump ( $duplicates ) ;
        }

        $this->glob->log->add ( 'spider : duplicates cleaned' ) ;


        $counter = 0 ;

        foreach ( $status as $file => $todo ) {

            // if ( $counter++ > 10 ) break ;

            echo "<hr/><span style='color:red;'>$todo</span> " ;
            $record = array () ;
            $uid = null ;
            $choice = null ;

            if ( $todo === true ) { 
                $choice = 'create' ;
                print_r ( $file ) ;
            }
            else if ( $todo == false )
                $choice = 'delete' ;
            else
                $choice = 'find' ;

            
            switch ( $choice ) {
                case 'delete' : $uid = $this->find_hash ( $file ) ;  break ;
                case 'create' : $uid = md5 ( uniqid () ) ; break ;
                case 'find'   : $uid = $todo ; break ;
            }

            $record['uid'] = $uid ;

            // First, the file path to the original PDF document
            $record['original'] = $_f = $file ;
            $record['fileinfo'] = pathinfo ( $_f ) ;
            $record['stat'] = stat ( $_f ) ;
            $record['size'] = human_filesize ( $record['stat']['size'], 1 ) ;
            $record['timestamp'] = $record['stat']['mtime'] ;
            $record['extension'] = $record['fileinfo']['extension'] ;

            $filename = $record['fileinfo']['basename'] ;
            $extension = $record['fileinfo']['extension'] ;
            
            // Then we need new filenames to where the HTML and TXT versions go
            /*
            $record['item']  = $_p = $this->target . "/$uid." . $extension ;
            $record['pdf']  = $this->target . "/$uid.pdf" ;
            $record['html'] = $_h = $this->target . "/$uid.html" ;
            $record['txt']  = $_t = $this->target . "/$uid.txt" ;

            $record['item_web']  = $this->web . "/$uid." . $extension ;
            $record['pdf_web']  = $this->web . "/$uid.pdf" ;
            $record['html_web'] = $this->web . "/$uid.html" ;
            $record['txt_web']  = $this->web . "/$uid.txt" ;
            */
            $f = trim ( substr ( $file, strlen ( $this->source ) + 1 ) ) ;
            $f = trim ( substr ( $f, 0, strlen ( $f ) - 4 ) ) ;

            $record['small'] = $f ;

            echo "<div style='padding:5px;margin-bottom:15px;'>[<b style='font-size:1.2em;'>$extension</b>] $filename ($todo) ($choice)</div> \n" ;

            if ( $choice == 'delete' ) {

                // Delete some old file
                $this->docs[$uid] = null ;
                if ( !$debug ) unset ( $this->docs[$uid] ) ;

                echo "<pre style='background-color:#efd;padding:2px 4px;margin:0;width:auto;'>deleted $uid</pre>" ;
                $deleted++ ;

            } else {

                // Create or find

                // $record['bash'] = $bash = "cp \"$_f\" \"$_p\" ; pdftohtml -noframes -l 5 -c \"$_p\" \"$_h\"" ;

                // echo "<pre style='background-color:#efd;padding:2px 4px;margin:0;width:auto;'>$bash</pre>" ;
/*
                // echo "<pre style='padding:2px 4px;margin:0;width:auto;'>".print_r ( $record )."</pre>" ;

                $retval = $last_line = null ;

                if ( !$debug ) $last_line = exec ( $bash, $retval ) ;
                if ( !$debug ) usleep(200);

                echo "<div style='border:solid 1px #ccc;color:#777;font-size:10px;margin:5px 0;padding:4px;margin-bottom:15px;'>" ;
                var_dump ( $retval ) ;
                var_dump ( $last_line ) ;
                
                if ( trim ( $last_line ) == '' ) {
                    echo "<h1 style='background-color:red;'>Error converting PDF to HTML</h1> " ;
                    if ( !$debug ) $this->docs[$uid] = null ;
                    if ( !$debug ) unset ( $this->docs[$uid] ) ;
                    $deleted++ ;

                } else {
                    echo "<h1 style='background-color:green;'>$last_line</h1> " ;

                    if ( is_array ( $retval ) )
                        foreach ( $retval as $rec )
                            echo "$rec " ;

                     if ( $choice == 'create' ) $created++ ;
                     else                       $old++ ;

                    
                }
                */
                
                if ( isset ( $this->docs_repo[$uid] ) && isset ( $this->docs_repo[$uid]['_copied'] ) ) {
                    // $old_date = $this->docs_repo[$uid]['timestamp'] ;
                    // $new_date = $record['timestamp'] ;
                    // $diff = $new_date - $old_date ;
                    // echo "old=[$old_date] new=[$new_date] diff=[$diff]  " ;
                    // $this->docs_repo[$uid]['_spidered'] = $new_date ;
                    // $this->docs_repo[$uid]['_converted'] = false ;
                } else {
                    echo "{NO OLD RECORD} " ;
                    // $this->docs_repo[$uid]['_spidered'] = $new_date ;
                }
                
                if ( $choice == 'create' || $choice == 'find' ) {
                    $this->docs[$uid] = $record ;
                    $this->docs_repo[$uid]['uid'] = $uid ;
                    $this->docs_repo[$uid]['original'] = $record['original'] ;
                    $this->docs_repo[$uid]['filename'] = $filename ;
                    $this->docs_repo[$uid]['extension'] = $extension ;
                    $this->docs_repo[$uid]['small'] = $f ;
                    $this->docs_repo[$uid]['_spidered'] = $record['timestamp'] ;
                }
                echo "</div>";

            }

            my_flush() ;
        }

        // $this->glob->log->add ( 'spider : finished loop' ) ;

        $this->glob->log->add ( 'spider : save data' ) ;
        if ( !$debug ) $this->save () ;

        // $this->glob->log->add ( 'spider : load data' ) ;
        // $this->load () ;

        echo "created=[$created] old_kept=[$old] deleted=[$deleted] " ;

        // echo "<pre style='background-color:#edf;padding:2px 4px;margin:0;width:auto;'>".print_r ( $this->docs, true )."</pre>" ;

        // print_r ( $this->docs ) ;

        echo "<h1>Okay, done!</h1>" ;
        $this->glob->log->add ( 'spider : all done' ) ;

        // echo $this->glob->log->report() ;
        die() ;
    }

}
