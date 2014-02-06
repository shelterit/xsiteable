<?php

   
class xs_widget_data_control_latest_list extends \xs\Action\WidgetController {

    // Generic metadata
    public $meta = array(
        'name' => 'Data:generic_list widget',
        'description' => 'Data control widgety for generic list of things',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'data',
    );
    
    public $settings = array (
        // 'title' => 'Is author of these documents',
        // 'style' => 'min-height:400px;',
        'color' => 'color-orange',
        // 'class' => array ( 'color-blue' ),
    ) ;
    
    private $resolve = null ;
    

    // Default output
    function GET_content ( $param = null ) {
        
        $param = $this->xsl_to_php_param ( $param ) ;
        // debug_r($param);
        $topicid = $param['topic-id'] ;
        // debug($topicid);
        $types = array ( $this->_type->_page, $this->_type->_comment, $this->_type->_news_item, $this->_type->_forum_item, $this->_type->_document ) ;
        $oa = $this->glob->tm->query ( array ( 
            'select'      => 'id,type1,label,m_c_date,m_c_who,m_p_date,m_p_who,m_u_date,m_u_who,parent',
            'type'        => $types,
            'sort_by'     => 'm_c_date DESC',
            'm_c_p_u_who' => $topicid,
            'limit'       => 10,
            'lookup_name' => 'type1,m_c_who,m_p_who,m_u_who,m_d_who,parent',
            'debug'       => true
        ) ) ;
        // die();
        /*
        $oc = $this->glob->tm->query ( array ( 
            'select'      => 'id,type1,label,m_c_date,m_c_who,m_p_date,m_p_who,m_u_date,m_u_who,parent',
            'type'        => $this->_type->_comment,
            'sort_by'     => 'm_c_date DESC',
            'm_c_p_u_who' => $topicid,
            'limit'       => 10,
            'lookup_name' => 'type1,m_c_date,m_c_who,m_p_date,m_p_who,m_u_date,m_u_who,parent',
            'debug'       => true
        ) ) ;*/
        // debug($types);
        // debug($oa);
        // debug_r ( array ( 'comment'=>$this->_type->_comment, 'page'=>$this->_type->_page, 'news'=>$this->_type->_news, 'forum'=>$this->_type->_forum, 'document'=>$this->_type->_document ) ) ;
        
        $topics = new \xs\TopicMaps\Collection ( $oa ) ;
        
        // resolve some topics in that query result
        $topics->resolve_topics ( array (
            'm_c_who' => array (
                // 'label' => false,
                'name' => 'username=substr($in,5)'
            ),
            'm_p_who' => array (
                'label' => false,
                'name' => 'username=substr($in,5)'
            ),
            'm_u_who' => array (
                'label' => false,
                'name' => 'username=substr($in,5)'
            )
        ) ) ;

        // debug_r($topics->topics);
        
        $_html = '' ;
        $arr = array () ;

        foreach ( $topics->topics as $topic_id => $topic_object ) {
            $topic = new \xs\Store\Properties ( $topic_object->get_as_array () ) ;
            $when = '0' ;
            $topic->op = 'n/a' ;
            if ( (int) $topic->m_c_who == $topicid ) { $when = $topic->m_c_date ; $topic->op = 'Create' ; }
            if ( (int) $topic->m_p_who == $topicid ) { $when = $topic->m_p_date ; $topic->op = 'Publish' ; }
            if ( (int) $topic->m_u_who == $topicid ) { $when = $topic->m_u_date ; $topic->op = 'Update' ; }
            if ( (int) $topic->m_d_who == $topicid ) { $when = $topic->m_d_date ; $topic->op = 'Delete' ; }
            $arr[$when] = $topic->__get_array () ;
        }
        
        krsort($arr);
        // debug_r($arr);
        foreach ( $arr as $when => $topic ) {
            // debug_r($when,'m');
            // $topic = $topic->__get_array () ;
            $cd = timed($when) ;
            $operation = $topic['op'] ;
            // debug_r($topic);
            
            // $thing_type = isset ($topic['type1_type1']) ? $topic['type1_type1'] : null ;
            $thing_name = isset ($topic['type1_name']) ? $topic['type1_name'] : null ;
            
            // debug ( $thing_name ) ;
            
            $test = $this->resolve_topic ( $thing_name ) ;
            $final = $thing_name ;
            
            if ( $test !== null )
                $final = $test ;
            
            if ( $final == 'comment' ) {
                if ( isset ( $topic['parent_type1'] ) ) {
                    $oa = $this->glob->tm->query ( array ( 
                        'select'      => 'id,type1,name,label,m_c_date,m_c_who,m_p_date,m_p_who,m_u_date,m_u_who,parent',
                        'id'          => $topic['parent_type1'],
                        'sort_by'     => 'm_c_date DESC',
                        'limit'       => 1,
                        'lookup_name' => 'type1,m_c_who,m_p_who,m_u_who,m_d_who,parent'
                    ) ) ;
                    $t = reset ( $oa ) ;
                    // $test = $this->glob->tm->lookup_topics ( array ( $t['name'] ) ) ;
                    if ( $t ) {
                        $arr[$when]['where_to'] = $t['name'] ;
                        if ( $t['name'] == 'user' )
                            $arr[$when]['where_to'] = 'profile' ;
                    }
                }
            }
            
            if ( $final == 'page' )
                $final = '_page' ;
            
            /*
            debug ( $final ) ;
            
            if ( isset ($topic['type1_type1']) && (int)$topic['type1_type1'] == (int)$this->_type->_forum_item )
                $arr[$when]['where'] = 'forum' ;
            else if ( isset ( $topic['type1_type1']) && (int)$topic['type1_type1'] == (int)$this->_type->_news_item )
                $arr[$when]['where'] = 'news' ;
            else if ( isset ( $topic['type1_type1']) && (int)$topic['type1_type1'] == (int)$this->_type->_document ) {
                $arr[$when]['where'] = 'documents' ;
                if ( isset ( $topic['parent_id']) )
                    $arr[$when]['parent_id'] = substr ( $arr[$when]['parent_name'], 9 ) ;
            } // else $arr[$when]['where'] = '' ;
            */
            $where = $final ;
            if ( isset ( $arr[$when]['where'] ) )
                $where = $arr[$when]['where'] ;
            
            $href = $this->glob->dir->home.'/'.$where.'/'.$topic['id'] ;
            
            if ( $where == 'comment' ) {
                if ( isset ( $topic['parent_label'] ) )
                    $topic['label'] = $topic['parent_label'] ;
                if ( isset ( $arr[$when]['where_to'] ) )
                    $href = $this->glob->dir->home.'/'.$arr[$when]['where_to'].'/'.$topic['parent_id'].'?f:_item=view#comment-'.$topic['id'] ;   
            }
            
            // if ( $where == 'documents' )
            //     $href = $this->glob->dir->home.'/'.$where.'/'.$topic['parent_id'].'?f:_item=view#comment-'.$topic['id'] ;
              
            // echo "[{$href}] " ;
            $topic['label'] = htmlentities ( $topic['label'] ) ;
            
            if ( trim ( $topic['label'] ) == '' )
                $_html .= "<tr>
                <td><span style='display:none;'>{$when}</span> {$cd}</td>
                <td>{$operation}</td>
                <td>".$topic['label']."</td>
                <td><a href='{$href}'>{$topic['type1_label']}</a></td>
                </tr>" ;
            else
                $_html .= "<tr>
                <td><span style='display:none;'>{$when}</span> {$cd}</td>
                <td>{$operation}</td>
                <td>{$topic['type1_label']}</td>
                <td><a href='{$href}'>".$topic['label']."</a></td>
                        </tr>" ;
            
            // $arr[$doc_id] = $value ;
        }
        
                
        $z = '<table id="tm-data-table" style="width:100%;font-size:0.8em;">
                <thead><tr><th>When</th><th>What</th><th>On</th><th>Where</th></tr></thead> <tbody>' . $_html . '</tbody>
            </table>
            <script> oTable = $("#tm-data-table").dataTable({ "bJQueryUI": true, "bPaginate": false, "bSearch": false, "bFilter": false,
        "bLengthChange": true, "aaSorting": [[ 0, "desc" ]]  });
            </script>  ' ;
        
        return $this->prepare ( $z ) ; 
    }

    function resolve_topic ( $type ) {
        if ( ! $this->resolve ) {
            $this->resolve = $this->glob->config->parse_section ( 'resolve' ) ;
        }
        $res = $this->resolve ;
        $n = $this->glob->tm->lookup_topics ( array ( $type => $type ) ) ;
        if ( isset ( $n[$type] ) ) {
            // yes, found the type
            $t = $n[$type]['name'] ;
            if ( isset ( $res[$t] ) ) {
                return $res[$t][0]['@label'] ;
            }
        }
        return null ;
    }
}
