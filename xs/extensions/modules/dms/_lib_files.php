<?php
 
class dms_lib_files {
    
    // are we running in safe mode? (safe=no reaction)
    public $safe_mode = false ;
    
    private $glob = null ;
    
    private $rel = null ;
    
    private $base_folder = null ;
    
    private $source_path = null ;
    private $alt_source_paths = null ;
    
    function __construct ( $glob = null ) {
        $this->glob = $glob ;
        $this->base_folder = @$this->glob->config['dms']['destination_folder'] ;
        
        $this->source_path = @$this->glob->config['dms']['source_folder'] ;
        $this->alt_source_paths = @$this->glob->config['dms']['additional_source_folder'] ;
        
    }
    
    function is__writable ( $path ) {
        
    //will work in despite of Windows ACLs bug
    //NOTE: use a trailing slash for folders!!!
    //see http://bugs.php.net/bug.php?id=27609
    //see http://bugs.php.net/bug.php?id=30931

        if ( $path{strlen($path)-1} == '/' ) // recursively return a temporary file path
            return $this->is__writable ( $path.uniqid ( mt_rand() ).'.tmp' ) ;
        else if (is_dir ( $path ) )
            return $this->is__writable ( $path.'/'.uniqid ( mt_rand() ).'.tmp' ) ;
        // check tmp file for read/write capabilities
        $rm = file_exists ( $path ) ;
        $f = @fopen ( $path, 'a' ) ;
        if ( $f === false )
            return false;
        fclose($f);
        if ( !$rm )
            unlink ( $path ) ;
        return true;
    }    
    
    function can_write_to_dir ( $dir ) {
        
        $status = true ;
        
        if ( ! $this->is__writable ( $dir . '/' ) ) {
            $status = false ;
        }
        
        if ( $status )
            echo "<p>All write access accounted for. Let's go!" ;
        
        return $status ;
    }
    
  function spider ( $start_path = null ) {

      $ext = '*' ;
      
      if ( isset ( $this->glob->config['dms']['file_formats'] ) )
          $ext = $this->glob->config['dms']['file_formats'] ;
      
      $extensions = array_keyify ( explode ( ',', $ext ) ) ;
      
      if ( ! $start_path && isset ( $this->glob->config['dms']['source_folder'] ) )
          $start_path = $this->glob->config['dms']['source_folder'] ;
      
      return $this->process_dir ( 
         $start_path, 
         $extensions 
      ) ;
      
  }
  
  function process_dir ( $dir, $ext = array ( '*' => '*' ), $recursive = true ) {
      
        if ( is_dir ( $dir ) ) {

            for ( $list = array(), $handle = opendir ( $dir ) ; ( FALSE !== ( $file = readdir ( $handle ) ) ) ; ) {
                
                // echo "[$file] " ;
                
                if ( ( $file != '.' && $file != '..') && ( file_exists ( $path = $dir . '/' . $file ) ) ) {

                    if ( is_dir ( $path ) && ( $recursive ) ) {
                        $list = array_merge ( $list, $this->process_dir ( $path, $ext, TRUE ) ) ;
                    } else {
                        $f = pathinfo ( $dir . '/' . $file );
                        if ( isset ( $f['extension'] ) ) {
                            if ( isset ( $ext[trim($f['extension'])] ) ) {
                                $entry = $dir . '/' . $file ;
                                $list[$entry] = stat ( $entry ) ;
                                // echo '. ' ;
                            }
                        }
                    } 
                }
            }
            closedir($handle);
            return $list;
        }
        echo '<li>Error: ProcessDir ( $directory ) where $directory isn\'t a directory.</li>' ;
        return FALSE;
    }
        
