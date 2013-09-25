<?php
    
    // Include the basic xSiteable framework
    require_once ( '_include.php' ) ;
    
    if ( isset ( $_REQUEST['through'] ) ) {
        echo "Through!" ;
        die () ;
    }
    
    // var_dump ( $_SERVER ) ;
    
    $sn = $_SERVER['SERVER_NAME'] ;
    $self = $_SERVER['PHP_SELF'] ;
    $chop = substr ( $self, 0, -9 ) ;
    
    $base_uri = 'http://' . $sn . $chop ;
    
    // echo "<hr> [$sn] [$self] [$chop] = [$base_uri] <hr>" ;
    
    echo "<h2>Welcome!</h2> <p>Welcome to the xSiteable install testing script, which runs the first time to make sure you've got all the bits and bobs installed and configured.</p>" ;
    echo "<p>What follows is a set of points that will test items of interest, and tell you if you've got them right. Ok, here we go;</p>" ;
    
    $xs_installed = true ;
    $versions = array () ;
    
    $php = $xsl = $pdo = $tidy = $json = $rewrite = false ;
    
    foreach (get_loaded_extensions() as $ext) 
        $versions[strtolower($ext)] = phpversion($ext) ;
    
    echo "<li>PHP needs to be at least version 5.2 : " ;
    if ( (int) substr ( $versions['core'], 0, 1) > 4 ) {
        if ( (int) substr ( $versions['core'], 2, 1) > 1 ) {
            $php = true ;
            echo "Yes, version <b style='color:green'>" . $versions['core']."</b>" ;
        } else { echo "<b style='color:red'>Nope</b>." ; $xs_installed = false ; }
    } else { echo "<b style='color:red'>Nope</b>." ; $xs_installed = false ; }
    echo "</li>\n" ;
    
    echo "<li>Tidy (HTML cleanup module) installed : " ;
    if ( (int) substr ( $versions['tidy'], 0, 1) > 0 ) {
        $tidy = true ;
        echo "Yes, version <b style='color:green'>" . $versions['tidy']."</b>" ;
    } else { echo "<b style='color:red'>Nope</b>." ; $xs_installed = false ; }
    echo "</li>\n" ;
    
    echo "<li>XSLT installed : " ;
    if ( isset ( $versions['xsl'] ) ) {
        $xsl = true ;
        echo "Yes, version <b style='color:green'>" . $versions['xsl']."</b>" ;
    } else { echo "<b style='color:red'>Nope</b>." ; $xs_installed = false ; }
    echo "</li>\n" ;
    
    echo "<li>JSON installed : " ;
    if ( isset ( $versions['json'] ) ) {
        $json = true ;
        echo "<b style='color:green'>Ok.</b>" ;
    } else { echo "<b style='color:red'>Nope</b>." ; $xs_installed = false ; }
    echo "</li>\n" ;
    
    echo "<li>PDO installed : " ;
    if ( (int) substr ( $versions['pdo'], 0, 1) > 0 ) {
        $pdo = true ;
        echo "Yes, version <b style='color:green'>" . $versions['pdo']."</b><br>" ;
        echo "<div style='border:solid 1px #999;padding:8px;margin-left:30px;'>" ;
        echo "<p>If you want to, we can try your main database connection here. The DSN string has the following format;<br>" ;
        echo "<code>'[driver]:host=[host];dbname=[database]'</code>, so for example: <code>'mysql:host=127.0.0.1;dbname=intranet'</code>.<br>" ;
        echo "xSiteable uses at least one database which you can call whatever you want, but 'intranet' is the one used in the example above. If this database doesn't exist in the database, it needs to be created, and make sure the user account you use for it has reand and write access, and 'CREATE' access as well for the schema filling (unless you want to do this yourself; look at the docos).</p>" ;
        
        $d = isset ( $_REQUEST['dsn'] ) ? $_REQUEST['dsn'] : '' ;
        $u = isset ( $_REQUEST['un'] ) ? $_REQUEST['un'] : '' ;
        $p = isset ( $_REQUEST['pw'] ) ? $_REQUEST['pw'] : '' ;
        
        echo "<form action='".$base_uri."' method='get'>" ;
        echo "<br>DSN: <input value='$d' name='dsn' style='width:500px;' />" ;
        echo "<br>Username: <input value='$u' name='un' />" ;
        echo "<br>Password: <input value='$p' name='pw' />" ;
        echo "<br><input type='submit' value='Try it!' />" ;
        echo "</form>" ;
        
        if ( isset ( $_REQUEST['dsn'] ) && isset ( $_REQUEST['un'] ) && isset ( $_REQUEST['pw'] ) ) {
            
            $test = new PDO ( $_REQUEST['dsn'], $_REQUEST['un'], $_REQUEST['pw'] ) ; 

            try {
                $test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $result = $test->exec ("SHOW TABLES;");
                echo "<b style='color:green'>Seems Ok. Yay!</b><br> " ;
            } catch (PDOException $e) {
                echo "Oops! Some problem, although, mind you, I used MySQL 'show tables' which obviously don't work in other databases. Support for better varieties coming soon.<br> " ;
                var_dump ( $e ) ;
                if ($e->getCode == '2A000')
                    echo "Syntax Error: ".$e->getMessage();
            }            
            
        }
    
        echo "</div>" ;
        
    } else { echo "<b style='color:red'>Nope</b>." ; $xs_installed = false ; }
    echo "</li>\n" ;
    

    echo "<li>rewrite rules enabled or configured correctly: " ;
        
    $uri = $base_uri . 'admin/test?through=true' ;

    // echo "[$uri] " ;
    
    $test = file_get_contents ( $uri ) ;

    if ( trim ( $test ) !== 'Through!' ) {
        $xs_installed = false ;
        echo "<b style='color:red'>Nope</b>. If using Apache webserver, the .htaccess file provided may need tweaking of paths to make it go correctly. For any other, who knows? (Ie. support for other webservers and OSes will come over time)" ; $xs_installed = false ;
    } else {
        $rewrite = true ;
        echo "<b style='color:green'>Yes, looks good</b>." ;
    }
    echo "</li>\n" ;

    echo "<li>testing read and write access to some of the main directory structures : <ol>" ;

    $test = is__writable ( XS_DIR_CACHE ) ;
    $res = '['.XS_DIR_CACHE.'] is <b style="color:green">writeable</b>.' ;
    if ( $test == false ) { $res = '['.XS_DIR_CACHE.'] is <b style="color:red;">not writeable</b>.' ; $xs_installed = false ; }
    echo "<li>cache directory : $res</li>" ;
    
    $test = is__writable ( XS_DIR_LOG ) ;
    $res = '['.XS_DIR_LOG.'] is <b style="color:green">writeable</b>.' ;
    if ( $test == false ) { $res = '['.XS_DIR_LOG.'] is <b style="color:red;">not writeable</b>.' ; $xs_installed = false ; }
    echo "<li>log directory : $res</li>" ;
    
    $test = is__writable ( XS_DIR_DATASTORE ) ;
    $res = '['.XS_DIR_DATASTORE.'] is <b style="color:green">writeable</b>.' ;
    if ( $test == false ) { $res = '['.XS_DIR_DATASTORE.'] is <b style="color:red;">not writeable</b>.' ; $xs_installed = false ; }
    echo "<li>application/datastore directory : $res</li>" ;
    
    echo "</ol></li>\n" ;
    
    if ( ! $xs_installed ) {
        echo "Make sure the above directories are writeable by the web server; they provide us with caching, logging and storing indexes." ;
    }

    
    if ( ! $xs_installed ) {
        ?>
<html>
    <head><title>xSiteable not successfully installed!</title></head>
    <body>
        <h1>Oops!</h1>
        <p>Looks like your installation of xSiteable didn't go quite according to plan. Go through the list above to make sure all tests are Ok. Sorry about that.</p>
    </body>
</html>

<?php
    } else {
        ?>
<html>
    <head><title>xSiteable successfully installed!</title></head>
    <body>
        <h1>Yay!</h1>
        <p>Looks like your installation of xSiteable has come together fine. No guarantees that it all fits together, though, especially databases and the like (which has many sources of things that could go wrong. But apart from that ...</p>
        <p>Next, you can now safely a) remove the "require" line from the index.php file, and b) knock your self out in the application/configuration.ini file. Good luck.</p>
    </body>
</html>

<?php
    }
    
    
    
    
    function is__writable ( $path ) {
        
    //will work in despite of Windows ACLs bug
    //NOTE: use a trailing slash for folders!!!
    //see http://bugs.php.net/bug.php?id=27609
    //see http://bugs.php.net/bug.php?id=30931

        if ( $path{strlen($path)-1} == '/' ) // recursively return a temporary file path
            return is__writable ( $path.uniqid ( mt_rand() ).'.tmp' ) ;
        else if (is_dir ( $path ) )
            return is__writable ( $path.'/'.uniqid ( mt_rand() ).'.tmp' ) ;
        // check tmp file for read/write capabilities
        $rm = file_exists ( $path ) ;
        $f = @fopen ( $path, 'a' ) ;
        if ( $f === false )
            return false ;
        fclose($f);
        if ( !$rm )
            unlink ( $path ) ;
        return true ;
    }    
    
    