<?php

class _update extends _admin {

    function __construct ( $conf = array () ) {
        parent::__construct ( $conf ) ;
    }

    function this_action () {

        $this->load () ;

        // var_dump ( $this->glob->breakdown ) ;

        ?>
<html>
    <head>
        <title>update a document</title>
        <script src="/static/js/modernizr-1.5.min.js" type="text/javascript"></script>
        <script src="/static/js/jquery-1.4.4.min.js" type="text/javascript"></script>
        <script src="/static/js/script.js" type="text/javascript"></script>
    </head>
    <body>

<?php
        $id = $this->glob->breakdown->id ;

        if ( $id != '' ) {

            echo "<li><a href='/admin/update'>&lt;&lt; back to list</a></li>" ;

            echo "<div style='margin:20px;padding:20px;'>" ;

            echo "<table>" ;

            foreach ( $this->docs as $idx => $file ) {

                $uid = $file['uid'] ;

                if ( $uid == $id ) {

                    // var_dump ( $file ) ;
                    echo "<tr>" ;
                    echo "<td>".$file['fileinfo']['filename']."</td><td>" ; ?>

                    <button onclick="xs_redirect('/admin/spider/<?php echo $id; ?>','metawind')">Spider it</button>
                    <button onclick="xs_redirect('/admin/convert/<?php echo $id; ?>','metawind')">Convert it</button>
                    <button onclick="xs_redirect('/admin/clean/<?php echo $id; ?>','metawind')">Clean it</button>
                    <button onclick="xs_redirect('/admin/harvest/<?php echo $id; ?>','metawind')">Harvest it</button>

                    <?php

                    echo "</td></tr>" ;
                    
                }

            }

            echo "</table>" ;

            echo "</div>" ;

            ?>

   <iframe src="" id="metawind" width="700" height="500" style="border:solid 1px #fed;"></iframe>


<?php

        } else {

            echo "<table>" ;

            foreach ( $this->docs as $idx => $file ) {

                $uid = $file['uid'] ;

                // var_dump ( $file ) ;
                echo "<tr><td>".$file['fileinfo']['dirname']."</td>" ;
                echo "<td><a href='/admin/update/$uid'>".$file['fileinfo']['filename']."</a></td></tr>" ;

            }

            echo "</table>" ;
        }

        ?>
       </body>
</html>
<?php

        die () ;
    }
}