    function get_dir_structure ( $filename, $base_folder = '', $create = false ) {
        
        if ( $base_folder === null ) {
            $base_folder = $this->base_folder ;
            // echo "**************************" ;
        }
        $size = strlen ( $filename ) ;
        $dest = $q = $w = '' ;
        
        if ( $size > 3 ) {
            $q = $w = '/' ;
            $q .= $filename[0] ;
            $q .= $filename[1] ;
            $w .= $filename[2] ;
            $w .= $filename[3] ;
            $dest = $q . $w ;
        }
        
        if ( $create ) {
            
            $l1 = $base_folder . $q ;
            if ( ! is_dir ( $l1 ) )
                mkdir ( $l1 ) ;
            
            $l2 = $base_folder . $q . $w ;
            if ( ! is_dir ( $l2 ) )
                mkdir ( $l2 ) ;
        }
        
        return $base_folder . $dest ;
    }

    function find_last_version ( $matches ) {
        $min = 0 ;
        if ( isset ( $matches ) && is_array ( $matches ) && count ( $matches ) > 0 )
            foreach ( $matches as $match ) {
                $tversion = (int) substr ( $match, strpos ( $match, '.' )+1, 5 ) ;
                if ( $tversion > $min )
                    $min = $tversion ;
            }
        return $min ;
    }
    
    function find_last_draft ( $matches ) {
        $min = 0 ;
        if ( isset ( $matches ) && is_array ( $matches ) && count ( $matches ) > 0 )
            foreach ( $matches as $match ) {
                $tversion = (int) substr ( $match, strrpos ( $match, '-' ) + 1, 4 ) ;
                if ( $tversion > $min )
                    $min = $tversion ;
            }
        return $min ;
    }
    
    function get_versions ( $doc ) {
        // debug ( $doc ) ;
        $test = $this->process_dir_versions ( $doc->path_dest, 
                $doc->uid.'.*.'. $doc->extension ) ;
        // debug_r ( $doc->uid.'.*.'. $doc->extension, $doc->final_path ) ;
        if ( ! is_array ( $test ) ) 
            return array () ;
        rsort ( $test ) ;
        return $test ;
    }
    
    function get_drafts ( $doc, $version ) {
        $draft_pattern = $doc->uid.'-draft.'.sprintf("%05s",($version)).'-*.'. $doc->extension ;
        $test = $this->process_dir_versions ( $doc->path_dest, $draft_pattern ) ;
        if ( ! is_array ( $test ) ) 
            return array () ;
        rsort ( $test ) ;
        return $test ;
    }
      
  function version_full_path ( $doc, $version = 0, $web_link = false ) {
      $s = $doc->final_path ;
      if ( $web_link !== false ) 
          $s = $web_link ;
      return sprintf ( "%s/%s.%05d.%s", $s, $doc->uid, $version, $doc->extension ) ;
  }

  function draft_full_path ( $doc, $version = 0, $draft = 0, $web_link = false ) {
      $s = $doc->final_path ;
      if ( $web_link !== false ) 
          $s = $web_link ;
      return sprintf ( "%s/%s-draft.%05d-%04s.%s", $s, $doc->uid, $version, $draft, $doc->extension ) ;
  }

  function process_dir_versions ( $dir, $pattern ) {
      
      // debug($dir . ' - ' . $pattern, "!!!!!!!!!!!!!!");
        if ( is_dir ( $dir ) ) {

            // echo "isdir=[$dir] " ;
            // debug('is_dir');
            $matches = array () ;
            
            for ( $list = array(), $handle = opendir ( $dir ) ; ( FALSE !== ( $file = readdir ( $handle ) ) ) ; ) {
                
            // debug($file,'is_dir');
                if ( ( $file != '.' && $file != '..') && ( file_exists ( $path = $dir . '/' . $file ) ) ) {

                    // echo "<pre style='background-color:yellow;'>" ;
                    
                    $f = pathinfo ( $dir . '/' . $file );
                    // $basename = $f['basename'] ;
                    // $pattern = $f['filename'] . '.*.' . $f['extension'] ;
                    
                    if ( fnmatch ( $pattern, $file ) )
                        $matches[] = $file ;

                }
            }
            closedir($handle);
            natsort ( $matches ) ;
            return $matches ;
        } else
            return FALSE;
  }
      
    
    function relative_path ( $file, $source ) {
        $f = trim ( substr ( $file, strlen ( $source ) + 1 ) ) ;
        $f = trim ( substr ( $f, 0, -4) ) ;
        // if ( rand ( 0, 10 ) == 5 ) debug_r ( array ( 'source' => $source, 'file' => $file, pathinfo ( $file ) ), ' ' ) ;
        return $f ;
    }
    
