<?php

   
class xs_widget_data_control_properties extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'Data:properties widget',
        'description' => 'Data property control widgety',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'data',
    );

    // Widget settings (default values, might be manually overridden)
    // $settings denote the appearance of the widget, the outer skin, if you like
    public $settings = array (
        'title' => 'Properties',
        'style' => 'min-height:400px;',
        'color' => 'color-green',
        // 'class' => array ( 'color-blue' ),
    ) ;

    private $prop_html = '' ;

    function GET_content () {

        $id = $this->glob->breakdown->id ;

        // get assocs for this id

        $topics = $this->glob->tm->query ( array ( 'id' => $id ), true ) ;

        $props = $this->glob->tm->pick_out ( $topics[$id] ) ;

        // print_r ( $topics ) ; print_r ( $props ) ;
        
        $arr = array () ;

        foreach ( $props as $idx => $value ) {
            // $this->prop_html .= "<tr><td>$idx</td><td>$value</td></tr>" ;
            $arr[$idx] = $value ;
            // echo "$idx : $value <br>\r\n" ;
        }
        
        // $this->glob->stack->add ( 'xs_props', $arr ) ;

        // print_r ( $topic ) ;

        $x = '<form id="xs-tm-props-form" xmlns:nut="http://schema.shelter.nu/nut"
                    xmlns:form="http://schema.shelter.nu/nut-form"
                    xmlns:f="http://schema.shelter.nu/nut-formy"
                    action="{$dir/home}/_tm/topic/{$rest/id}" method="post">
            <input type="hidden" name="f:id" value="{$item/id}" />
            <ul id="xs-tm-props" style="list-style-type:none;padding:0;margin:0;">' ;
        
        foreach ( $arr as $idx => $value ) 
            $x .= "<li style='padding:0;margin:0;margin-bottom:10px;'><b>{$idx}</b> 
                    <a href='#' onclick='xs_delete_prop(\"{$idx}\");return false;' style='float:right;'>x</a>
                      <textarea name='f:{$idx}' style='width:97%;height:50px;'>".htmlentities($value)."</textarea>
                   </li>" ;
        
        $x .= '</ul><ul style="list-style-type:none;padding:0;margin:0;"><li style="float:left;"><input type="submit" value="Save!" style="background-color:#afa;" /> </li>
            <li style="float:left;"><button type="button" onclick="xs_add_prop();">Add</button> </li>
            </ul></form>' ;
        
        // var_dump ( $x ) ;
        
        return $this->prepare ( $x ) ; 
    }


    
}
