<?php

    // The security module handles all things identification, authentication 
    // and access rules, with heaps of the base code borrowed from
    // https://github.com/GeoffYoung/PHP-ACL/blob/master/class.acl.php

    class xs_module_security extends \xs\Events\Module {

        private $token_structure = 'security-website-pages' ;
        private $topics = null ;
        private $structure = null ;
        
        private $names = null ;
        private $result = null ;
        private $rules = null ;
        private $config = null ;
        
        private $uri = null ;
        
        // register that we want to include an associated JavaScript file
        public $_include_js = true ;

        function ___modules () {
            
            // we support a RESTful API at this URI
            $this->_register_resource ( XS_MODULE, '_api/module/security/access_table', $this ) ;

            // Define the structure query
            $this->structure = $this->glob->data->register_query (

                // identifier for what data connection to use (xs: default xSiteable)
                'xs',

                // identifier for our query
                $this->token_structure,

                // the query in question
                array (
                    'select'      => 'id,type1,parent,label',
                    'type'        => $this->_type->page,
                    'return'      => 'topics',
                ),

                // the timespan of caching the result
                '+1 second'
            ) ;
            
        }
        
        function resolve_uri_structure ( $path = null ) {
            
            // debug ( $path, '_resolve 1' ) ;
            
            // if ( $this->topics != null )
            //     return $this->names ;
            
            $res = array () ;
            
            if ( $path == null ) {
                $this->uri = $this->glob->request->_uri ;
            } else {
                $this->uri = $path ;
            }
            // if ( $this->uri == '' ) $this->uri = XS_ROOT_ID ;

            $path = explode ( '/', $this->uri ) ;
            
            $l = count ( $path ) ;

            // debug ( $path, $l ) ;
            
            if ( $l > 0 ) {
                
                $res[XS_ROOT_ID] = $this->glob->data->create_id ( 
                        XS_PAGE_DB_IDENTIFIER, array ( 'uri' => XS_ROOT_ID ) 
                    ) ;
                
                foreach ( $path as $idx => $item ) {
                    $e = '' ;
                    for ( $m=0; $m < $idx + 1; $m++)
                        $e .= $path[$m] . '/' ;
                    $id = substr ( $e, 0, -1 ) ;
                    if ( trim ( $id ) == '' ) $id = XS_ROOT_ID ;

                    $res[$id] = $this->glob->data->create_id ( 
                        XS_PAGE_DB_IDENTIFIER, array ( 'uri' => $id ) 
                    ) ;
                }

                $topics = $this->glob->tm->query ( array ( 
                    'select' => 'id,label,name,scheme', 
                    'name' => $res, 
                    'return' => 'topics' 
                ) ) ;

                $this->topics = $topics ;
                $this->names = $res ;
            }
            // debug ( $this->names, 'security___' ) ;
            return $res ;
        }
        
        function get_topics () {
            return $this->topics ;
        }
        
        function get_topic_names () {
            if ( is_array ( $this->names ) && count ( $this->names ) > 0 )
                return $this->names ;
            return array () ;
        }
        
        function parse_config () {
            $ret = array () ;
            // return $ret ;
            if ( isset ( $this->glob->config['access'] ) ) {
                foreach ( $this->glob->config['access'] as $page => $item ) {
                    foreach ( explode ( ',', $item ) as $ex ) {
                        // $what = 'deny' ;
                        // if ( substr ( $ex, 1, 4) == 'deny' ) 
                        // echo "<pre>" ; print_r ( $ex ) ; echo '</pre>' ;
                    }
                }
            }
            return $ret ;
        }
        
        function parse_access_rules ( $uri = null ) {

            if ( $uri == null )
                $uri = $this->glob->request->_uri ;
            
            $result = $this->result ;
            $rules = $this->rules ;
            
            if ( $this->config == null )
                $this->config = $this->parse_config () ;
            

            if ( is_array ( $this->topics ) ) {
                $rules = $this->config ;
                foreach ($this->topics as $topic) {
                    if (isset($topic['scheme'])) {
                        $l = @unserialize($topic['scheme']);
                        // debug_r ( $l, $topic['label'] ) ;
                        if (is_array($l) && count($l) > 0) {
                            $c = current ( $l ) ;
                            if ( isset ( $c['source'] ) )
                                $uri = $c['source'] ;
                            foreach ( $l as $idx => $rule ) {
                                $func = $rule['func'] ;
                                $type = $rule['type'] ;
                                $what = $rule['what'] ;
                                $ruler = $rule['rule'] ;
                                // echo $rule['comment'] ;
                                $comment = isset ( $rule['comment'] ) ? $rule['comment'] : '' ;
                                $rules[$uri][$func][$type][$what][$ruler] = $comment ;
                            }
                        }
                    }
                }
                // debug ( $rules ) ;
                // natsort2d ( $rules ) ;
                ksort ( $rules ) ;
                // debug_r ( $rules ) ;
                // debug_r ( $rules ) ;
                $this->rules = $rules ;
            }
            
            // debug_r ( $this->rules ) ;

            if ( is_array ( $this->rules ) ) {

                $result = array();

                // debug_r ( $rules ) ;
                foreach ( $this->rules as $pag => $functionalities ) {
                    
                    $page = $pag ;
                    if ( $page == '' ) $page = XS_ROOT_ID ;
                    
                    $pos = strpos ( $this->uri, $page ) ;
                    if ( $pos === false && $page != XS_ROOT_ID ) continue ;
                    
                    // if ( $pos !== false ) echo "[$page]=[$pos] " ;
                    
                    // echo "[$page][{$this->uri}]=" ; echo "[".strpos ( $this->uri, $page )."] " ;
                    
                    foreach ($functionalities as $functionality => $types ) {

                        foreach ($types as $type => $rules) {

                            foreach ( $rules as $what => $rule_arr) {
                                foreach ( $rule_arr as $rule => $comment ) {

                                    $fetch = 0 ;

                                    switch ($type) {
                                        case 'usertype' : $fetch = $this->glob->user->isUsertype ( $what ) ; break ;
                                        case 'group'    : $fetch = $this->glob->user->isGroup ( $what ) ; break ;
                                        case 'role'     : $fetch = $this->glob->user->isRole ( $what ) ; break ;
                                        case 'username' : $fetch = $this->glob->user->isUsername ( $what ) ; break ;
                                        default : break ;
                                    }
                                    // debug($fetch);
                                    $allowed = 0 ;

                                    if ($rule == 'a' || $rule == 'd') {

                                        $w = 'ignored' ;

                                        if ( $fetch ) {
                                            // yes, the criteria fits
                                            if ( $rule == 'a' ) $allowed = 1 ; 
                                            elseif ( $rule == 'd' )  $allowed = 2 ;
                                        } elseif ( $fetch === null ) {
                                            $allowed = 3 ;
                                        }

                                        if ( $allowed == 1 ) $w = 'allowed' ;
                                        if ( $allowed == 2 ) $w = 'denied' ;
                                        if ( $allowed == 3 ) {
                                            $w = 'forced' ;
                                        }

                                        // $source, $func, $type, $what, $fetch, $rule, $ruling

                                        $this->add_access_rule ( 
                                                $page,$functionality,$type,$what,$comment,$fetch,$rule,$w 
                                        ) ;

                                        continue;
                                    }

                                }
                            }
                        }
                    }
                }
                // $this->result = $result ;
                // debug($result) ;
                
            }
            // debug_r($this->result);
        }
        
        function ___output () {
            $this->glob->stack->add ( 'xs_access', $this->result ) ;
        }
        
        function add_access_rule ( $source, $func, $type, $what, $comment, $fetch, $rule, $ruling ) {
            
            $idx = "{$source}-{$func}-{$type}-{$what}" ;
            
            $this->result[$source][$idx] = array ( 
                                        'source' => $source, 
                                        'func' => $func, 
                                        'type' => $type, 
                                        'what' => $what,
                                        'comment' => $comment,
                                        'fetch' => $fetch, 
                                        'rule' => $rule,
                                        'ruling' => $ruling
                                        ) ;
            
        }
        
        function has_access ( $func = 'page', $incoming_default = null ) {
            
            // echo "<hr style='height:5px;background-color:red;' />" ;
            
            $default = false ;
            
            if ( isset ( $this->glob->config['framework']['security_model'] )
                 && $this->glob->config['framework']['security_model'] == 'open' )
                $default = true ;
            
            $allowed = $default ;
            // debug( $allowed, $func ) ;
            
            // debug( $incoming_default, $func ) ;
            
            if ( $incoming_default !== null ) {
                // echo "!!! " ;
                $allowed = $incoming_default ;
            }
            
            // debug( $allowed, $func ) ;
            // $allowed = $default ;
            
            // debug ( $this->result ) ;
            
            if ( is_array ( $this->result ) && count ( $this->result ) > 0 )
                foreach ( $this->result as $page => $rules ) {
                    foreach ( $rules as $rule ) {
                        
                        $f = $rule['func'] ;
                        $pos = strpos ( $f, '*' ) ;
                        $pre = substr ( $f, 0, $pos ) ;
                        // echo "[$func - $f - $pos - $pre] " ;
                        if ( trim ( $pre ) != '' && strpos ( $func, $pre ) !== false ) {
                            $rule['func'] = $func ;
                            // debug(strpos ( $func, $pre ));
                            // echo " !!! " ;
                        }
                        
                        // echo "[$func / $f] " ;
                        if ( $rule['func'] == $func && isset ( $rule['ruling'] ) ) {
                            if ( $rule['ruling'] == 'denied' ) {
                                $allowed = false ;
                            }
                            if ( $rule['ruling'] == 'allowed' || $rule['ruling'] == 'forced' ) {
                                $allowed = true ;
                            }
                        }
                        // debug($rule) ;
                    }
                }
            // debug( $allowed, 'bbbbb '.$func ) ;
            // debug ( $this->result ) ;
            // var_dump ( $allowed, $func ) ;
            return $allowed ;
        }
        
        function draw_radiolist ( $id, $list, $enabled = true, $select = null ) {
            foreach ( $list as $idx => $value ) {
                echo "<div><input type='radio' name='f:{$id}' value='{$idx}'" ;
                if ( $idx == $select )
                    echo " selected='selected'" ;
                echo ">{$value}</div>" ;
            }
        }
        
        function draw_list ( $id, $list, $enabled = true, $select = null ) {
            echo "<select id='{$id}' name='f:{$id}'" ;
            if ( ! $enabled )
                echo " disabled='disabled'" ;
            echo ">" ;
            foreach ( $list as $idx => $value ) {
                echo "<option value='{$idx}'" ;
                if ( $idx == $select )
                    echo " selected='selected'" ;
                echo ">{$value}</option>" ;
            }
            echo "</select>" ;
        }
        
        function draw_field ( $id, $value = '', $enabled = true ) {
            echo "<input type='text' style='width:90%;' id='{$id}' name='f:{$id}' value='{$value}'" ;
            if ( ! $enabled )
                echo " disabled='disabled'" ;
            echo ">" ;
        }
        
        function _http_action ( $in = null ) {
            $method = $this->glob->request->get_method () ;
            $this->$method () ;
        }
        
        function POST () {
            
            // echo 'POST' ; die () ;
            
            $rules = array () ;
            $uri = $this->glob->request->uri ;
            if ( $uri == '' ) $uri = XS_ROOT_ID ;
            
            $fields = $this->glob->request->__get_fields () ;
            
            // debug_r ( $fields ) ;
            // echo "!!!" ;
            // Is the current URI allowed for the current default user?
            $res = $this->resolve_uri_structure ( $uri ) ;
            
            // parse security
            $this->parse_access_rules () ;
            
            if ( is_array ( $fields ) && count ( $fields ) > 0 )
                foreach ( $fields as $key => $field ) {
                    $t = explode ( '__', $key ) ;
                    if ( count ( $t ) > 1 ) {
                        $rules[$t[0]][$t[1]] = $field ;
                    }
                }
            
            $old = array () ;
            if ( isset ( $this->result[$uri] ) )
                $old = $this->result[$uri] ;

            // debug_r ( $rules ) ;
            
            $new = array () ;
            if ( is_array ( $rules ) && count ( $rules ) > 0 )
                foreach ( $rules as $idx => $rule ) {
                    $in = array () ;
                    $add = true ;
                    $in['source'] = $uri ;
                    $in['func'] = isset ( $rule['source'] ) ? $rule['source'] : '' ;
                    if ( trim ( $in['func'] ) == '' ) $add = false ;
                    $in['type'] = isset ( $rule['type'] ) ? $rule['type'] : '' ;
                    if ( trim ( $in['type'] ) == '' ) $add = false ;
                    $in['what'] = isset ( $rule['what'] ) ? $rule['what'] : '' ;
                    $in['comment'] = isset ( $rule['comment'] ) ? $rule['comment'] : '.' ;
                    $in['rule'] = isset ( $rule['rule'] ) ? $rule['rule'] : '' ;
                    if ( $add ) {
                        $new[$idx] = $in ;
                    }
                }
            
            //debug_r($new);// die();
            // identifier
            $id = $this->glob->data->create_id ( 
                XS_PAGE_DB_IDENTIFIER, array ( 'uri' => $uri ) 
            ) ;
            
            // debug ( $id ) ;

            $topics = $this->glob->tm->query ( array ( 
                'name' => $id, 
            ) ) ;
            $topic = end ( $topics ) ;
            
            $topic['scheme'] = @serialize ( $new ) ;
            $topic['who'] = $this->glob->user->id ;
            $this->glob->tm->update ( $topic ) ;
            
            // $topics = $this->glob->tm->query ( array ( 'name' => $id ) ) ;
            // $topic = end ( $topics ) ;
            
            $this->glob->request->_redirect = $uri ;
            // debug_r ( $topic ) ;
            // die();
        }
        
        function GET () {
            
            $uri = $this->glob->request->uri ;
            $read_only = $this->glob->request->read_only ;
            
            if ( $read_only == 'true' )
                $read_only = true ;
            else
                $read_only = false ;
            
            // debug($uri, '_GET 1');
            if ( $uri == '' ) $uri = XS_ROOT_ID ;
            $f = $this->glob->request->func ;

            $z = str_replace ( array ( chr(10), ' ', chr(13) ), ' ', 
                   strip_tags ( $this->glob->request->func ) ) ;
            $func = explode ( ' ', $z ) ;

            // debug_r($this->glob->request, 'request');
            // debug_r($func);
            // Is the current URI allowed for the current default user?
            $res = $this->resolve_uri_structure ( $uri ) ;

            // debug($res) ;
            // parse security
            $this->parse_access_rules () ;
            
            $list_what = array ( 
                'username' => 'Username',
                'usertype' => 'User is of Type',
                'group' => 'User belongs to Group',
                'role' => 'User has Role'
            ) ;
            
            $list_rules = array ( 'a' => 'Allow', 'd' => 'Deny' ) ;

            $list_source = array () ;
            foreach ( $func as $source ) {
                $list_source[$source] = $source ;
                if ( $source == 'page' )
                    $list_source[$source] = 'View page' ;
            }
            // debug($list_source);
            $counter = 0 ;
            
            $_uri = $this->glob->request->_uri ;
            if ( $_uri == '' ) $_uri = XS_ROOT_ID ;

            ?><form action="<?php echo $this->glob->dir->home . '/' . $_uri ; ?>" method="post" style="margin:0;margin-top:4px;padding:0;<?php
            if ( $read_only ) echo "padding:10px 15px;" ;
            ?>">
                 <table id="xs-access-rules<?php if ( $read_only ) echo "-ro" ; ?>" width="100%;margin:0;padding:3px;">
                    <thead><tr style="background-color:#999;">
                        <td style="background-color:#fca;font-weight:bold;width:30px;text-align:center;">rule</td>
                        <td style="background-color:#fca;font-weight:bold;">source</td>
                        <td style="background-color:#fca;font-weight:bold;">  </td>
                        <td style="background-color:#fca;font-weight:bold;">type</td>
                        <td style="background-color:#fca;font-weight:bold;">what</td>
                        <td style="background-color:#fca;font-weight:bold;">comment</td>
                        <td style="background-color:#fca;font-weight:bold;">rule</td>
                        <td style="background-color:#fca;font-weight:bold;">ruling</td>
                        <td style="background-color:#fca;font-weight:bold;">action</td>
                    </tr></thead>
                    <tbody>
            <?php 
            // debug_r ( $this->result ) ;
            
            if ( is_array ( $this->result ) && count ( $this->result ) > 0 )
                
             foreach ( $this->result as $source => $content ) {
                 
                 $enabled = true ;
                 if ( trim ( $source ) !== trim ( $uri ) )
                     $enabled = false ;
                 
                 if ( $read_only )
                     $enabled = false ;
                 
                 $style = '' ;
                 if ( ! $enabled ) $style = 'background-color:#ddd;color:#555;' ;
                 
                foreach ( $content as $rule ) {
                    
                    // debug_r($rule);
                    $counter++ ;
                    $rnd = chr ( rand ( 65, 86 ) ) . rand ( 100, 999 ) ;
                    $func = 'page' ;
                    if ( isset ( $rule['id'] ) && trim ( $rule['id'] ) != '' )
                        $rnd = $rule['id'] ;
                    if ( isset ( $rule['func'] ) && trim ( $rule['func'] ) != '' )
                        $func = $rule['func'] ;
                    
            ?><tr id="<?php echo $rnd ; ?>__row" class="<?php if ( ! $enabled ) echo "state-disabled" ; ?>">
                  <td style="<?php echo $style; ?>width:30px;text-align:center;"><?php echo $counter; ?></td>
                  <td style="<?php echo $style; ?>"><?php 
                        
                    if ( ! $enabled && ! $read_only ) 
                        echo $rule['source'] . ' &gt; '.$func.' <i style="font-size:0.7em;color:#333;">(inherited)</i>' ;
                    else {
                        $func = '' ;
                        if ( isset ( $rule['func'] ) )
                            $func = $rule['func'] ;
                        echo 'For ' ;
                        $this->draw_list ( $rnd.'__source', $list_source, $enabled, $func );
                    }
                        ?></td>
                        <td style="<?php echo $style; ?>">If</td>
                        <td style="<?php echo $style; ?>"><?php $this->draw_list ( $rnd.'__type', $list_what, $enabled, $rule['type'] ); ?></td>
                        <td style="<?php echo $style; ?>">=&nbsp;<?php $this->draw_field ( $rnd.'__what', $rule['what'], $enabled ); ?></td>
                        <td style="<?php echo $style; ?>"><?php 
                        if ( $enabled ) 
                            $this->draw_field ( $rnd.'__comment', $rule['comment'], $enabled );
                        else
                            echo $rule['comment'] ;
                        ?></td>
                        <td style="<?php echo $style; ?>"> then <?php $this->draw_list ( $rnd.'__rule', $list_rules, $enabled, $rule['rule'] ); ?></td>
                        <td style="<?php echo $style; ?>"><?php
                           if ( $rule['ruling'] == 'denied' ) 
                               echo "<b style='background-color:#f88;'>{$rule['ruling']}</b>" ;
                           elseif  ( $rule['ruling'] == 'allowed' ) 
                               echo "<b style='background-color:#8f8;'>{$rule['ruling']}</b>" ;
                           elseif ( $rule['ruling'] == 'ignored' ) 
                               echo "<b style='background-color:#ddd;'>No match</b>" ;
                           elseif ( $rule['ruling'] == 'forced' ) 
                               echo "<b style='background-color:#8f8;'>Super-user allowed</b>" ;
                        ?>
                        </td>
                        <td style="<?php echo $style; ?>">
                            <?php if ( $enabled ) { ?>
                                <button onclick="$('#<?php echo $rnd ; ?>__row').remove();">Delete</button>
                            <?php } ?>
                        </td>
                    </tr>
            <?php 
                }
             } 
             
             if ( ! $read_only ) { ?>
                    <tr class="state-disabled last">
                        <td colspan="7" style="text-align:left;color:#888;font-style:italic;">
                            Note: All rules are parsed, top to bottom. Drag rules to order.
                        </td>
                        <td colspan="2" style="text-align:right;"><div style='text-align:right;'>
                            <button type="button" onclick='add_new_access_rule();return false;'>Add new rule</button>
                            <input type='submit' value='Save!' />
                        </div></td>
                    </tr>
            <?php }
            
            echo "</tbody></table><input type='hidden' name='uri' value='{$uri}' /></form> " ;

        }
    }
