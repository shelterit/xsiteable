<?php

    class xs_module_tags extends \xs\Events\Module {

        public $meta = array (
            'name' => 'Tags module',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // if types are to be defined and used
        protected $___register_types = array ( 

            // basic standard tag types
            '_tag' => 'A tag',
            '_tags' => 'A number of tags',
            'has_tag' => 'Has a tag',
            
            // controlled tags
            '_controlled_tag' => 'A controlled tag',
            '_controlled_tags' => 'A number of controlled tags',
            'has_controlled_tag' => 'Has a controlled tag'
            
        ) ;
        
        protected $resource = null ;
        
        protected $section = null ;
        protected $tag = null ;
        
        protected $open_tags = null ;
        protected $controlled_tags = null ;
        
        function ___modules () {
            
            // we support a RESTful API at this URI
            $this->_register_resource ( XS_MODULE, '_api/module/tags/factory' ) ;
            
            // register a few actions against a given type
            // $this->_register_action ( 'add_tag', $this->_type->_document, 'add_document' ) ;
            /*
            $this->_register_as_type_controller ( $this->_type->_tag ) ;
            
            $this->_register_as_handler ( $this->_type->_tag, array ( 
                'create', 
                'delete',
                'attach' => array ( 
                    'method' => '_attach_tag',
                    ''
                ),
                'detach'
            ) ) ;
            
            $this->_register_as_handler ( $this->_type->_controlled_tag, array ( 
                'create', 
                'delete',
                'attach',
                'detach'
            ) ) ;
             * 
             */
        }
        
        function _http_action ( $in = null ) {
            $method = $this->glob->request->get_method () ;
            $this->$method () ;
        }
        
        function ___on_core_process_request () {
            
            if ( $this->glob->breakdown->concept == 'tags' ) {
                
                $core = $this->_get_module ( 'core' ) ;
                $lookup = $core->on_request_file ( 'website/generic_zombie.php' ) ;
                
                if ( isset ( $lookup[1] ) ) {
                    
                    $this->resource = $lookup[1] ;
                    
                    $this->resource->_set_parent ( $this ) ;

                    return ( array ( 'module_tags', $this->resource ) ) ;
                }
            }
            
            return ( array ( 'module_tags', null ) ) ;
        }
        
        function GET () {
            
            if ( $this->resource ) {
                
                $this->resource->set_title ( 'Taaaaaaaags!' ) ;
                $facets = array ( 'tags' => 'Tags!' ) ;
                
                $section = $this->glob->breakdown->section ;
                
                $this->tag = $this->glob->breakdown->id ;
                
                if ( trim ( $section ) == '' )
                    $section = '_index' ;
                
                // $type = null ;
                /*
                if ( trim ( $section ) == '' ) {
                    $section = '_index' ;
                } elseif ( $section == '_controlled' ) {
                    $type = $this->_type->_controlled_tag ;
                } else {
                    $type = $this->_type->_tag ;
                }
                */
                $this->section = trim ( $section ) ;
                
                switch ( trim ( $this->section ) ) {
                    case '_open' : $facets['_open'] = 'Open tags' ; break ;
                    case '_controlled' : $facets['_controlled'] = 'Controlled tags' ; break ;
                }
                
            } else {
                // echo "!!!" ;
            }

            $this->glob->stack->add ( 'xs_facets', $facets ) ;
        }
        
        function list_tags ( $label, $tags, $prefix, $pick_label = 'label', $pick_id = 'id' ) {
            $html = '' ;
            if ( $label != '' ) $html .= '<h3>'.$label.'</h3>' ;
            $html .= '<ul>' ;
            // debug_r ( $tags ) ;
            if ( is_array ( $tags ) && count ( $tags ) > 0 ) {
                foreach ( $tags as $tag ) {
                    // debug_r($this->section);
                    $href = $this->glob->dir->home ;
                    $i = isset ( $tag[$pick_id] ) ? $tag[$pick_id] : '-' ;
                    $w = isset ( $tag[$pick_label] ) ? $tag[$pick_label] : '-' ;
                    $href .= $prefix.'/'.$i ;
                    $html .= "<li><a href='{$href}'>{$w}</a></li>" ;
                }
            }
            $html .= '</ul>' ;
            return $html ;
        }
        
        function is_this ( $what = '' ) {
            if ( $what == '' ) $what = '_index' ;
            if ( $what == $this->section ) return 'class="selected"' ;
        }
        
        
        
        
        function group_assocs_by_role ( $res ) {
            $fin = array () ;
            $fin_lut = array () ;
            if ( count ( $res ) > 0 ) {
                foreach ( $res as $assoc_id => $members ) {
                    foreach ( $members as $member_id => $member ) {
                        $fin[$member['role_label']][$assoc_id] = array (
                            'id' => $member_id,
                            'label' => $member['label'],
                            'role' => $member['role'],
                            'role_label' => $member['role_label'],
                        ) ;
                        $fin_lut[$member['role_label']] = $member['role'] ;
                    }
                }
                return array ( $fin, $fin_lut ) ;
            }
            return array () ;
        }
        
        // note : Fake event. Our zombie object passes this through to us
        function _gui_section0 () {
            return '
                <ul class="menu">
                    <li><a '.$this->is_this().' href="'.$this->glob->dir->home.'/tags">Index</a></li>
                    <li><a '.$this->is_this('_open').' href="'.$this->glob->dir->home.'/tags/_open">Open tags</a></li>
                    <li><a '.$this->is_this('_controlled').' href="'.$this->glob->dir->home.'/tags/_controlled">Controlled tags</a></li>
                </ul> ' ;
        }
        
        // note : Fake event. Our zombie object passes this through to us
        function _gui_section1 () {
            
            $tag = $this->tag ;
            $tm = $this->_get_module ( 'topic_maps' ) ;
            $html = '' ;
            
            $tags = array () ;
            $otag = array () ;
            $tag_id = null ;
            
            if ( trim ( $tag ) != '' ) {
                $q = array ( 
                    'type' => array ( $this->_type->_tag, $this->_type->_controlled_tag ),
                    'name' => 'tag:'.$this->clean_tag ( $tag ) 
                ) ;
                $tags = $this->glob->tm->query ( $q ) ;
                // debug_r($tags);
                $otag = end ( $tags ) ;
                $tag_id = $otag['id'] ;
            }
            
            switch ( trim ( $this->section ) ) {

                case '_index' : // main index page; show both kind of types
                        $otags = $this->glob->tm->query ( array ( 
                            'type' => array ( $this->_type->_tag ),
                            'sort_by' => 'label'
                        ) ) ;
                        $ctags = $this->glob->tm->query ( array ( 
                            'type' => array ( $this->_type->_controlled_tag ),
                            'sort_by' => 'label'
                        ) ) ;
                        
                        return $this->list_tags ( 'Open tags', $otags, '/tags/_open', 'label', 'label' ) .
                               $this->list_tags ( 'Controlled tags', $ctags, '/tags/_controlled', 'label', 'label' ) ;
                    break ;

                case '_open' : // open tags section
                    $facets['_open'] = 'Open tags' ;
                    if ( trim ( $tag ) == '' ) {
                        // open tags index
                        $otags = $this->glob->tm->query ( array ( 
                            'type' => array ( $this->_type->_tag ),
                            'sort_by' => 'label'
                        ) ) ;
                        return $this->list_tags ( 'Open tags', $otags, '/tags/_open', 'label', 'label' ) ;
                    } else {
                        // open tag
                        $q = $this->glob->tm->query_assoc ( array ( 
                                'type' => $this->_type->has_tag, 
                                'member_id' => $tag_id,
                        ) ) ;
                        $t = new \xs\TopicMaps\Assocs ( $q ) ;
                        $t->inject ( array ( 'type' => $this->_type->has_tag ) ) ;
                        $t->member_resolve () ;
                        
                        $res = $t->get_members_not_of_type ( $this->_type->_tag ) ;
                        
                        $fin_n = $this->group_assocs_by_role ( $res ) ;
                        $fin = $fin_lut = array () ;
                        if ( isset ( $fin_n[0] ) ) $fin = $fin_n[0] ;
                        if ( isset ( $fin_n[1] ) ) $fin_lut = $fin_n[1] ;
                        // debug_r($fin_n);
                        $html .= '<h2>Things tagged with "'.$this->tag.'"</h2>' ;
                        if ( count ( $tags ) > 0 ) {
                            foreach ( $fin as $title => $items ) {
                                // $html .= '<h3>'.$title.'</h3> ' ;
                                $html .= $this->list_tags ( $title, $items, $tm->resolve_topic ( 'type', $fin_lut[$title] ) ) ;
                            }
                        } else {
                            $html .= '<p>Hmm. None?</p>' ;
                        }
                        return $html ;
                    }
                    break ;

                case '_controlled' : // controlled tags section

                    $facets['_controlled'] = 'Controlled tags' ;
                    if ( trim ( $tag ) == '' ) {
                        // controlled tags index
                        $ctags = $this->glob->tm->query ( array ( 
                            'type' => array ( $this->_type->_controlled_tag ),
                            'sort_by' => 'label'
                        ) ) ;
                        return $this->list_tags ( 'Controlled tags', $ctags, '/tags/_controlled', 'label', 'label' ) ;
                    } else {
                        // controlled tag
                        $q = $this->glob->tm->query_assoc ( array ( 
                                'type' => $this->_type->has_controlled_tag, 
                                'member_id' => $tag_id,
                        ) ) ;
                        $t = new \xs\TopicMaps\Assocs ( $q ) ;
                        $t->inject ( array ( 'type' => $this->_type->has_controlled_tag ) ) ;
                        $t->member_resolve () ;
                        
                        // debug_r($t);
                        $res = $t->get_members_not_of_type ( $this->_type->_controlled_tag ) ;
                        // debug_r($res);
                        $fin_n = $this->group_assocs_by_role ( $res ) ;
                        // debug_r($fin);
                        $fin = $fin_lut = array () ;
                        if ( isset ( $fin_n[0] ) ) $fin = $fin_n[0] ;
                        if ( isset ( $fin_n[1] ) ) $fin_lut = $fin_n[1] ;
                        
                        $html .= '<h2>Things tagged with "'.$this->tag.'"</h2>' ;
                        if ( count ( $tags ) > 0 ) {
                            foreach ( $fin as $title => $items ) {
                                // $html .= '<h3>'.$title.'</h3> ' ;
                                $html .= $this->list_tags ( $title, $items, $tm->resolve_topic ( 'type', $fin_lut[$title] ) ) ;
                            }
                        } else {
                            $html .= '<p>Hmm. None?</p>' ;
                        }
                        return $html ;
                    }
                    break ;
            }
        }
        
        // note : Fake event. Our zombie object passes this through to us
        function _gui_section2 () {
            // return '<b>[2]</b> ' ;
        }
        
        // note : Fake event. Our zombie object passes this through to us
        function _gui_section3 () {
            // return '<b>[3]</b> ' ;
        }
        
        function clean_tag ( $tag ) {
            
            return preg_replace('/[^\da-z]/i', '_', strtolower ( $tag ) ) ;
        }
        
        function POST () {
            
            $in_tag = $this->clean_tag ( $this->glob->request->tag ) ;
            $in_type = $this->glob->request->type ;

            
            $tags = $this->glob->tm->query ( array ( 
                'name' => 'tag:'.$in_tag
            ) ) ;
            
            if ( is_array ( $tags ) && count ( $tags ) > 0 )
                return "Hmm. Tag [{$in_tag}] seems to already exist?" ;
            
            $t = '_'.$in_type ;
            
            $c = $this->glob->tm->create ( array ( 
                'label' => $in_tag,
                'type1' => $this->_type->$t,
                'name' => 'tag:'.$in_tag,
            ) ) ;
            
            echo $in_type." '".$this->glob->request->tag."' successfully created. Have fun" ;
        }
    }
