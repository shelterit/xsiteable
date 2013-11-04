<?php

   
class xs_widget_data_control_topic extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'Data:topic widget',
        'description' => 'Data topic control widgety',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'data',
    );

    // Widget settings (default values, might be manually overridden)
    // $settings denote the appearance of the widget, the outer skin, if you like
    public $settings = array (
        'title' => 'Topic',
        'style' => 'min-height:400px;',
        'color' => 'color-green',
        // 'class' => array ( 'color-blue' ),
    ) ;


    // Default output
    function GET_content () {

        $x = '<form xmlns:nut="http://schema.shelter.nu/nut"
                    xmlns:form="http://schema.shelter.nu/nut-form"
                    xmlns:f="http://schema.shelter.nu/nut-formy"
                    action="{$dir/home}/_tm/topic/{$rest/id}" method="post">
              <ul class="form-list">
                <li>
                    <f:f name="f:label" title="Title" style="width:350px;font-size:18px;font-weight:bold;color:green;" class="fielder">{$item/label}</f:f>
                    <table border="0" style="border:none !important;">
                      <tr>
                        <td  class="no-border" width="33%">
                          <b>Id</b> <input type="text" name="f:id" value="{$item/id}" style="width:45px;" />
                        </td>
                        <td  class="no-border" width="33%">
                          <b>Parent Id</b> <input type="text" name="f:parent" value="{$item/parent}" style="width:45px;" />
                        </td>
                      </tr>
                      <tr>
                        <td  class="no-border"> </td>
                        <td  class="no-border">
                            <a href="{$dir/home}/_tm/topic/{$item/parent}"><nut:value-of select="$item/parent_label" /></a>
                        </td>
                      </tr>
                    </table>
                </li>
                <li>
                    <div><b>Type(s)</b></div>
                    <table border="0" class="no-border" style="margin:10px 0 25px 0;padding:0;width:100%;">
                      <tr>
                        <td  class="no-border" width="33%">
                          <input type="text" name="f:type1" value="{$item/type1}" style="width:45px;" />
                        </td>
                        <td  class="no-border" width="33%">
                          <input type="text" name="f:type2" value="{$item/type2}" style="width:45px;" />
                        </td>
                        <td  class="no-border" width="33%">
                          <input type="text" name="f:type3" value="{$item/type3}" style="width:45px;" />
                        </td>
                      </tr>
                      <tr>
                        <td  class="no-border">
                          <a href="{$dir/home}/_tm/topic/{$item/type1}"><nut:value-of select="$item/type1_label" /></a>
                        </td>
                        <td  class="no-border">
                          <a href="{$dir/home}/_tm/topic/{$item/type2}"><nut:value-of select="$item/type2_label" /></a>
                        </td>
                        <td  class="no-border">
                          <a href="{$dir/home}/_tm/topic/{$item/type3}"><nut:value-of select="$item/type3_label" /></a>
                        </td>
                      </tr>
                    </table>
                </li>
                <li>
                <f:f name="f:name" title="Name" class="fielder" style="width:300px;">{$item/name}</f:f>
                <f:f name="f:schema" title="Schema" class="fielder">{$item/schema}</f:f>
                <f:f name="f:value" title="Value" class="fielder" type="textarea" style="width:100%;height:100px;">{$item/value}</f:f>
                </li>
                

                <li style="float:left;"><input type="submit" value="Save!" style="background-color:#afa;" /> </li>
              </ul>
            </form>
            <form xmlns:nut="http://schema.shelter.nu/nut"
                    xmlns:form="http://schema.shelter.nu/nut-form"
                    xmlns:f="http://schema.shelter.nu/nut-formy"
                    action="{$dir/home}/_tm/topic/{$rest/id}?_method=delete&amp;_redirect=/_tm/topic" method="post">
                    <ul class="form-list">
                    <li style="float:right;"><input type="submit" value="Delete!" style="background-color:#faa;" /></li>
                    </ul>
            </form>
        ' ;

        return $this->prepare ( $x ) ;
    }


    
}
