<?php

// require_once ( XS_DIR_APP . '/classes/TmSql.class.php' ) ;
    
class xs_widget_bookmark extends xs_Action_Widget_Controller {

    public $meta = array(
        'name' => 'bookmark widget',
        'description' => 'Adds simple bookmarking to any resource',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'functionality',
    );

    public $properties = array (
        'title' => 'Bookmark'
    ) ;

    private $widget_uri = '_api/widgets/bookmark' ;

    function __construct () {
        // Always use parent constructor to set things up
        parent::__construct();

        // Register a resource for this widget to respond to
        $this->_register_resource ( XS_WIDGET, $this->widget_uri ) ;
    }
    
    function ___register_functionality_zz () {
        $this->_register_functionality ( 'Bookmark!', 'page:bookmark', 'role:editor' ) ;
        $this->_register_functionality ( 'sd', 'control', 'role:editor' ) ;
        $this->_register_functionality ( 'sd', 'control:*', 'role:editor' ) ;
        $this->_register_functionality ( 'sd', 'control:owner', 'role:editor' ) ;
        $this->_register_functionality ( 'sd', 'version', 'role:editor' ) ;
    }

    function ___settings () {
            // $this->db = new db_helper ( $this->glob->db ) ;
    }
    
    function ___view () {
        $menu = $this->_get_resource ( '_api/gui/menu' ) ;
        $menu->POST ( 
            array ( 
                'page' => array ( 
                    'uri' => 'bookmark', 
                    'label' => 'Bookmark!',
                    'path' => 'ajax',
                    'uid' => 'uid' . rand ( 10, 100000 )
                ) 
            )
        ) ;
    }

    function getTitle () { return "Boo!" ; }
    function getControls () { return "V X" ; }
    function getMenu () { return "<ul><li>1</li><li>2</li><li>3</li><li>4</li></ul>" ; }
    function getContent () { return "Stuff here!" ; }

    function GET () {
        
        // print_r ( $this->glob->db ) ;
        $db = new TmSql ( $this->glob->db ) ;

        $menu = "" ;
        $id = $this->glob->request->__fetch ( 'id', '' ) ;

        // TODO : Should be rewritten to use access rules instead of a group name
        if ( $this->glob->user->inGroup ( 'some_group' ) ) {
            $menu = "<a onclick='ajax_get()' href='".$this->glob->config['website']['uri'].$this->widget."'>admin</a>" ;
            // echo $id ;
        }


        switch ( $this->glob->request->__fetch ( 'action', 'list' ) ) {

            case 'list' :

                switch ( $this->glob->request->__fetch ( 'user', 'root' ) ) {

                    case 'root' :
                        
                        $query = $db->query ( array (
                            'type'      => __BOOKMARK,
                            'sort_by'   => 'm_p_date DESC',
                        ) ) ;

                        echo $menu ;

                        break ;

                    default :

                        break ;
                }

                break ;

            case 'admin' :

                echo $menu ;
                echo "<textarea name='' style='width:auto;width:100%;height:300px;'>" ;
                echo "http://somewhere.com/ = Some external place" ;
                echo "</textarea> <button>Save!</button>" ;
                break ;

            case 'button' :

                break ;

        }
        /*
        $r = $this->glob->request->__fetch ( 'status', 'false' ) ;
        // echo "[$r] " ;
        switch ( $r ) {
            case 'true' : echo "<button class='ui-state-highlight ui-icon-pin-s' onclick=\"rep('?status=false');\">Favorite!</button>" ; break ;
            case 'false' : echo "<button onclick=\"rep('?status=true');\">Mark as favorite</button>" ; break ;
        }
         * 
         */
        
    }

}
