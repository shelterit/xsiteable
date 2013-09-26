<?php

// require_once ( XS_DIR_APP . '/classes/datamodel.php' ) ;
require_once ( XS_DIR_LIB . '/spyc/spyc.php5' ) ;

require '_admin.php' ;
require '_spider.php' ;
require '_converter.php' ;
require '_clean.php' ;
require '_harvest.php' ;
require '_update.php' ;
require '_test.php' ;

class xs_action_instance extends xs_Action_Webpage {

    public $page = array(
        'title' => "Admin",
        'template' => XS_PAGE_AUTO,
    );

    function ___action () {

        // Set timeout to forever
        set_time_limit(0);

        $test = $this->glob->breakdown->section ;

        $conf = array (
            'source' => $this->glob->dir->docs_file_source,
            'target' => $this->glob->dir->docs_file_target,
            'web'    => $this->glob->dir->docs_web
        ) ;

        $this->title = 'Admin' ;


        // What's out input
        switch ( $test ) {

            case '' :
                // Do nothing

                break;

            case 'spider'  :

                $do = new _spider ( $conf ) ;
                $do->this_action () ;

                break ;

            case 'convert'  :

                $do = new _convert ( $conf ) ;
                $do->this_action () ;

                break ;

            case 'clean'   :

                $do = new _clean ( $conf ) ;
                $do->this_action () ;

                break ;

            case 'harvest' :

                $do = new _harvest ( $conf ) ;
                $do->this_action () ;

                break ;

            case 'update' :

                $do = new _update ( $conf ) ;
                $do->this_action () ;

                break ;

            case 'daily_process_health_safe' :
                $dm = $this->_get_module ( 'dms' ) ;
                $dm->health_check ( true ) ;
                die () ;
                break ;

            case 'daily_process_health' :
                $dm = $this->_get_module ( 'dms' ) ;
                $dm->health_check () ;
                die () ;
                break ;

            case 'daily_process_safe' :
                $dm = $this->_get_module ( 'dms' ) ;
                $dm->daily_process ( true ) ;
                die () ;
                break ;

            case 'daily_process' :
                $dm = $this->_get_module ( 'dms' ) ;
                $dm->daily_process () ;
                die () ;
                break ;

            case 'daily_process_forced_harvest' :

                $dm = $this->_get_module ( 'dms' ) ;
                
                // $dm->browse () ;
                $dm->forced_harvest_process () ;
                
                die () ;
                break ;

            case 'test' :

                $do = new _test ( $conf ) ;
                $do->action () ;

                break ;

            case 'db_create_delete' :

                $m = $this->glob->data->get_native_driver ( 'xs' ) ;
                
                var_dump ( $m ) ;

                $dm = new xs_TopicMaps_Datamodel ( $m ) ;

                file_put_contents ( XS_DIR_APP . '/datastore/_data_backup.sql', $dm->backupData ( XS_DIR_APP . '/datastore/_data_backup.sql' ) ) ;

                $dm->installModel ( true ) ;

                // ALTER TABLE  `xs_topic` CHANGE  `status`  `map` INT( 11 ) NULL DEFAULT NULL

                $dm->restoreData ( file_get_contents ( XS_DIR_APP . '/datastore/_data_backup.sql' ) ) ;

                die() ;

            case 'db_create' :

                $m = $this->glob->data->get_native_driver ( 'xs' ) ;

                var_dump ( $m ) ;
                
                $dm = new xs_TopicMaps_Datamodel ( $m ) ;

                file_put_contents ( XS_DIR_APP . '/datastore/_data_backup.sql', $dm->backupData ( XS_DIR_APP . '/datastore/_data_backup.sql' ) ) ;

                $dm->installModel ( false ) ;

                // ALTER TABLE  `xs_topic` CHANGE  `status`  `map` INT( 11 ) NULL DEFAULT NULL

                $dm->restoreData ( file_get_contents ( XS_DIR_APP . '/datastore/_data_backup.sql' ) ) ;

                die() ;

            case 'db_sql_dump' :

                $m = $this->glob->data->get_native_driver ( 'xs' ) ;

                $dm = new xs_TopicMaps_Datamodel ( $m ) ;
                $path = XS_DIR_APP . '/datastore/' . $this->glob->breakdown->id ;
                $dm->backupData ( $path  ) ;

                // debug_r ( $this->glob->breakdown ) ;
                echo "Ok; look in '{$path}'" ;
                // file_put_contents ( '_ontology.php', $ret ) ;

                die() ;

        }

    }

}


function hex_color( $c ){
    return sprintf("%02X%02X%02X", dechex($c), dechex($c), dechex($c) );
}