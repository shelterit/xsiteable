<?php

/*
 * This widget displays a table with users (from the database as well as from
 * the configuration file), and offer a way to set security groups and various
 * bits of meta data.
 * 
 */

class xs_widget_user_manager extends xs_Action_Widget_Controller {

    public $meta = array (
        'name' => 'User Manager widget',
        'description' => 'Admin widget for dealing with users',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'admin'
    ) ;
    
    public $settings = array (
        'title' => 'User Manager widget',
        'style' => 'min-height:500px;',
        'color' => 'color-red',
    ) ;

    private $html = null ;
    private $users = array () ;

    function ___this () {

        // We could use some help around here
        $this->html = new html_helper () ;

        // This query is all the users
        $this->users = $this->glob->data->register_query (

           // identifier for what data connection to use
           'xs',

           // identifier for our query
           'users-all',

            array (
            'select'      => 'id,name,label',
            'type'        => $this->_type->_user,
            'sort_by'     => 'label DESC',
            // 'lookup_name' => 'm_p_who,m_u_who',
            ),

           // the timespan of caching the result
           '+1 second'
        ) ;

    }
    
    // GET (this function) responds to individual AJAX calls to the Widget's API

    function GET () {

        // make sure we use all the stuff initialized when the widget is active,
        // also when we are getting stuff directly from the resource
        
        $this->___this () ;
    
        // check if we're to output the data in some other format
        $output = $this->glob->request->_output ;

        // renderer
        $render = html_helper::RENDER_HTML ;
        
        // table configuration options
        $conf = array ( 
            '_export' => array ( 'user_manager', '', $this->glob->dir->api ),
            'id'  =>  $this->html->create_link ( $this->glob->dir->home.'/_tm/topic', '[id]'  ),

        ) ;
        
        
        $data = $this->glob->data->get ( 'users-all' ) ;
        
        $data = $this->html->_render_table ( $data, $conf, $render ) ;
        
        echo $data ;
        echo " <script>oTable = $('#".$this->html->id."').dataTable({'sScrollY': '400px','bPaginate': false,'bJQueryUI': true,'sPaginationType': 'two_button' });</script>" ;
        
    }

    // Get the widget initial content
    function GET_content () {
        
        if ( $this->html == null )
            $this->___this () ;
        
        $menu = array ( 'index' => 'All users' ) ;

        return $this->prepare ( 
           $this->html->create_simple_widget ( 'user_manager',  $menu ) 
        ) ;

    }

}