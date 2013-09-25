<?php

   
class xs_widget_data_control_assoc_list extends xs_Action_Widget_Controller {

    // Generic metadata
    public $meta = array(
        'name' => 'Data:generic_list widget',
        'description' => 'Data control widgety for generic list of things',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'data',
    );

    // Widget settings (default values, might be manually overridden)
    // $settings denote the appearance of the widget, the outer skin, if you like
    public $settings = array (
        // 'title' => 'Is author of these documents',
        // 'style' => 'min-height:400px;',
        'color' => 'color-orange',
        // 'class' => array ( 'color-blue' ),
    ) ;

    // Default output
    function GET_content ( $param = null ) {
        
        $param = $this->xsl_to_php_param ( $param ) ;
        
        // debug_r ( $param ) ;
        $o = null ;
        $oa = null ;
        $docs = null ;
        $_html = '' ;

        $assoctype = $this->_type->$param['assoc-type'] ;
        $assocmembertopic = $param['assoc-member-topic'] ;
        
        $topicid   = $param['topic-id'] ;
        $topictype = $this->_type->$param['topic-type'] ;
        
        
        
        $tm = $this->_get_module ( 'topic_maps' ) ;

        $lut = $tm->get_assoc ( array (
            'lookup' => $assocmembertopic,
            'type' => $assoctype,
            'filter_in' => $topictype,
        ) ) ;

        // debug_r($lut,'lut');
        
        /*
        $oa = $this->glob->tm->query_assoc ( array ( 
            'type' => $assoctype, 
            'member_id' => $assocmembertopic,
            'return_members' => true
        ) ) ;
        $o = new xs_TopicMaps_Assocs ( $oa ) ;
        
        // debug_r ( $oa, 'Assocs Array result' ) ;
        // debug_r ( $o, 'Assocs Object representation' ) ;
        
        $o->member_resolve () ;
        $docs = $o->get_members_of_type ( $topictype ) ;
        
         * 
         */
        // debug_r(array('at'=>$assoctype, 'amt'=>$assocmembertopic,'tid'=>$topicid,'ttype'=>$topictype));
        // debug_r ( $docs, $param['label'] ) ;
        
        // debug_r($docs);
        
        $arr = array () ;

        // foreach ( $lut as $assoc_id => $doc ) {
            foreach ( $lut['members'] as $doc_id => $value ) {
                $z = $this->glob->tm->get_all_prop_for_topic ( $doc_id ,'next_review_date' ) ;
                $x = end ( $z ) ;
                
                $review = isset ( $x['value'] ) ? $x['value'] : 'Not set' ;
                
                $_html .= "<tr><td><a href='".$this->glob->dir->home."/documents/".$doc_id."'>".htmlentities($value['label'])."</a></td><td>{$review}</td></tr>" ;
                // $arr[$doc_id] = $value ;
                // echo "$idx : $value <br>\r\n" ;
            }
        // }
        $rnd = rand ( 10000, 99999 ) ;
        $z = '<table id="tm-data-table-'.$rnd.'" style="width:100%;font-size:0.8em;">
                <thead><tr><th>Label</th><th>next review</th></tr></thead> <tbody>' . $_html . '</tbody>
            </table>
            <script> 
                oTable = $("#tm-data-table-'.$rnd.'").dataTable({ "bJQueryUI": true, "bPaginate": false, "bSearch": false, "bFilter": false, "bLengthChange": true });
            </script> ' ;
        
        return $this->prepare ( $z ) ; 
    }


    
}
