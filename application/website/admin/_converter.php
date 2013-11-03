<?php
  
class _convert extends _admin {

    function __construct ( $conf = array () ) {
        parent::__construct ( $conf ) ;
    }

    function info () {
        echo "<h2>DB info</h2>" ;
        $count = count ( $this->docs ) ;
        echo "count=[$count] <br><hr><br> " ;
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

        foreach ( $files as $file => $size ) {
            $status[$file] = true ;
            if ( isset ( $old[$file] ) ) {
                $status[$file] = $old[$file] ;
                unset ( $old[$file] ) ;
            }
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

        $dm = new \xs\DocumentManager () ;

        
        
        $debug = false ;
        
        $this->docs = array () ;
        
        $this->load () ;

        $filter = trim ( $this->glob->breakdown->id ) ;
        $base_folder = $this->glob->config['dms']['destination_folder'] ;
        
        echo "<h1>Filter: '{$filter}'</h1>" ;

        $counter = 0 ;
        
        // echo "<pre>" ; print_r ( $this->docs_repo ) ; echo "</pre>" ; die () ;

        foreach ( $this->docs_repo as $uid => $file ) {

             // if ( $counter++ > 10 ) break ;
             
             $test = true ;
                
             if ( $filter !== '' && $filter !== '!' ) {
                 $test = stristr ( $file['original'], $filter ) ;
                 // echo "[".$file['original']."] [".$filter."] = [".var_export($test,true)."]<br>" ;
             }
             
            $old_date = $new_date = 0 ;
            
            if ( isset ( $file['_copied'] ) )
                $old_date = $file['_copied'] ;
            
            if ( isset ( $file['_spidered'] ) )
                $new_date = $file['_spidered'] ;
            
            $diff = $new_date - $old_date ;
                 
             if ( ( $test && $diff > 100 ) || $filter == '!' ) {
                 
                // var_dump ( $file ) ;
                echo "<hr/>[".$file['original']."] [".$uid."] \n<br/>" ;
                echo "old=[$old_date] new=[$new_date] diff=[$diff]  " ;
                
                $retval = $last_line = null ;

                if ( !$debug ) {
                    
                    // $source = $file['original'] ;
                    /*
                    $dest = $file['pdf'] ;
                    if ( isset ( $file['item'] ) )
                        $dest = $file['item'] ;
                    */
                    // first, copy files

                    $filename = $file['uid'] . '.' . $file['extension'] ;
                    
                    $additional_folders = $dm->get_dir_structure ( $filename, '' ) ;
                    $base_uri = $this->glob->config['dms']['destination_uri'] ;
                    
                    $dm->archive_copy_file ( 
                        $file['original'],
                        $filename,
                        $base_folder
                    ) ;
                    
                    $path = $base_folder . $additional_folders ;
                    $item = $path .'/'. $filename ;
                    $html = $path .'/'. $file['uid'] . '.html' ;
                    $txt  = $path .'/'. $file['uid'] . '.txt' ;
                    $html_uri = $base_uri . $additional_folders .'/'. $file['uid'] . '.html' ;
                    
                    $this->docs_repo[$uid]['html'] = $html ;
                    $this->docs_repo[$uid]['txt'] = $txt ;
                    
                    // $dest = $base_folder .'/'. $dm->get_dir_structure ( $filename, '' ) ;
                    
                    // $dest_html = $base_folder . $dm->get_dir_structure ( $uid, '' ) . '.html' ;
                    
                    // $html_file = $folder .'/'. $file['uid'] . '.' . $file['extension'] ;
                    // echo "*( $item - $html )* " ;
                    
                    // $this->docs_repo[$uid]['destination'] = $dm->get_dir_structure (
                    //     $filename,
                    //     $base_folder
                    // ) ;
                    
                    $this->docs_repo[$uid]['_copied'] = $file['_spidered'] ;
                    
                    $extension = $file['extension'] ;
                    // $to_path   = $file['filename'] ;
                    
                    // $f = pathinfo ( $dest ) ;
                    // if ( isset ( $f['dirname'] ) )
                    //     $to_path = $f['dirname'] ;
                    // $uid = $file['uid'] ;
                    $bash = '' ;
                    
                    // fetch procedure for conversion
                    if ( isset ( $this->glob->config['dms'][$extension.'.create_html'] ) )
                        $bash = $this->glob->config['dms'][$extension.'.create_html'] ;
                    
                    if ( trim ( $bash ) != '' ) {
                        // convert to HTML

                        $bash = str_replace ( '{$from}', $item, $bash ) ;
                        $bash = str_replace ( '{$from_link}', $html_uri, $bash ) ;
                        $bash = str_replace ( '{$to}', $html, $bash ) ;
                        $bash = str_replace ( '{$to_path}', $path, $bash ) ;
                        $bash = str_replace ( '{$uid}', $uid, $bash ) ;

                        $a = trim ( substr ( strtolower ( $bash ), 0, 4 ) ) ;

                        echo "<pre style='background-color:yellow;'> $bash </pre>" ;

                        my_flush() ;
                        if ( $a == 'php:' ) {
                            echo "<pre style='background-color:red;'>".substr ( $bash, 4 )."</pre>" ;

                            eval ( substr ( $bash, 4 ) . ';' ) ;

                        } else {
                                $last_line = exec ( $bash, $retval ) ;
                                $this->docs_repo[$uid]['_converted'] = $file['_spidered'] ;
                                // $file['_converted'] = true ;
                                // $this->docs_repo[$uid]['timestamp'] = $this->docs[$uid]['timestamp'] ;
                        }
                    
                     }
                }
                if ( !$debug ) usleep(10);

                echo "<div style='border:solid 1px #ccc;color:#777;font-size:10px;margin:5px 0;padding:4px;margin-bottom:15px;'>" ;
                var_dump ( $retval ) ;
                var_dump ( $last_line ) ;
                my_flush() ;
                /*
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

                    if ( !$debug ) $this->docs[$uid] = $record ;
                }
                */
                echo "</div>";
             }

        }

        // $this->glob->log->add ( 'spider : finished loop' ) ;

        $this->glob->log->add ( 'converter : save data' ) ;
        if ( !$debug ) $this->save () ;

        // $this->glob->log->add ( 'spider : load data' ) ;
        // $this->load () ;

        // echo "created=[$created] old_kept=[$old] deleted=[$deleted] " ;

        // echo "<pre style='background-color:#edf;padding:2px 4px;margin:0;width:auto;'>".print_r ( $this->docs, true )."</pre>" ;

        // print_r ( $this->docs ) ;

        echo "<h1>Okay, done!</h1>" ;
        $this->glob->log->add ( 'convert : all done' ) ;

        // echo $this->glob->log->report() ;
        die() ;
    }

}
