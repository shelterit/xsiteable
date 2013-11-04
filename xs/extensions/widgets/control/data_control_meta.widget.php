<?php

   
class xs_widget_data_control_meta extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'Data:meta widget',
        'description' => 'Meta topic control widgety',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'data',
    );

    // Widget settings (default values, might be manually overridden)
    // $settings denote the appearance of the widget, the outer skin, if you like
    public $settings = array (
        'title' => 'Meta data',
        'style' => 'min-height:100px;',
        'color' => 'color-red',
        // 'class' => array ( 'color-blue' ),
    ) ;


    // Default output
    function GET_content () {

        $x = '<table style="width:100%;">
                   <tr style="background-color:#ccc;font-size:18px;padding:5px;margin:5px;">
                      <td>Created</td> <td>Published</td> <td>Updated</td> <td>Deleted</td> 
                   </tr>
                   <tr>
                   
                      <td style="background-color:#f3f4f5;padding:5px;"> 
                        <a href="{$dir/home}/_tm/topic/{$item/m_c_who}">{$item/m_c_who}</a>
                         at {$item/m_c_date}
                      </td>
                
                      <td style="padding:5px;"> 
                        <a href="{$dir/home}/_tm/topic/{$item/m_p_who}">{$item/m_p_who}</a>
                         at {$item/m_p_date}
                      </td>
                
                      <td style="background-color:#f3f4f5;padding:5px;"> 
                        <a href="{$dir/home}/_tm/topic/{$item/m_u_who}">{$item/m_u_who}</a>
                         at {$item/m_u_date}
                      </td>
                
                      <td style="padding:5px;"> 
                        <a href="{$dir/home}/_tm/topic/{$item/m_d_who}">{$item/m_d_who}</a>
                         at {$item/m_d_date}
                      </td>
                
                   </tr>
                </table>' ;

        return $this->prepare ( $x ) ;
    }


    
}
