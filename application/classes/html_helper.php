<?php

    class html_helper {

        public $counter = 0 ;
        public $id = null ;

        const RETURN_DATA  = 0 ;
        const RENDER_HTML  = 1 ;
        const RENDER_CSV   = 2 ;
        const RENDER_JSON  = 3 ;
        const RENDER_XML   = 4 ;
        const RENDER_EXCEL = 5 ;

        function  __construct () {
        }

        function create_simple_widget ( $id, $arr, $name = '' ) {

            $count = 0 ;
            $pass = array () ;
            $first = '' ;
            $mode = '' ;

            foreach ( $arr as $item => $label ) {
                if ( $count++ == 0 ) $first = $label ;
                $pass[$this->create_ajax_link ( $id, $item, $mode, $name )] = $label ;
            }
            
            // debug_r($pass);

            return
               $this->create_linked_list ( $id.'-menu', $pass, $first ) .
               "<div id='{$id}-content'></div><script>ajax_get('_api/widgets/{$id}','{$id}-content','{$id}_view=index&amp;name={$name}')</script>" ;

        }

        function create_linked_list ( $id, $arr, $selected = null, $class = 'widget-menu' ) {
            $ret = '<ul id="'.$id.'" class="'.$class.'">' ;
            foreach ( $arr as $idx => $label ) {
                $ret .= '<li><a href="'.$idx.'"' ;
                if ( $selected == $label )
                    $ret .= ' class="selected"' ;
                $ret .= ' onclick="menu(this)">'.$label.'</a></li>' ;
            }
            return $ret .= '</ul>' ;
        }

        function create_ajax_link ( $widget, $view='', $mode='', $id='' ) {
            return "javascript:ajax_get('_api/widgets/{$widget}','{$widget}-content','{$widget}_view={$view}&amp;{$widget}_mode={$mode}&amp;name={$id}');" ;
        }
        
        function create_link ( $prepend, $id='' ) {
            return "{$prepend}/{$id}" ;
        }

        function on_click ( $id ) {
            return " onclick=''" ;
        }
        
        function func_menu ( $orientation = 'right', $content ) {
            return "\n<div class='xs-func ui-corner-all' style='float:{$orientation};'>{$content}</div>\n" ;
        }
        
        function func_menu_item ( $label, $link = null, $icon_in = 'edit.png' ) {
            $home = '{$dir/home}/' ;
            $onclick= '' ;
            if ( $link === null )
                $link = '#' ;
            if ( is_array ( $link ) ) {
                $home='' ;
                $link = end ( $link ) ;
                $onclick= $link ;
                $link = '#' ;
            }
            $href = "{$home}{$link}" ;
            $style = 'margin:5px 4px;padding:0;float:right;width:50px;text-align:center;vertical-align:middle;font-size:0.8em;color:#fff;font-weight:bold;' ;
            $icon = '{$dir/images}/icons/24x24/actions/'.$icon_in ;
            $ret = "   <a class='xs-func-item' style='{$style}' href='{$href}' onclick='{$onclick}'>" .
                   "<img src='{$icon}' alt='{$label}' style='margin:0;padding:0;' /><br/>" .
                   "{$label}</a> \n" ;
            // debug_r ( htmlentities ( $ret ) ) ;
            return $ret ;
        }

        function export ( $plugin = 'default', $what = null, $dir = null ) {
            $ret = '<div style="text-align:right;"> <a href="'.$dir.'/widgets/'.$plugin.'?'.$plugin.'_view='.$what.'&amp;_output=csv">CSV</a> ' ;
            $ret .= ' <a href="'.$dir.'/widgets/'.$plugin.'?'.$plugin.'_view='.$what.'&amp;_output=xml">XML</a> ' ;
            $ret .= ' <a href="'.$dir.'/widgets/'.$plugin.'?'.$plugin.'_view='.$what.'&amp;_output=json">JSON</a> ' ;
            $ret .= ' <a href="'.$dir.'/widgets/'.$plugin.'?'.$plugin.'_view='.$what.'&amp;_output=excel">Excel</a> </div>' ;
            return $ret ;
        }

        function _render_headers ( $keys, $conf = array (), $mode = html_helper::RENDER_HTML ) {
            $ret = null ;
            if ( $mode == html_helper::RENDER_HTML ) $ret .= "<thead><tr>\n";
            if ( $mode == html_helper::RENDER_HTML ) $ret .= "<th>No.</th>\n";
            foreach ( $keys as $key ) {
                if ( isset ( $conf[$key] ) ) {
                    if ( $conf[$key] !== false ) {
                        if ( strpos($key, 'flag:') !== false )
                           $key = str_replace ('flag:', '', $key ) ;
                        if ( $mode == html_helper::RENDER_HTML ) $ret .= "<th>$key</th>\n";
                        else $ret[$key] = $key ;
                    }
                } else {
                   if ( $mode == html_helper::RENDER_HTML ) $ret .= "<th>$key</th>\n";
                        else $ret[$key] = $key ;
                }
            }
            if ( $mode == html_helper::RENDER_HTML ) $ret .= "</tr></thead>\n";
            return $ret ;
        }

        function _render_row ( $keys, $conf = array (), $mode = html_helper::RENDER_HTML ) {
            
            $ret = null ;

            if ( $mode == html_helper::RENDER_HTML ) $ret .= "<tr>\n";
            if ( $mode == html_helper::RENDER_HTML ) $ret .= "<td> $this->counter</td>\n"; // (".print_r(array_keys($keys),true).")

            foreach ( $keys as $key => $value ) {

                if ( isset ( $conf[$key] ) ) {

                    if ( $conf[$key] !== false ) {

                        if ( strpos ( $key, 'flag:' ) !== false ) {

                            $cls = $stl = '' ;
                            switch ( $conf[$key] ) {
                                case 'flag' :
                                    $stl = 'background-color:' ;
                                    if ( trim($value) == 'false' )
                                        $stl .= '#ccf' ;
                                    else
                                        $stl .= 'none' ;
                                    break ;
                                case 'timer' :
                                    $stl = 'background-color:' ;
                                    if ( $value < -270 )
                                        $cls = 'yellow' ;
                                    else
                                        $cls = 'green' ;
                                    if ( $value < -380 )
                                        $cls = 'red' ;
                                    break ;
                                case 'nlmh' :
                                    $stl = 'background-color:' ;
                                    if ( $value == 'N' )
                                        $stl .= '#fee' ;
                                    if ( $value == 'L' )
                                        $stl .= '#ecc' ;
                                    if ( $value == 'M' )
                                        $stl .= '#dbb' ;
                                    if ( $value == 'H' )
                                        $stl .= '#d88' ;
                                    break ;
                            }

                            if ( $mode == html_helper::RENDER_HTML ) $ret .= '<td class="color-'.$cls.'" style="'.$stl.'"> '.$value.' </td>' ;
                            else $ret[$key] = $value ;

                        } elseif ( count ( $res = explode ( '[', $conf[$key] ) ) > 1 ) {

                            $re = $conf[$key] ;
                            
                            $what = substr ( $res[1], 0, strpos ( $res[1], ']' ) ) ;
                            $orig = $what ;
                            $from = null ;
                            
                            if ( strstr ( $what, ':' ) ) {
                                $from = (int) substr ( $what, strpos ( $what, ':' ) + 1 ) ;
                                $what = substr ( $what, 0, strpos ( $what, ':' ) ) ;
                            }
                            
                            $data = $keys[$what] ;
                            
                            // echo "[$what::$data::$from] " ;
                            
                            if ( $from != null )
                                $data = substr ( $keys[$what], $from ) ;
                            
                            $re = str_replace ( "[$orig]", $data, $re ) ;
                        
                                    
                            // echo "[$what::$data::$from] *** " ;

                            if ( isset ( $res[2] ) ) {
                                $what = substr ( $res[2], 0, strpos ( $res[2], ']' ) ) ;
                                $re = str_replace ( "[$what]", $keys[$what], $re ) ;
                            }
                            $conf[$key] = $re ;

                            if ( $mode == html_helper::RENDER_HTML ) 
                                $ret .= '<td><a href="'.$conf[$key].'">'.$value.'</a> </td>' ;
                            else 
                                $ret[$key] = $value ;

                        }

                    } else {
                        // if ( $mode == html_helper::RENDER_HTML ) $ret .= "<td>$value ({$conf[$key]})</td>" ;
                    }

                } else {
                    if ( $mode == html_helper::RENDER_HTML ) $ret .= "<td>$value</td>" ;
                    else $ret[$key] = $value ;
                }
            }

            if ( $mode == html_helper::RENDER_HTML ) $ret .= "</tr>\n";

            return $ret ;
        }

        function _render_table ( $table, $conf = array (), $mode = html_helper::RENDER_HTML ) {

            $this->counter = 0 ;
            $data = array () ;

            $ret = null ;
// echo "[".count($table)."]" ;
            $this->id = "dyn-table-".rand(100, 999999999) ;

            if ( isset ( $conf['_export'] ) )
                $ret .= $this->export ( $conf['_export'][0], $conf['_export'][1], $conf['_export'][2] ) ;

            if ( $mode == html_helper::RENDER_HTML )
                $ret .= "<table class='fiddle' id='{$this->id}'>\n";

            if ( is_array ( $table ) ) {

                $tbl = array () ;
                $ix  = array () ;
                $in  = array () ;
                $c = 0 ;
                
                foreach ( $table as $record => $row )
                    foreach ( $row as $idx => $v ) {
                        $ix[$idx] = $idx ;
                        $in[$idx] = '' ;
                    }
                
                // $tmp = array () ;
                // foreach ( $ix as $idx => $count )
                //     $tmp[$idx] = $count ;
                
                // debug_r ( $ix ) ;
                // debug_r ( $tmp ) ;
                
                foreach ( $table as $record => $row ) {
                    $template = $in ;
                    foreach ( $row as $idx => $v ) {
                        // if ( $ix[$idx] !== false )
                        // echo "[{$idx}]=[{$ix[$idx]}]=[$v] " ;
                        $template[$ix[$idx]] = $v ;
                        // debug_r($template);
                        // ksort ( $tmp ) ;
                        // if ( isset ( $conf[$idx] ) )
                            // if ( $conf[$idx] != false )
                               // $ix[$idx] = true ;
                    }
                    $tbl[$record] = $template ;
                }
                
                // debug_r ( $ix ) ;
                // debug_r ( $tbl ) ;
                
                foreach ( $tbl as $record => $row ) {

                    if ( $this->counter == 0 )

                        if ( $mode == html_helper::RENDER_HTML ) {
                            $ret .= $this->_render_headers ( array_keys ( $row ), $conf, $mode ) . "<tbody>" ;
                        } else {
                            $data['headers'] = $this->_render_headers ( array_keys ( $row ), $conf, $mode ) ;
                        }

                    $render = $this->_render_row ( $row, $conf, $mode ) ;

                    if ( $mode == html_helper::RENDER_HTML )
                        $ret .= $render ;
                    else
                        $data[$record] = $render ;

                    $this->counter++ ;
                }

            }

            if ( $mode == html_helper::RENDER_HTML ) $ret .= "</tbody></table>\n";

            switch ( $mode ) {
                case html_helper::RENDER_EXCEL :
                case html_helper::RENDER_CSV   : 
                case html_helper::RENDER_JSON  :
                case html_helper::RETURN_DATA  : return $data ; break ;
                case html_helper::RENDER_HTML  : return $ret ; break ;
            }

            return null ;
        }

        function _render_csv_table ( $table, $conf = array () ) {
            $this->_render_table ( $table, $conf, html_helper::RENDER_CSV ) ;
        }

        function _render_excel_table ( $table, $conf = array () ) {
            $this->_render_table ( $table, $conf, html_helper::RENDER_EXCEL ) ;
        }

        function _render_json_table ( $table, $conf = array () ) {
            $this->_render_table ( $table, $conf, html_helper::RENDER_JSON ) ;
        }

        function _render_xml_table ( $table, $conf = array () ) {
            $this->_render_table ( $table, $conf, html_helper::RENDER_XML ) ;
        }

    }