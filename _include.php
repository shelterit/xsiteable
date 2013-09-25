<?php
    
    // Change these according to your run-time preference, but we always
    // prefer full reporting so we don't create code that ignore problems.
    // Switch off in production, of course, but if you've done your
    // error-checking properly even that shouldn't be needed. So there. :)

    // error_reporting(-1);
    ini_set("display_errors", 1);


    // Include the basic xSiteable framework
    require_once ( 'xs_framework.php' ) ;
    
