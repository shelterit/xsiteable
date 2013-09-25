<?php

class _admin extends xs_Action {

    public $source = null ;
    public $target = null ;
    public $web    = null ;

    public $docs = array () ;
    public $docs_repo = array () ;
    public $idx = array () ;


    function __construct ( $conf = array() ) {
        foreach ( $conf as $idx => $value )
            $this->$idx = $value ;
    }

    function action ( $what ) {

        // What's out input
        switch ( $what ) {

            case 'spider'  :

                echo '<META HTTP-EQUIV="Pragma" CONTENT="no-cache">' ;
                echo '<META HTTP-EQUIV="Expires" CONTENT="-1">' ;

                $func = new _spider () ;

                $func->action () ;

                die() ;

                break ;

            case 'convert'  :

                echo '<META HTTP-EQUIV="Pragma" CONTENT="no-cache">' ;
                echo '<META HTTP-EQUIV="Expires" CONTENT="-1">' ;

                $func = new _convert () ;

                $func->action () ;

                die() ;

                break ;

            case 'clean'   :

                echo '<META HTTP-EQUIV="Pragma" CONTENT="no-cache">' ;
                echo '<META HTTP-EQUIV="Expires" CONTENT="-1">' ;

                $func = new _spider () ;

                $func->action () ;

                die() ;

                break ;

            case 'harvest' :

                echo '<META HTTP-EQUIV="Pragma" CONTENT="no-cache">' ;
                echo '<META HTTP-EQUIV="Expires" CONTENT="-1">' ;

                $func = new _spider () ;

                $func->action () ;

                die() ;

                break ;

            case 'update' :

                echo '<META HTTP-EQUIV="Pragma" CONTENT="no-cache">' ;
                echo '<META HTTP-EQUIV="Expires" CONTENT="-1">' ;

                $func = new _update () ;

                $func->action () ;

                die() ;

                break ;

            case 'test' :

                echo '<META HTTP-EQUIV="Pragma" CONTENT="no-cache">' ;
                echo '<META HTTP-EQUIV="Expires" CONTENT="-1">' ;

                $func = new _test () ;

                $func->action () ;

                die() ;

                break ;
        }

    }

    function save () {
        file_put_contents ( 'application/datastore/cmd.arr', serialize ( $this->docs ) ) ;
        file_put_contents ( 'application/datastore/cmd_repo.arr', serialize ( $this->docs_repo ) ) ;
        echo "{saved}" ;
        $this->glob->log->add ( 'admin : save done' ) ;
    }

    function load () {
        $this->docs = unserialize ( file_get_contents ( 'application/datastore/cmd.arr' ) ) ;
        $this->docs_repo = @unserialize ( @file_get_contents ( 'application/datastore/cmd_repo.arr' ) ) ;
        echo "{loaded}" ;
        $this->glob->log->add ( 'admin : load done' ) ;
    }

    function save_index () {

        $i = 'str_serialize' ;
        $this->glob->log->add ( 'admin : save_index_'.$i ) ;

        $t = array () ;

        foreach ( $this->idx as $id => $idx )
            $t[substr ( $id, 0, 1 )][$id] = $idx ;

        foreach ( $t as $letter => $idx ) {
            // echo "<h1>[".ord($letter)."]</h1> <i style='font-size:7px;'>" ; print_r ( $idx ) ; echo "</i>" ;
            file_put_contents ( 'application/datastore/idx_'.$letter.'.array', serialize ( $idx ) ) ;
        }
        // echo "<pre>" ; print_r ( $t ) ; echo "</pre>" ;

        $this->glob->log->add ( 'admin : save_index done' ) ;
    }
    
    function load_all_index () {
        $all = 'abcdefghijklmnopqrstuvwxyz0123456789' ;
        for ( $n=0; $n<strlen($all); $n++ ) {
            $idx = $this->load_index ( $all[$n] ) ;
            $this->idx = array_merge ( $this->idx, $idx ) ;
        }
        // print_r ( $this->idx ) ;
    }

    function load_index ( $letter ) {

        $i = 'str_serialize' ;
        $idx = array () ;
        $this->glob->log->add ( 'admin : load_index_'.$letter ) ;
        // echo "<div>load_index_{$letter}</div>" ;
        $idx = unserialize ( @file_get_contents ( 'application/datastore/idx_'.$letter.'.array' ) ) ;
        $this->glob->log->add ( 'admin : load_index_'.$letter.' done.' ) ;

        // $this->glob->log->add ( 'admin : load_index done' ) ;

        return $idx ;
    }

  function process_dir ( $dir, $recursive = FALSE) {

      $c = 'pdf' ;
      
      if ( isset ( $this->glob->config['dms']['file_formats'] ) )
          $c = $this->glob->config['dms']['file_formats'] ;
      
      $formats = array_keyify ( explode ( ',', $c ) ) ;
      // echo "<pre>" ; print_r ( $formats ) ; echo "</pre>" ;
      
        if ( is_dir ( $dir ) ) {

            for ( $list = array(), $handle = opendir ( $dir ) ; ( FALSE !== ( $file = readdir ( $handle ) ) ) ; ) {

                if ( ( $file != '.' && $file != '..') && ( file_exists ( $path = $dir . '/' . $file ) ) ) {

                    if ( is_dir ( $path ) && ( $recursive ) ) {
                        $list = array_merge ( $list, $this->process_dir ( $path, TRUE ) ) ;
                    } else {
                        $f = pathinfo ( $dir . '/' . $file );
                        if ( isset ( $f['extension'] ) ) {
                            if ( isset ( $formats[trim($f['extension'])] ) ) {
                                $entry = $dir . '/' . $file ;
                                $list[$entry] = true ;
                            }
                        }
                    }
                    
                }
            }
            closedir($handle);
            return $list;
        } else
            return FALSE;
    }
}