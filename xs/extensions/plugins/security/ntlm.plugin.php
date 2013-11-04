<?php

    /* NTLM plugin ; if you've got NTLM support in your infra-structure, this
     * plugin will automatically pop in your username, and you can use it for
     * prelimenary authentication and stuff like that, or go the full
     * authentication route by using a Authentication plugin in addition. */

    class xs_plugin_ntlm extends \xs\Action\Generic {

        private $debug = false ;
        private $headers = false ;

        public $meta = array (
            'name' => 'NTLM plugin',
            'description' => 'If NTLM is supported on your infra structure, this plugin will fill in your username automatically for you',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        function ___env () {

            // first, get those pesky HTTP headers
            $this->headers = $this->glob->request->get_headers() ;

            if ( $this->glob->request->__get('_auth', 'true') == 'false'
               && ( isset ( $this->headers['X-Process'] ) && $this->headers['X-Process'] != 'true' ) ) {

                  $this->glob->request->set_header ( 'Authorization' ) ;

               }

            $this->post_check () ;
        }

        // if this event is fired, do the NTLM hoe-down

        function ___on_user_config () {

            // debug('__NTLM :: on_user_config()') ;
            
            if ( trim ( $this->glob->request->__get ( 'f:xs-login-username' ) ) != '' ) {
                // debug('__NTLM :: on_user_config() :: EXITING!') ;
                return null ;
            }
            
            
            // If we want to use NTLM lookup?
            if ( isset ( $this->glob->config['user_management']['NTLM'] ) &&
                         $this->glob->config['user_management']['NTLM'] == true ) {

                if ( trim ( $this->glob->request->_user ) == '' ) {

                    // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] NTLM->___on_user_config ()"  ) ;
                    return $this->ntlm_lookup () ;

                } else {
                    // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] NTLM->___on_user_config () : Not initiated due to forced _user parameter"  ) ;
                }

            } 

            // returning null means we caught the event, but we're not really doing anything
            return null ;

        }

        function post_check () {

            // is someone POSTing stuff our way?
            // on some systems NTLM requires things POSTed to first be posted
            // with content-length:0 and then be sent back another ask for a secret
            // handshake, and will then re-POST the actual content (for example
            // all versions of Internet Explorer does this)

            if ( $this->glob->request->method() == 'POST' ) {

                if ( $this->debug ) $this->glob->logger->logInfo('[' . $_SERVER['REMOTE_ADDR'] . '] xs_plugin_ntlm : POST attempt : '.print_r ( $this->headers, true )  ) ;

                if ( isset ( $this->headers['Content-Length'] ) ) {

                    // content-length = 0? Probably IE doing its handshake thing.
                    // Well, then, give him his handshake!

                    if ( (int) $this->headers['Content-Length'] == 0 ) {


                        // $this->glob->logger->logInfo('[' . $_SERVER['REMOTE_ADDR'] . '] xs_plugin_ntlm : POST BOUNCE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!' ) ;

                        // Step 1 ; brew up a foul handshake soup

                        $msg2 = "NTLMSSP\x00\x02\x00\x00\x00" .
                                "\x00\x00\x00\x00" . // target name len/alloc
                                "\x00\x00\x00\x00" . // target name offset
                                "\x01\x02\x81\x00" . // flags
                                "\x00\x00\x00\x00\x00\x00\x00\x00" . // challenge
                                "\x00\x00\x00\x00\x00\x00\x00\x00" . // context
                                "\x00\x00\x00\x00\x00\x00\x00\x00" ; // target info len/alloc/offset

                        // Step 2 ; return the secret NTLM handshake signal to the browser,
                        //          indicating that we indeed speak sophisticated NTLM

                        header('HTTP/1.1 401 Unauthorized');
                        header('X-Process: true');
                        header('WWW-Authenticate: NTLM ' . trim(base64_encode($msg2)));
                        die() ;
                   }
                }
            }

        }

        function ntlm_lookup () {

            $debug = $this->debug ;

            // Don't do this whole hoopla if we're using an API, ok?
            if ( $this->glob->request->method() != 'POST' &&  ($this->glob->breakdown->concept != 'api' && $this->glob->breakdown->concept != '_api')   ) {

                if ( $debug ) $this->glob->logger->logInfo('[' . $_SERVER['REMOTE_ADDR'] . '] xs_plugin_ntlm : Not POST, not API' ) ;

                // Are we authorized?
                if ( !isset ( $this->headers['Authorization'] ) ) {

                    // Step 1 : Return 401 with www-Authenticate: NTLM to
                    // force NTLM authentication handshake session

                    header('HTTP/1.1 401 Unauthorized');
                    header('X-Process: true');
                    header('WWW-Authenticate: NTLM');
                    if ( $debug ) $this->glob->logger->logInfo('[' . $_SERVER['REMOTE_ADDR'] . '] xs_plugin_ntlm : message 1 (not authorized)' ) ;
                    die();
                }

                // Step 2 ; check what step 1 returns

                $auth = $this->headers['Authorization'];

                if (substr($auth, 0, 5) == 'NTLM ') {

                    $msg = base64_decode(substr($auth, 5));

                    if (substr($msg, 0, 8) != "NTLMSSP\x00")
                        die('error header not recognised');


                    if ($msg[8] == "\x01") {
                        $msg2 = "NTLMSSP\x00\x02\x00\x00\x00" .
                                "\x00\x00\x00\x00" . // target name len/alloc
                                "\x00\x00\x00\x00" . // target name offset
                                "\x01\x02\x81\x00" . // flags
                                "\x00\x00\x00\x00\x00\x00\x00\x00" . // challenge
                                "\x00\x00\x00\x00\x00\x00\x00\x00" . // context
                                "\x00\x00\x00\x00\x00\x00\x00\x00" ; // target info len/alloc/offset

                        // Step 2 ; return the secret NTLM handshake signal

                        header('HTTP/1.1 401 Unauthorized');
                        header('X-Process: true');
                        header('WWW-Authenticate: NTLM ' . trim(base64_encode($msg2)));
                        if ( $debug ) $this->glob->logger->logInfo('[' . $_SERVER['REMOTE_ADDR'] . '] xs_plugin_ntlm : message 2 (send handshake)' ) ;
                        // echo "!" ;
                        die();
                    }

                    // Step 3
                    if ($msg[8] == "\x03") {

                        // Ok, info returned. Pick out credentials from the return

                        // var_dump ( $msg ) ;

                        $cred = array (

                            // Inject basic user credentials into the temporary data object
                            'username'    => strtolower ( $this->get_msg_str($msg, 36 ) ),
                            'domain'      => $this->get_msg_str($msg, 28),
                            'workstation' => $this->get_msg_str($msg, 44),
                            'password'    => null  // null, since we can't extract a password through NTLM

                        ) ;

                        if ( $debug ) $this->glob->logger->logInfo('[' . $_SERVER['REMOTE_ADDR'] . '] xs_plugin_ntlm : message 3 (success) : ' . print_r ( $cred, true ) ) ;

                        // var_dump ( $_SERVER ) ;
                        // var_dump ( $this->glob->request ) ;+

                        // echo "[CRED]:" ; var_dump ( $cred ) ;
                        // echo "[CRED-DEBUG]:" ; print_r ( $msg ) ;

                        $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] NTLM->ntlm_lookup () returned '".$cred['username']."'"  ) ;

                        return $cred ;

                    }

                    // If you get here, then the authentication failed
                    echo "FAIL!" ;
                    return null ;
                }
            }

            // if all else fails, return blank array (null means do not call back)
            return array () ;
        }

        function get_msg_str($msg, $start, $unicode = true) {
            $len = (ord($msg[$start + 1]) * 256) + ord($msg[$start]);
            $off = (ord($msg[$start + 5]) * 256) + ord($msg[$start + 4]);
            if ($unicode) {
                return str_replace("\0", '', substr($msg, $off, $len));
            } else {
                return substr($msg, $off, $len);
            }
        }

    }
