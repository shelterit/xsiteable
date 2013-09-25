<?php

   
class xs_widget_data_control_assocs extends xs_Action_Widget_Controller {

    // Generic metadata
    public $meta = array(
        'name' => 'Data:assocs widget',
        'description' => 'Data association control widgety',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'data',
    );

    // Widget settings (default values, might be manually overridden)
    // $settings denote the appearance of the widget, the outer skin, if you like
    public $settings = array (
        'title' => 'Associations',
        'style' => 'min-height:400px;',
        'color' => 'color-green',
        // 'class' => array ( 'color-blue' ),
    ) ;

    private $prop_html = '' ;

    function ___this () {

    }

    // Default output
    function GET_content () {
        
        $x = '' ;
        $id = $this->glob->breakdown->id ;
        
        $topics = $this->glob->tm->query ( array ( 'm_c_who' => $id, 'm_p_who' => $id ), true ) ;

        // $x .= count ( $topics ) ; 
        $arr = array () ;

        foreach ( $topics as $idx => $topic ) {
            $this->prop_html .= "<tr><td><a href='".$this->glob->dir->home."/_tm/topic/{$idx}'>$idx</a></td><td><a href='".$this->glob->dir->home."/_tm/topic/{$idx}'>".htmlentities($topic['label'])."</a></td></tr>" ;
            $arr[$idx] = $topic ;
            // echo "$idx : $value <br>\r\n" ;
        }
        
        // $this->glob->stack->add ( 'xs_props', $arr ) ;

        // print_r ( $topic ) ;
        
        $z = '<table id="tm-data-table" style="width:100%;">
                <thead><tr><th>Type</th><th>Value</th></tr></thead> <tbody>' . $this->prop_html . '</tbody>
            </table>
            <script> oTable = $("#tm-data-table").dataTable({ "bJQueryUI": true, "bPaginate": false, "bSearch": false, "bFilter": false,
        "bLengthChange": true });
            </script>  ' ;
        
        // $x .= '<ul style="list-style-type:none;padding:0;margin:0;">' ;
        // foreach ( $arr as $idx => $value ) 
            // $x .= "<li style='padding:0;margin:0;margin-bottom:10px;'><b>$idx:</b> <a href='".$this->glob->dir->home."/_tm/topic/{$idx}'>".$value['label']."</a></li>" ;
        // $x .= '</ul>' ;
        
        // var_dump ( $x ) ;
        
        return $this->prepare ( $z ) ; 
    }


    
}
