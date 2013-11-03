<?php

class xs_widget_csv extends \xs\Action\WidgetController {

    public $meta = array (
        'name' => 'CSV Widget',
        'description' => 'Displays a table of data from CSV datasource',
        'version' => '1.0',
        'author' => 'David Macdonald',
        'author_link' => 'http://www.example.org/',
        'category' => 'data'
    ) ;
    
    public $settings = array (
        'title' => 'CSV table',
        'style' => 'min-height:300px;',
        'class' => 'color-orange',
    ) ;

    public $properties = array (
        'url' => 'index',
    ) ;

    // the data tokens used: if null it hasn't been initialized
    private $token = null ;

    private $query_id = null ;


    function ___init () {

        // We could use some help around here
        $this->html = new \xs\Gui\Html () ;
    }
    


    // Hook on to XS_DATASTORE to set up our database connection, plus inject
    // our most used SQL queries for handling, caching and off-loading

    function ___action () {
        
        $this->token = $this->glob->data->register_query (

           'csv', $this->query_id, null, '+1 second'

        ) ;

    }

    // GET (this function) responds to individual AJAX calls to the Widget's API

    function GET () {

        // check if we're to output the data in some other format
        //$output = $this->glob->request->_output ;

        // renderer
        $render = \xs\Gui\Html::RENDER_HTML ;

        $this->query_id = 'csv-generic-' . urlencode($this->properties['url']) ;

        if ( trim ( $this->properties['url'] ) == '' )
            $this->query_id = 'csv-generic-blank' ;

        $data   = $this->glob->data->get ( $this->query_id );

        $table = $this->html->_render_table ( $data, Array(), $render ) ;

       echo " [".$this->query_id."] " . $table ;
       echo " <script>oTable = $('#".$this->html->id."').dataTable({'sScrollY': '400px','bPaginate': false,'bJQueryUI': true,'sPaginationType': 'two_button' });</script>" ;

    }


    // Get the widget initial content
    function GET_content () {

        return $this->prepare ( $this->html->create_simple_widget ( 'csv', array ( 'index' => 'Home' ) ) ) ;

    }
}
