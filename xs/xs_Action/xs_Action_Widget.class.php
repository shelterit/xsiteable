<?php

    /*
     * This is the action class for widgets. Note, however, that it functions as a
     * widget *controller*, and needs to support dealing with multiple instances
     * of that type of widget, possibly with different views and so forth. There's
     * an array in the foyer of the instances associated with this controller.
     */

    class xs_Action_Widget extends xs_Action {

        public $class_type = 'abstract' ;
        
        // The name of the widget controller (based on its class name)
        public $controller_name = null ;
        
        // The name of any instance we might be
        public $instance_id = null ;

        function GET_title ( $id ) { 
            return $this->prepare ( $this->_settings->title ) ;
        }
        function GET_menu () { return $this->prepare ('') ; }
        function GET_content () { return $this->prepare ('') ; }
        function GET_footer () { return $this->prepare ('') ; }

        function get_settings () {
            return $this->_settings->__get_array () ;
        }
        function get_properties () {
            return $this->_properties->__get_array () ;
        }
        function get_technicals () {
            return $this->_technical->__get_array () ;
        }
        function get_setting ( $s ) {
            return $this->_settings->$s ;
        }
        function get_property ( $p ) {
            return $this->_properties->$p ;
        }
        function get_technical ( $t ) {
            return $this->_technical->$t ;
        }
        
        function gui_setup ( $id = '' ) {

            // the widgets visual and behavioral options. Everything is disabled
            // by default (and upped based on users credentials)

            // debug_r ( $this, 'Action_Widget' ) ;
            $option = $this->get_widget_defaults () ;
            
            // get XML for our settings
            $settings = $this->get_xml_object ( $this->_settings ) ;
            if ( $settings == '' ) $option['edit'] = false ;
            
            // get XML for our properties
            $properties = $this->get_xml_object ( $this->_properties ) ;
            if ( $properties == '' ) $option['config'] = false ;

            // get XML for our technical details
            $technical = $this->get_xml_object ( $this->_technical ) ;
            if ( $technical == '' ) $option['technical'] = false ;

            // create XML representation for our options
            $options = '' ;
            foreach ( $option as $item => $state )
                if ( $state )
                    $options .= "<$item />" ;

            // a chunk of XML that will render the widget (through another call to the XSLT layer)
            $ret = "<nut:widget xmlns:nut='http://schema.shelter.nu/nut' widget-type='{$this->class_type}' name='".$this->controller_name."' id='".$this->instance_id."'>
                       <options>$options</options>
                       <settings>$settings</settings>
                       <properties>$properties</properties>
                       <technical>$technical</technical>
                    </nut:widget> " ;

            // debug_r(htmlentities($ret),' ');
            
            return $ret ;
        }
        
        function get_widget_defaults () {
            
            $default = false ;

            $option = array (
                'collapse' => $default,
                'close' => $default,
                'config' => $default,
                'edit' => $default,
                'move' => $default,
                'technical' => $default
            ) ;
            
            $security = $this->_get_module ( 'security' ) ;
            
            if ( $security->has_access ( 'widget:collapse', true ) ) {
                $option['collapse'] = true ;
            }
            if ( $security->has_access ( 'widget:config', false ) ) {
                $option['config'] = true ;
            }
            if ( $security->has_access ( 'widget:edit', false ) ) {
                $option['edit'] = true ;
            }
            if ( $security->has_access ( 'widget:manage', false ) ) {
                $option['collapse'] = true ;
                $option['edit'] = true ;
                $option['config'] = true ;
                $option['move'] = true ;
                $option['close'] = true ;
                $option['technical'] = true ;
            }
            return $option ;
        }

        function get_xml_object ( $inst ) {

            // objects or array are allowed
            $arr = $inst ;
            
            // incoming object?
            if ( is_object ( $inst ) )
                $arr = $inst->__get_array () ;
            
            //debug_r($arr,'action_widget_class');

            // create XML from these arrays
            $ret = '' ;
            foreach ( $arr as $idx => $value ) {
                if ( is_array ( $value ) )
                    $ret .= "<item name='$idx' array='true'>{$this->get_xml_object($value)}</item>" ;
                else
                    $ret .= "<item name='$idx'>$value</item>" ;
            }
            return trim ( $ret ) ;
        }

    function xsl_to_php_param ( $param ) {
        $ret = array () ;
        $e = explode ( '|', trim ($param ) ) ;
        foreach ( $e as $p ) {
            $r = explode ( ':', trim ($p ) ) ;
            // debug($r);
            if ( trim ( $r[0] ) != '' )
                $ret[trim($r[0])] = isset ($r[1]) ? trim($r[1]) : '' ;
        }
        return $ret ;
    }


    }
	