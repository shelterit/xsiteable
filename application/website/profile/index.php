<?php

class xs_action_instance extends \xs\Action\Webpage {

    public $page = array ( 'title' => 'Profile' ) ;

    public $_title = 'My profile' ;
    
    private $current_id = null ;
    
    
    function ___action () {
        
        $uid = trim ( $this->glob->breakdown->section ) ;
        $id = null ;
        
        $facets = array ( 'profile' => 'Profile' ) ;
        
        // id coming in?
        if ( $uid != '' ) {
            
            // yes, but is it numeric?
            if (is_numeric ( $uid ) ) {
                $this->current_id = $id = $uid ;
                $topics = $this->glob->tm->query ( array ( 'id' => $uid ) ) ;
                $topic = reset ( $topics ) ;
                if ( $topic ) {
                    $uid = substr ( $topic['name'], 5 ) ;
                }
                // debug_r($topic);
            } else {
                $topics = $this->glob->tm->query ( array ( 'name' => 'user:'.$uid ) ) ;
                $topic = reset ( $topics ) ;
                $id = $topic['id'] ;
            }
            
            if ( ! $topic ) {
                $this->set_template ( 'no_data' ) ;
                return ;
            }
            
            if ( ! isset ( $topic['email'] ) ) {
                $domain = isset ( $this->glob->config['user_management']['profile_email_domain'] ) 
                   ? $this->glob->config['user_management']['profile_email_domain']
                   : $_SERVER['HTTP_HOST'] ;
                $topic['email'] = $uid . '@' . $domain ;
            }
            $query_id = 'user_lookup_'.$uid ;
            
            // $topics = $this->glob->tm->query ( array ( 'id' => $uid ) ) ;
            // $topic = end( $topics ) ;
            

            $this->glob->data->register_datasource (

               // What is the token identifier for this datasource?
               'ad',

               // name of the driver
               'ad_ldap',

               // populate an array with config data needed
               array (
                    'base_dn'             => $this->_config ( 'ad_ldap', 'base_dn' ),
                    'account_suffix'      => $this->_config ( 'ad_ldap', 'account_suffix' ),
                    'domain_controllers'  => $this->_config ( 'ad_ldap', 'domain_controllers' ),
                    'username' => $this->_config ( 'ad_ldap', 'username' ),
                    'password' => $this->_config ( 'ad_ldap', 'password' )
               )

            ) ;

            $this->glob->data->register_query (

               // use the ad_ldap source
               'ad',

               // identifier for our query
               $query_id,

               "user/{$uid}",

               // the timespan of caching the result
               '+1 hour'
            ) ;

            // $e = $this->glob->data->get ( $id ) ;

            // var_dump ( $e ) ;


            // $this->glob->ad->ad_ldap_connect() ;

            // $user = $this->glob->ad->get_user ( $uid ) ;

            // $topic['id'] = $this->current_id ;

            $this->glob->stack->add ( 'xs_profile', $topic ) ;

        } else {

            $id = $this->glob->user->id ;
            
            $this->current_id = $id ;
            
            $this->glob->stack->add ( 'xs_profile', 
                    array_merge ( $this->glob->user->__getArray(), array ('id' => $this->current_id) ) 
            ) ;
        }
        /*
        $this->glob->stack->add ( 'xs_env', $_ENV ) ;
        $this->glob->stack->add ( 'xs_cookie', $_COOKIE ) ;
        $this->glob->stack->add ( 'xs_request', $_REQUEST ) ;
        $this->glob->stack->add ( 'xs_session', $_SESSION ) ;

        $this->glob->stack->add ( 'xs_info', phpinfo_array () ) ;
        */
        $this->log ( 'READ' ) ;
        
        // debug_r($this->glob->user);
        $this->glob->stack->add ( 'xs_facets', $facets ) ;
        
        
        // comments?
        $comments = $this->_get_module ( 'comments' ) ;
        $this->glob->stack->add ( 'xs_comments', $comments->get_for_topic ( $id ) ) ;
        
        
        
    }

}