    function path_trace ( $path ) {
        // breaks apart a path, and creates an array, tracing through it as a structure
        $e = array_reverse ( explode ( '/', $path ) ) ;
        $paths = array () ;
        foreach ( $e as $c => $ee ) {
            $p = '' ;
            for ( $n=$c; $n>=0; $n--)
                $p .= $e[$n] . '/' ;
            $paths[] = substr ( $p, 0, -1 ) ;
        }
        return array_reverse ( $paths ) ; 
    }

    function array_lsearch ($str, $array ) { 
        $found = array () ;
        foreach ( $array as $k => $v )
            if ( ( $s = stripos ( $v, $str ) ) )
                $found[$v] = $s ; 
        asort ( $found ) ;
        // debug_r($array,'a_l: '.$str);
        // debug_r($found,'a_l: '.$str);
        return $found ;
    }
    
    function compare ( $this_str, $that_str ) {
        if ( strlen ( $this_str ) > strlen ( $that_str ) )
            return stripos ( $this_str, $that_str ) ;
        return stripos ( $that_str, $this_str ) ;
    }
    
    function get_source ( $path ) {
        $pos = $this->compare ( $path, $this->source_path ) ;
        if (  $pos !== false && $pos == 0 ) {
            return $this->source_path ;
        } else {
            if ( is_array ( $this->alt_source_paths ) ) {
                foreach ( $this->alt_source_paths as $source_path ) {
                    $pos = $this->compare ( $path, $source_path ) ;
                    if ( $pos !== false && $pos == 0 ) {
                        return $source_path ;
                    }
                }
            }
        }
        return null ;
    }
    

    
    function delete_duplicates ( $arr, $absolute = false ) {
        
        if ( ! is_array ( $arr ) )
            return ;
        
        // if ( $absolute ) echo "(checking for absolute_paths) " ;

        $delete = array () ;
        $rel = $source = null ;
        
        foreach ( $arr as $path => $topics ) {
            
            if ( $absolute ) {
                $source = $this->get_source ( $path ) ;

                if ( $source !== null )
                    $rel = $this->relative_path ( $path, $source ) ;
            }
            
            $count = 0 ;
            $first = null ;
            
            foreach ( $topics as $topic_id => $true ) {
                
               if ( $absolute && $source == null ) {
                  $delete[$topic_id] = null ;
                  continue ;
               }

                if ( $count++ == 0 ) {
                    $first = $topic_id ;
                } else {
                    $delete[$topic_id] = $first ;
                }
            }
        }
        
        if ( count ( $delete ) > 0 ) {
            // echo "<div> - deleting topics: " ;
            foreach ( $delete as $topic_id => $topic ) {
                // echo '['.$topic_id.'] ' ;
                if ( ! $this->safe_mode ) 
                    $this->glob->tm->delete ( $topic_id ) ;
            }
        }
        
        return $delete ;
    }


    function find_duplicates ( $arr ) {
        
        if ( ! is_array ( $arr ) )
            return array () ;
        
        $w = array () ;
        foreach ( $arr as $idx => $item )
            // if ( trim ( $idx ) != '' )
                $w[$idx] = $item['value'] ;
        // asort($w) ;
        $t = array_count_values($w) ;
        asort($t) ;
        $f = array () ;
        foreach ( $t as $idx => $val )
            if ( $val > 1 ) {
                $ids = array () ;
                foreach ( $arr as $x => $i )
                    if ( $i['value'] == $idx && trim ( $i['value'] ) != '' )
                        $ids[$x] = true ;
                ksort ( $ids ) ;
                $f[$idx] = $ids ;
            }
        return $f ;
    }
    
    
    
}
