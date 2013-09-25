<?php
/*
 * Conteg.include
 *
 *  Content-Negotiation + Cache-Control for PHP-produced HTTP web-output
 *
 * Public functions:
 *    Conteg()               - constructor
 *    charsetAccepted()      - returns TRUE/FALSE (charset not acceptable)
 *    compress()             - returns TRUE/FALSE (whoops)
 *    getAccept()            - returns array ($accept)
 *    getAcceptCharset()     - returns array ($accept_charset)
 *    getAcceptLang()        - returns array ($accept_language)
 *    getCompLevel()         - returns int between 0 and 9 inclusive
 *    getReferer()           - returns array (referer uri + parts)/FALSE
 *    getSize()              - returns $gzsize/FALSE
 *    isError()              - returns $_error/FALSE
 *    isResponseNoContent()  - returns 304 or 406 or 416/FALSE
 *    mediaTypeAccepted()    - returns TRUE/FALSE (media-type not acceptable)
 *    negotiateEncoding()    - returns array/FALSE; negotiates acceptable encodings
 *    requestNoCache()       - returns TRUE/(int=max allowed cache age, secs)/FALSE (no request)
 *    requestNoStore()       - returns TRUE/FALSE (no such request header)
 *    sendStatusHeader()     - returns TRUE/FALSE (error)
 *    setSearch()            - returns TRUE/FALSE (error)
 *    setup()                - returns TRUE/FALSE (error)
 *    show()                 - returns TRUE/FALSE (error)
 *
 * Private functions:
 *    _checkRange()          - returns TRUE (send a 206) / FALSE (send a 416)
 *    _initRequest()         - returns TRUE
 *    _initResponse()        - returns TRUE
 *    _loadAvgBSD()          - returns Load Average / FALSE
 *    _loadAvgLinux()        - returns Load Average / FALSE
 *    _loadAvgUnix()         - returns Load Average / FALSE
 *    _trigger_error()       - exit() or return FALSE
 *
 * Copyright 2009 -> 2004 (c) All Rights Reserved.
 *                            All Responsibility Yours.
 *
 * This code is released under the GNU LGPL Go read it over here:
 * http://www.gnu.org/copyleft/lesser.html
 *
 *    See end of Class for help + advice, changelog, etc.
 *
 *    TAB=3
 *
 * @category HTTP
 * @version $Id: Conteg.include, v0.13.9 2010-08-20
 * @author Alex KEMP http://www.modem-help.co.uk/
 */
/* Constants
 *
 * - added by AK 20 Mar 04
 */
// -------------- Start of Global Defines -----------------

	define('_GZIP_ENCODE_STAT', '__COMPRESSION__');
	define('_GZIP_ENCODE_LEVEL', '__COMPRESSION_LEVEL__');

// -------------- End of Global Defines -------------------
// -------------- Start of Class Declaration --------------
	class Conteg {
		// public variables
		// Response/Request-Header variables
		var $accept							= array();	// 1.0 sec D.2.1; 1.1 sec 14.1 (request header)
		var $accept_charset				= array();	// 1.0 sec D.2.2; 1.1 sec 14.2 (request header)
		var $accept_encoding				= array();	// 1.0 sec D.2.3; 1.1 sec 14.3 (request header)
		var $accept_language				= array();	// 1.0 sec D.2.4; 1.1 sec 14.4 (request header)
		var $browserAgent;								// string
		var $browserOS;									// string
		var $browserVersion;								// string
		var $cache_control				= '';			// 1.1 rfc2616 sec 14.9.4, sec 13.4 (response header)
		var $content_type					= '';			// 1.0 sec 10.5; 1.1 sec 14.17 (response header)
		var $content_lang					= '';			// 1.0 sec D.2.5; 1.1 sec 14.12 (response header)
		var $etag							= '';			// 1.1 rfc2616-sec14.html#sec14.19 (response header)
		var $expires;										// timestamp; 1.0 rfc1945.txt section 10.7 (response header)
		var $if_match						= array();	// 1.1 rfc2616-sec14.html#sec14.24 (request header)
		var $if_modified_since			= '';			// timestamp; 1.0 rfc1945.txt section 10.9 (request header)
		var $if_none_match				= array();	// 1.1 rfc2616-sec14.html#sec14.26 (request header)
		var $if_range						= '';			// timestamp/string; 1.1 sec14.html#sec14.27 (request)
		var $if_unmodified_since		= '';			// timestamp; 1.1 sec14.html#sec14.28 (request header)
		var $last_modified;								// timestamp; 1.0 rfc1945.txt section 10.10 (response)
		var $method							= '';			// GET, POST, HEAD
		var $now								= '';			// timestamp
		var $pragma;										// (string)
		var $protocol						= '';			// HTTP/1.1 or HTTP/1.0
		var $range							= array();	// 1.1 rfc2616-sec14.html#sec14.35 (request header)
		var $referer						= array();	// (sic) (array of strings) referring webpage URI
		var $ssl								= FALSE;		// T/F
		var $vary							= '';			// 1.1 rfc2616-sec14.html#sec14.44 (response header)
		var $user_agent					= '';			// 1.1 rfc2616-sec14.html#sec14.43 (request header)
		// compress variables - set by prog processing
		var $data;											// the un-compressed content
		var $encoding;										// string; the actual data encoding to apply
		var $gzdata;										// the compressed content
		var $gzsize;										// bytes; size of the compressed content
		var $level;											// Compression level (0-9)
		var $search							= array();	// item(s) to replace before compression
		var $size;											// bytes; size of the uncompressed content
		var $stat;											// compression percentage (0-100, 0==none)
		var $replace						= array();	// replace $search item(s)
		// private variables
		var $_cache_control_response	= array(		// (response header)
//			'max-age'				=> (int),			// 1.1 rfc2616 sec 14.9.4, sec 13.4
			'must-revalidate'		=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
			'no-cache'				=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
			'no-store'				=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
			'no-transform'			=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
//			'post-check'			=> (int),
//			'pre-check'				=> (int),
			'private'				=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
			'proxy-revalidate'	=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
			'public'					=> FALSE				// 1.1 rfc2616 sec 14.9.4, sec 13.4
//			's-maxage'				=> (int)				// 1.1 rfc2616 sec 14.9.4, sec 13.4
		);
		var $_cache_control_request	= array(		// (request header)
//			'max-age'				=> (int),			// 1.1 rfc2616 sec 14.9.4, sec 13.4
//			'no-cache'				=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
//			'no-store'				=> FALSE,			// 1.1 rfc2616 sec 14.9.4, sec 13.4
//			'pragma'					=> 'no-cache'		// (string); 1.0 rfc1945 sec 11.12; 1.1 sec 14.32 (request)
		);
		var $_charset;										// string; doc charset; applied to $content_type if http/1.1
		var $_contentType;								// string; if !http/1.1 is === $content_type (response hdr)
		var $_encodings					= array();	// string; data encoding(s) on the menu
		var $_error							= '';			// string; contents of internal error message
		var $_httpStatus;									// int; user-enforced HTTP Status
		var $_inputNote;									// string; Apache-Note name for deflate Input Note
		var $_is304							= FALSE;		// T/F   Not Modified        rfc2616-sec10.html#sec10.3.5
		var $_is404;										// TRUE/FALSE (use normal header)
		var $_is406							= FALSE;		// T/F   Not Acceptable      rfc2616-sec10.html#sec10.4.7
		var $_is412							= FALSE;		// T/F   Precondition Failed rfc2616-sec10.html#sec10.4.13
		var $_isWeakETag;									// TRUE/FALSE 1.1 rfc2616-sec3.html#sec3.11
		var $_isExtETag					= FALSE;		// TRUE/FALSE ETag obtained from outside Class
		var $_no410;										// TRUE/FALSE (transform 404 header to 410 header)
		var $_other_var;									// string; ref: $etag; to flag some page aspect has changed
		var $_outputNote;									// string; Apache-Note name for deflate Output Note
		var $_noAccept;									// TRUE/FALSE (negotiate acc to Accept header)
		var $_noAcceptCharset;							// TRUE/FALSE (negotiate acc to Accept-Charset header)
		var $_noAcceptEncode;							// TRUE/FALSE (negotiate acc to Accept-Encoding header)
		var $_noAcceptLang;								// TRUE/FALSE (negotiate acc to Accept-Language header)
		var $_noAcceptRanges;							// TRUE/FALSE (negotiate acc to Range, If-Range header)
		var $_noContentType;								// TRUE/FALSE (use Content-Type Response header)
		var $_noContentLang;								// TRUE/FALSE (use Content-Language Response header)
		var $_noDupes;										// TRUE/FALSE (use Duplicate `Status` header)
		var $_noETag;										// TRUE/FALSE (use ETags)
		var $_noExpires;									// TRUE/FALSE (use Expires)
		var $_noLastModified;							// TRUE/FALSE (use Last-Modified)
		var $_noMSErrorFix;								// TRUE/FALSE (avoid MSIE `friendly` error pages)
		var $_noNotes;										// TRUE/FALSE (use Apache Notes)
		var $_noPrint;										// TRUE/FALSE (print immediate on declaration)
		var $_noReferLC;									// TRUE/FALSE (lc all url-parts of the $referer array)
		var $_noSearch;									// TRUE/FALSE (use global search/replace)
		var $_noUserAgent;								// TRUE/FALSE (negotiate acc to User-Agent header)
		var $_noVary;										// TRUE/FALSE (use Vary Response header)
		var $_port							= '';			// string; server-port, ref: $etag
		var $_preferOrder					= array();	// array; array of int => value pairs for IM, IMS, etc.
		var $_query							= '';			// string; ref: $etag
		var $_ratioNote;									// string; Apache-Note name for deflate Ratio Note
		var $_scriptName					= '';			// string; ref: $etag
		var $_SERVER_ARRAY;								// string; used in _initRequest()
		var $_SESSION_ARRAY				= '_SESSION';	// string; used in _initRequest()
		var $_session						= '';			// string; ref: $etag
		var $_num_cpu;										// number of CPUs in the machine (Mar 07 now setup param)
		var $_version						= '0.13.9';	// Version of the Conteg class
/*
 *  Conteg constructor - initialises the Class
 *
 * parameter: $param = array() - array of key => value pairs
 *            (see setup() for details)
 */
		function Conteg(
			$param = array()
		) {
			// sanity check
			if( ob_get_length() === FALSE ) {
				ob_start();
				$this->_trigger_error('Conteg::Conteg: output buffering switched ON; any output before this point will trigger errors when headers are sent.', E_USER_NOTICE );
			}

// clear to go; init Class variables
			$this->now	= time();
			$this->setup( $param );
			$this->_initRequest();

			if( $this->_noPrint == TRUE ) return;
			else $this->show();
		}	// Conteg::Conteg()
/*
 * charsetAccepted() - Whether a charset is acceptable or not.
 *
 * parameter: $mediaType; default: $_charset
 *
 *  Returns TRUE/FALSE (charset not acceptable)
 *
 * - 2007-04-29 teeny-weeny bugfix: now returns first acceptable charset in original case
 */
		function charsetAccepted(
			$charset = array()
		) {
			if( empty( $charset ) and empty( $this->_charset )) return $this->_trigger_error('Conteg::charsetAccepted(): Supplied charset and Class charset both empty.', E_USER_WARNING );
			elseif( empty( $this->accept_charset )) return $this->_trigger_error('Conteg::charsetAccepted(): Request charset(s) empty, cannot compare.', E_USER_NOTICE );
			else if( empty( $charset )) $charset = array( $this->_charset );

			if( isset( $this->accept_charset['*']) and ( $this->accept_charset['*'] > 0 )) return TRUE;
			else foreach( $charset AS $val ) {
				$val_upper	= strtoupper( $val );
				if( isset( $this->accept_charset[ $val_upper ]) and ( $this->accept_charset[ $val_upper ] > 0 )) return $val;
			}

			return FALSE;	// no acceptable charset found
		}	// Conteg::charsetAccepted()
/*
 * compress() - encode the current contents
 *
 * Returns: TRUE or FALSE (problem obtaining ob_contents or $data empty)
 *
 *    note 1: MSIE will show `friendly` error messages in certain cirmcumstances. The
 *           +Microsoft-approved fix is to pad the error message to > 512 bytes.
 *            See http://support.microsoft.com/kb/218155
 *                http://support.microsoft.com/kb/294807
 *
 * - 18 Feb 07 added $_noMSErrorFix check
 * - 16 Sep 05 added -AK
 */
		function compress(
			$refresh = TRUE	// (TRUE/FALSE) refresh $data/reuse existing $data
		) {
// sanity checks
			if( $refresh ) {
				if(( $this->data = ob_get_contents()) === FALSE ) return $this->_trigger_error('Conteg::compress(): No ob_contents to compress.', E_USER_ERROR );
			} else if( empty( $this->data )) return $this->_trigger_error('Conteg::compress(): No $data contents to compress.', E_USER_NOTICE );

			$encoding	= ( empty( $this->encoding ))
				? 'identity'
				: (( strlen( $this->data ) > 1000 )
					? $this->encoding
					: 'identity'							// not worth the effort
				);

// MSIE check
			$this->size			= strlen( $this->data );
			// blasted MSIE will show `friendly` error pages in certain situations
			// the following is to fix that
			if(( $this->size < 513 ) and
				( !empty( $this->_httpStatus )) and
				( $this->_noMSErrorFix == FALSE ) and
				( $this->browserAgent == 'MSIE') and
				(( $this->_httpStatus == 400 ) or
					( $this->_httpStatus == 403 ) or
					( $this->_httpStatus == 404 ) or
					( $this->_httpStatus == 405 ) or
					( $this->_httpStatus == 406 ) or
					( $this->_httpStatus == 408 ) or
					( $this->_httpStatus == 409 ) or
					( $this->_httpStatus == 410 ) or
					( $this->_httpStatus == 500 ) or
					( $this->_httpStatus == 501 ) or
					( $this->_httpStatus == 505 )
				)
			) {
				// content needs to be > 512 bytes
				$padBytes	= 513 - $this->size;
				if( substr_count( $this->data, '</body>')) {
					// this is html
					$replace		= "<p>&nbsp;</p>\n";
					$i				= ( int ) ceil( $padBytes / 14 );
					$replace		= str_repeat( $replace, $i ) .'</body>';
					$this->data	= str_replace('</body>', $replace, $this->data );
				} elseif( substr_count( $this->data, '</BODY>')) {
					// this is HTML
					$replace		= "<P>&nbsp;</P>\n";
					$i				= ( int ) ceil( $padBytes / 14 );
					$replace		= str_repeat( $replace, $i ) .'</BODY>';
					$this->data	= str_replace('</BODY>', $replace, $this->data );
				} else {
					// this is plain-text
					$pad			= "\n_";
					$i				= ( int ) ceil( $padBytes / 2 );
					$this->data	= $this->data . str_repeat( $pad, $i );
				}
				$this->size	= strlen( $this->data );
			}	// end MSIE `friendly` error page fix

// clear to go; init compression variables
			switch( $encoding ) {
				case 'gzip':		// see http://www.faqs.org/rfcs/rfc1952
				case 'x-gzip':
					if( version_compare(  PHP_VERSION, '4.2', '>=')) {
						$this->gzdata	= gzencode( $this->data, $this->level, FORCE_GZIP );
					} else {	// effective kludge for php < 4.2 (no level parameter)
						$crc				= crc32( $this->data );
						$this->gzdata	= "\x1f\x8b\x08\x00\x00\x00\x00\x00".	// gzip header
							gzcompress( $this->data, $this->level );
						$this->gzdata	= substr( $this->gzdata, 0, -4 ).		// fix crc bug
							pack('V', $crc ) . pack('V', $this->size );
					}
					$this->gzsize		= strlen( $this->gzdata );
					$this->stat			= round(( 1 - ( $this->gzsize / $this->size )) * 100 );
					break;
				case 'deflate':	// see http://www.faqs.org/rfcs/rfc1951
					$this->gzdata		= gzdeflate( $this->data, $this->level );
					$this->gzsize		= strlen( $this->gzdata );
					$this->stat			= round(( 1 - ( $this->gzsize / $this->size )) * 100 );
					break;
				case 'compress':	// see http://www.faqs.org/rfcs/rfc1950
				case 'x-compress':
					$this->gzdata		= gzcompress( $this->data, $this->level );
					$this->gzsize		= strlen( $this->gzdata );
					$this->stat			= round(( 1 - ( $this->gzsize / $this->size )) * 100 );
					break;
				case 'identity':
				default:
					$this->crc			= NULL;
					$this->gzdata		= '';
					$this->gzsize		= $this->size;
					$this->stat			= '(none)';
					$this->encoding	= 'identity';
			}	// switch( $encoding )

			return TRUE;
		}	// Conteg::compress()
/*
 * getAccept() - For external negotiation on media-type.
 *
 *  Returns array ($accept).
 */
		function getAccept() {
			// note: double arsort returns array to Request order
			return arsort( $this->accept );
		}
/*
 * getAcceptCharset() - For external negotiation on charset.
 *
 *  Returns array ($accept_charset).
 */
		function getAcceptCharset() {
			// note: double arsort returns array to Request order
			return arsort( $this->accept_charset );
		}
/*
 * getAcceptLang() - For external negotiation on language.
 *
 *  Returns array ($accept_language).
 */
		function getAcceptLang() {
			// note: double arsort returns array to Request order
			return arsort( $this->accept_language );
		}
/*
 * getCompLevel() - The level of compression we should use.
 *
 *  Returns an int between 0 and 9 inclusive.
 *
 *  Help: if you use an OS other then *nix please send the
 *+ code to make this work with your OS - Thanks, AK
 *
 * 2009-04-17 rewritten + updated for generic Unix after input from oriceon
 *            see http://forums.modem-help.co.uk/viewtopic.php?t=603
 */
		function getCompLevel() {

			if( $loadAvg = $this->_loadAvgLinux()) {
			} elseif( $loadAvg = $this->_loadAvgBSD()) {
			} else	$loadAvg	= $this->_loadAvgUnix();

			if( $loadAvg ) {
				$cl		= ( 1 - $loadAvg ) * 10;
				$level	= ( int ) max( min( 9, $cl ), 0 );
			} else {
				$level	= 3;
			}

			return $level;
		}	// Conteg::getCompLevel()
/*
 * getReferer() - The (array of parts of the) Referer (sic) string.
 *
 *  These are the possible parts of the associative array returned:
 *     'uri'       - full, original referer URI; always present
 *     'scheme'
 *     'user'
 *     'pass'
 *     'host'
 *     'port'
 *     'path'
 *     'query'
 *     'fragment'
 *
 *    note 1: at least `uri` + one other is present
 *+   note 2: auto-urldecoded, (not `uri`)
 *+   note 3: lower-cased if $_noReferLC == FALSE (default), (not `uri`)
 *+   example URI: scheme://user:pass@host:port/path?query#fragment
 *
 *  Returns $referer array/FALSE
 *
 * - 16 Feb 07 added -AK
 */
		function getReferer() {

			return ( empty( $this->referer )) ? FALSE : $this->referer;
		}
/*
 * getSize() - The size of the (un/)compressed data.
 *
 *  Returns $gzsize (same as $size when not compressed)/FALSE
 */
		function getSize() {

			return ( $this->gzsize ) ? $this->gzsize : FALSE;
		}
/*
 * isError() - return $_error/FALSE
 */
		function isError() {
			return ( $this->_error != '') ? $this->_error : FALSE;
		}
/*
 * isResponseNoContent() - Whether the response will be Status 304, 406 or 412
 *
 *  Returns 304, 406 or 412/FALSE (content required)
 *
 * - 2010-08-20 added
 */
		function isResponseNoContent() {
			if( !empty( $this->_is304 ))		return 304;		// Not Modified
			elseif( !empty( $this->_is406 ))	return 406;		// Not Acceptable
			elseif( !empty( $this->_is412 ))	return 412;		// Precondition Failed
			else										return FALSE;	// content required
		}	// Conteg::isResponseNoContent()
/*
 * mediaTypeAccepted() - Whether a media-type is acceptable or not.
 *
 * parameter: $mediaType; default: $_contentType
 *
 *  Returns TRUE/FALSE (media-type not acceptable) (eg text/html)
 *
 * - 2007-04-29 bugfix: was `isset( $this->accept_charset[])', s/b `isset( $this->accept[])'
 *+							$t originally set via an `if()'
 */
		function mediaTypeAccepted(
			$mediaType = array()
		) {
			if( empty( $mediaType ) and empty( $this->_contentType )) return $this->_trigger_error('Conteg::mediaTypeAccepted(): Supplied media-type and Class _contentType both empty.', E_USER_WARNING );
			elseif( empty( $this->accept )) return $this->_trigger_error('Conteg::mediaTypeAccepted(): Request media-type(s) empty, cannot compare.', E_USER_NOTICE );
			else if( empty( $mediaType )) $mediaType = array( $this->_contentType );

			if( isset( $this->accept['*/*']) and ( $this->accept['*/*'] > 0 )) return TRUE;
			else foreach( $mediaType AS $val ) {
				if( isset( $this->accept[ $val ]) and ( $this->accept[ $val ] > 0 )) return TRUE;
				$t	= (( $p = strpos( $val, '/')) !== FALSE ) ? substr( $val, 0, $p ) .'/*' : 'bugfix';
				if( isset( $this->accept[ $t ]) and ( $this->accept[ $t ] > 0 )) return TRUE;
			}

			return FALSE;	// no acceptable media-type found
		}	// Conteg::mediaTypeAccepted()
/*
 * negotiateEncoding() - Negotiate acceptable encoding(s)
 *
 * parameter: $encoding: array of acceptable encodings; choose from:
 *                       gzip, compress, deflate, identity
 *                       see rfc2616-sec3.html#sec3.5
 *             http/1.0: x-gzip, x-compress
 *   default: $_encodings or ALL encodings
 *
 *  Returns array of accepted encodings/FALSE (none supplied acceptable)
 *
 * - 2007-10-08 mod: $_noUserAgent added; all $vary processing moved to _initResponse()
 */
		function negotiateEncoding(
			$encoding = array()
		) {
			if( empty( $this->accept_encoding )) return $this->_trigger_error('Conteg::negotiateEncoding(): Request encoding(s) empty, cannot compare.', E_USER_NOTICE );
			elseif( empty( $encoding ) and empty( $this->_encodings )) {
				$encoding	= ( $this->protocol == 'HTTP/1.1')
					? array('gzip', 'compress', 'deflate')
					: array('x-gzip', 'x-compress');
			} else if( empty( $encoding )) $encoding = $this->_encodings;

			foreach( $this->accept_encoding AS $key => $val ) {
				if( in_array( $key, $encoding )) {
					// check for media-type + browser bugs
					switch( $key ) {
						case 'gzip':
						case 'x-gzip':
						case 'compress':
						case 'x-compress':
						case 'deflate':
							if( empty( $this->_contentType )) {
								// Care! cannot check every possible content-type
								if(( $magic = ob_get_contents()) === FALSE ) {
									break;	// in fact, cannot check any, so safety-first
								} else $magic = substr( $magic, 0, 4 );
								if( substr( $magic, 0, 2 ) === '^_')				break;	// gzip data
								elseif( substr( $magic, 0, 3 ) === 'GIF')			break;	// gif images
								elseif( substr( $magic, 0, 2 ) === "\xFF\xD8")	break;	// jpeg images
								elseif( substr( $magic, 0, 4 ) === "\x89PNG")	break;	// png images
								elseif( substr( $magic, 0, 2 ) === 'PK')			break;	// pk zip file
								elseif( substr( $magic, 0, 3 ) === 'FWS') {					// Shockwave Flash
									if(
										( !$this->_noUserAgent ) and
										( !empty( $this->browserOS )) and
										( $this->browserOS == 'Win')
									) {
										// Flash on windows incorrectly claims to accept gzip'd content.
										break;
									}
								// assume text beyond this point
								} elseif(( !empty( $this->browserAgent )) and ( !empty( $this->browserVersion ))) {
									// see schroepl.net/projekte/mod_gzip/browser.htm
									if(( $this->browserAgent == 'MOZILLA') and ereg('^([0-9])\.([0-9]{1,2})', $this->browserVersion, $match )) {
										// Netscape 4.x has problems but does not know it
										if( $match[ 1 ] == '4') break;
									}
								}
							} else {	// if( empty( $this->_contentType ))
								switch( $this->_contentType ) {					// see ietf.org/rfc/rfc1521.txt
									case 'text/html':
									case 'text/plain':
									case 'text/css':
									case 'text/xml':
									case 'application/xml':
									case 'application/xhtml+xml':
									case 'application/rss+xml':
										if(
											( empty( $this->browserAgent )) or
											( empty( $this->browserVersion ))
										) {
											// trust that they know what they are doing
										} else if( !$this->_noUserAgent ) {		// browser-check
											// see schroepl.net/projekte/mod_gzip/browser.htm
											if(( $this->browserAgent == 'MOZILLA') and ereg('^([0-9])\.([0-9]{1,2})', $this->browserVersion, $match )) {
												if( $match[ 1 ] == '4') {
													// Netscape 4.x has problems but does not know it
													if( ereg('0[678]', $match[ 2 ])) {	// 4.06-4.08? forget it
														break 2;
													} else if( $this->_contentType != 'text/html') break 2;
												}
											}	// end Netscape 4.x check
											if(( !empty( $this->browserOS )) and ( $this->browserOS == 'Mac')) {
												if(( $this->browserAgent == 'MSIE') and ereg('^([0-9])', $this->browserVersion, $match )) {
													if( $match[ 1 ] <= '5') {	// reported php manual gzcompress.php
														break 2;
													}
												}
											}
										}	// end browser-check
										break;
									case 'application/octet-stream':	// a few examples...
									case 'audio/basic':
									case 'image/gif':
									case 'image/jpeg':
									case 'image/png':
									case 'video/mpeg':
									default:									// ...all designated not to be encoded
										break 2;
								}	// switch( $this->_contentType )
							}	// if( empty( $this->_contentType )) else
						case 'identity':
						default:
							$accepted[ $key ]	= $this->accept_encoding[ $key ];
					}	// switch( $key )
				}	// if( in_array( $key, $encoding ))
			}	// foreach( $encoding AS $key => $val )

			
			if( isset( $accepted )) {
				arsort( $accepted );	// note: double arsort returns array to Request order
				return $accepted;
			} else return FALSE;	// no acceptable encoding found */
		}	// Conteg::negotiateEncoding()
/*
 * requestNoCache() - Reports state of client Request re: Cache-Control; specifically:
 *   `Pragma: no-cache' Request header        (http/1.0 rfc1945 sec 11.12)
 *   `Cache-Control: no-cache' Request header (http/1.1 rfc2616-sec14.html#sec14.9.4)
 *   `Cache-Control: max-age' Request header  (http/1.1 rfc2616-sec14.html#sec14.9.4).
 *
 * returns TRUE/(int)/FALSE
 *   TRUE  = do not use a cache
 *   (int) = max allowed age of cache in secs
 *   FALSE = no such Request
 */
		function requestNoCache() {
			if( empty( $this->_cache_control_request )) return FALSE;
			elseif( isset( $this->_cache_control_request['max-age'])) {
				if( $this->_cache_control_request['max-age'] > 0 ) return $this->_cache_control_request['max-age'];
				else return TRUE;
			} elseif( isset( $this->_cache_control_request['no-cache'])) {
				return TRUE;
			} else if(
				isset( $this->_cache_control_request['pragma']) and
				( $this->_cache_control_request['pragma'] == 'no-cache')
			) {
				// "The server MUST NOT use a cached copy when responding to such a request"
				// (http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.4)
				// other if() clauses follow on from this assertion
				return TRUE;
			}

			return FALSE;
		}	// Conteg::requestNoCache()
/*
 * requestNoStore() - Reports state of client Request re: Cache-Control: no-store.
 *                    see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.2
 *
 * returns TRUE/FALSE (no such request)
 */
		function requestNoStore() {
			return ( isset( $this->_cache_control_request['no-store']))
				? TRUE
				: FALSE;
		}
/*
 * sendStatusHeader() - Sends the requested Status header
 *                      drawn from http://www.php.net/manual/en/function.header.php
 *                     +(Ciantic, 24-Dec-2005 02:07).
 *                      See http://www.w3.org/Protocols/rfc1945/rfc1945.txt section 6.1
 *                      http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6.1
 * Note 1: duplicate `Status` headers:
 *                      CGI version requires `Status` header, although standard 'HTTP/'
 *                     +headers *should* be parsed by PHP and turned into `Status` headers.
 *                     +It was supposed to be fixed in v4.3.5, although some folks have
 *                     +reported fixing probs with MSIE by sending duplicate Status headers.
 *                     +As sending a duplicate Status header will not cause any problem it
 *                     +is on by default. Init Class with `'dupe_status_header' => FALSE` to
 *                     +switch that behaviour OFF.
 * Note 2: transform 404 headers to 410 headers
 *                      Most 404 'Page Not Found' headers should actually be 410 'Page Gone'
 *                     +headers. Therefore, when $status = 404 and scheme is HTTP/1.1, all
 *                     +404 headers are transformed to 410 headers. Init Class with
 *                     +`'404_to_410' => FALSE` to switch that behaviour OFF.
 * Note 3: HTTP/1.1-specific-code behaviour under HTTP/1.0 schemes
 *                      This routine sends HTTP/1.1 status text regardless of the actual
 *                     +scheme employed (for humans only, does not affect browser behaviour).
 *                     +It also will allow a HTTP/1.1-specific status code to be sent to a
 *                     +browser which declares an earlier scheme. In that situation, the RFC
 *                     +advises the browser to treat the code as the base-code. eg 410 will be
 *                     +read as 400. To avoid this behaviour, use the $test parameter == TRUE
 *                     +to check first, then re-call with the appropriate code.
 *
 * returns TRUE/FALSE (error)
 *
 * 2007-07-07 $file + $line added to headers_sent() -AK
 * 2007-02-17 added $_no410 behaviour + bugfix (no headers when HTTP/1.1 status under HTTP/1.0 scheme)
 * 2006-07-02 added -AK
 */
		function sendStatusHeader(
			$status	= 200,
			$test		= FALSE	// discover whether a HTTP/1.1 header is valid for this request or not
		) {
			static $http	= array();

			// sanity checks
			if( empty( $this->protocol )) {
				return $this->_trigger_error('Conteg::sendHeader(): Cannot function; $this->protocol has not yet been set.', E_USER_ERROR );
			} elseif( headers_sent( $file, $line )) {
				return $this->_trigger_error("Conteg::sendHeader(): Cannot function; headers already sent to browser in $file on line $line.", E_USER_ERROR );
			} else if( $this->isError()) return FALSE;

			// one-time init
			if( empty( $http ))	$http	= array(
				100 => array( 0 => '',								1 => 'Continue'),
				101 => array( 0 => '',								1 => 'Switching Protocols'),
				200 => array( 0 => 'OK',							1 => 'OK'),
				201 => array( 0 => 'Created',						1 => 'Created'),
				202 => array( 0 => 'Accepted',					1 => 'Accepted'),
				203 => array( 0 => '',								1 => 'Non-Authoritative Information'),
				204 => array( 0 => 'No Content',					1 => 'No Content'),
				205 => array( 0 => '',								1 => 'Reset Content'),
				206 => array( 0 => '',								1 => 'Partial Content'),
				300 => array( 0 => '',								1 => 'Multiple Choices'),
				301 => array( 0 => 'Moved Permanently',		1 => 'Moved Permanently'),
				302 => array( 0 => 'Moved Temporarily',		1 => 'Found'),
				303 => array( 0 => '',								1 => 'See Other'),
				304 => array( 0 => 'Not Modified',				1 => 'Not Modified'),
				305 => array( 0 => '',								1 => 'Use Proxy'),
				307 => array( 0 => '',								1 => 'Temporary Redirect'),
				400 => array( 0 => 'Bad Request',				1 => 'Bad Request'),
				401 => array( 0 => 'Unauthorized',				1 => 'Unauthorized'),
				402 => array( 0 => '',								1 => 'Payment Required'),
				403 => array( 0 => 'Forbidden',					1 => 'Forbidden'),
				404 => array( 0 => 'Not Found',					1 => 'Not Found'),
				405 => array( 0 => '',								1 => 'Method Not Allowed'),
				406 => array( 0 => '',								1 => 'Not Acceptable'),
				407 => array( 0 => '',								1 => 'Proxy Authentication Required'),
				408 => array( 0 => '',								1 => 'Request Time-out'),
				409 => array( 0 => '',								1 => 'Conflict'),
				410 => array( 0 => '',								1 => 'Gone'),
				411 => array( 0 => '',								1 => 'Length Required'),
				412 => array( 0 => '',								1 => 'Precondition Failed'),
				413 => array( 0 => '',								1 => 'Request Entity Too Large'),
				414 => array( 0 => '',								1 => 'Request-URI Too Large'),
				415 => array( 0 => '',								1 => 'Unsupported Media Type'),
				416 => array( 0 => '',								1 => 'Requested Range Not Satisfiable'),
				417 => array( 0 => '',								1 => 'Expectation Failed'),
				500 => array( 0 => 'Internal Server Error',	1 => 'Internal Server Error'),
				501 => array( 0 => 'Not Implemented',			1 => 'Not Implemented'),
				502 => array( 0 => 'Bad Gateway',				1 => 'Bad Gateway'),
				503 => array( 0 => 'Service Unavailable',		1 => 'Service Unavailable'),
				504 => array( 0 => 'Gateway Time-out',			1 => 'Gateway Time-out'),
				505 => array( 0 => 'Gateway Time-out',			1 => 'HTTP Version Not Supported')
			);

			if( $this->protocol != 'HTTP/1.1') {
				if( $test ) {
					if( empty( $http[ $status ][ 0 ]))	return FALSE;
					else											return TRUE;
				}

				header("HTTP/1.0 $status ". $http[ $status ][ 1 ]);
				if( !$this->_noDupes )	header("Status: $status ". $http[ $status ][ 1 ]);
			} else {
				if( $test )									return TRUE;

				if(( $status == 404 ) and ( !$this->_no410 ))	$status	= 410;

				header("HTTP/1.1 $status ". $http[ $status ][ 1 ]);
				if( !$this->_noDupes )	header("Status: $status ". $http[ $status ][ 1 ]);
			}
			return TRUE;
		}	// Conteg::sendStatusHeader()
/*
 * setSearch() - Sets $search + $replace; used for stats reporting, etc.
 *
 * returns TRUE/FALSE (error)
 */
		function setSearch(
			$replace	= array()		// format: array( $search_item => $replace_item, ... )
		) {
			if( $this->isError()) return FALSE;
			elseif( empty( $replace )) {
				$this->search		= $this->replace = array();
				$this->_noSearch	= TRUE;
			} else {
				foreach( $replace as $s => $r ) {
					$this->search[]	= $s;
					$this->replace[]	= $r;
				}
				$this->search	= array_unique( $this->search );
				$this->replace	= array_unique( $this->replace );
				// paranoia programming
				if( count( $this->search ) != count( $this->replace )) {
					$this->search		= $this->replace = array();
					$this->_noSearch	= TRUE;
					return $this->_trigger_error('Conteg::setSearch(): Search + Replace do not balance.', E_USER_NOTICE );
				} else $this->_noSearch	= FALSE;
			};

			return TRUE;
		}	// Conteg::setSearch()
/*
 * setup() - Sets certain Class variables according to supplied array
 *           Note: this can be called many times, but after the first
 *                 call (Constructor) most empty values are ignored.
 *
 * parameter: $param = array() :- array of key => value pairs
 *
 *
 * No immediate output Switch (TRUE, FALSE):										'noprint' => FALSE
 *   TRUE:      No auto-output on instantiation; allows external negotiation,
 *             +debugging, ...
 *             +Affects constructor ONLY.
 *   FALSE:     (default) Print immediately.
 *
 * Use-Last-Modified Switch (TRUE, FALSE):										'use_last_modified' => TRUE
 *   TRUE:      (default) Sets `$_noLastModified' to FALSE
 *   FALSE:     Sets `$_noLastModified' to TRUE (do not use Last-Modified header)
 *
 * Modification (Unix timestamp):													'modified' => NULL
 *   TimeStamp: Sets `Last-Modified' header to this value (no checking).
 *   (empty):   (default) Sets `Last-Modified' header to current time.
 *
 * Use-Expires Switch (TRUE, FALSE):												'use_expires' => TRUE
 *   TRUE:      (default) Sets `$_noExpires' to FALSE
 *   FALSE:     Sets `$_noExpires' to TRUE (do not use Expires header)
 *
 * Expiry (int, seconds):																'expiry' => 3600
 *   int:       Sets `Expiry' header to current time + this value (no checking).
 *   (empty):   (default) Sets `Expiry' header to current time + 1 hour.
 *
 * Cache-Control; Use Cache-Control Switch: (empty, filled-array):		'cache_control' => array()
 *   ARRAY:    Send Cache-Control response headers; the following array keys are values for
 *            +the individual Cache-Control response header values;
 *            +see also rfc2616 sec 14.9.4 + sec 13.4.
 *      'max-age'          => (int) secs   If value empty, is set to value of Expiry (above).
 *      'must-revalidate'  => (anything)
 *      'no-cache'         => (anything)   Also sets Expiry to date in the past.
 *      'no-store'         => (anything)
 *      'no-transform'     => (anything)
 *      'private'          => (anything)
 *      'post-check'       => (int)
 *      'pre-check'        => (int)
 *      'proxy-revalidate' => (anything)
 *      'public'           => (anything)
 *      's-maxage'         => (int) secs   If value empty, is set to value of Expiry (above).
 *
 *             No RFC discusses `Pragma' in terms of Response headers (only Request headers).
 *            +Nevertheless, the following is available:
 *      'pragma'           => (string)
 *
 *             The following short-cut macros are also available, rather than specifying
 *            +individual values:
 *      'macro'            => 'cache-all'
 *      'macro'            => 'cache-none'
 *   EMPTY:    (default) Do not send Cache-Control headers
 *
 * Use-ETag Switch (TRUE, FALSE):													'use_etag' => FALSE
 *   TRUE:      Sets `$_noETag' to FALSE
 *   FALSE:     (default) Sets `$_noETag' to TRUE (do not use ETags)
 *
 * External ETag (string/empty):														'etag' => ''
 *   string:    Sets `$etag' to the given value + `$_noETag' to FALSE
 *             +$_isExtETag to TRUE; important to set 'weak_etag' correctly
 *   (empty):   (default) No change
 *
 * Weak ETags (TRUE, FALSE):															'weak_etag' => TRUE
 *   TRUE:      (default) Sets `$_isWeakETag' to TRUE
 *             +Also important for Accept-Ranges (below).
 *   FALSE:     Sets `$_isWeakETag' to FALSE
 *
 * Search + Replace Switch (FALSE, not-set, filled-array):					'search' => array()
 *   ARRAY:     format: array( $search_item => $replace_item, ... )
 *             +Used to do global search/replace on page contents.
 *   not-set    (default) Use stats search + replace only;
 *             +Search: `_GZIP_ENCODE_STAT';  replace: compression % (0-100),
 *             +Search: `_GZIP_ENCODE_LEVEL'; replace: compression level (off, 0-9).
 *   FALSE:     Do not use any search/replace.
 *
 * Apache-Notes Switch (TRUE, FALSE):												'use_apache_notes' => FALSE
 *   TRUE:      Apache-Notes are available, and will be set.
 *             +Used to log compression values within Apache logfiles
 *   FALSE:     (default) Do not use Apache-Notes to log compression values.
 *              (do please send the means to set for other web-servers)
 *
 * Apache-Notes Input: (string):														'input' => 'instream'
 *   string:    The Apache-Note name for input-bytes is set to this value
 *             +The name used within httpd.conf for the Deflate Input Note
 *   EMPTY:     (default) $_inputNote is set to 'instream'
 *
 * Apache-Notes Output: (string):													'output' => 'outstream'
 *   string:    The Apache-Note name for output-bytes is set to this value
 *             +The name used within httpd.conf for the Deflate Output Note
 *   EMPTY:     (default) $_outputNote is set to 'outstream'
 *
 * Apache-Notes Ratio: (string):														'ratio' => 'ratio'
 *   string:    The Apache-Note name for compression ratio is set to this value
 *             +The name used within httpd.conf for the Deflate Ratio Note
 *   EMPTY:     (default) $_ratioNote is set to 'ratio'
 *
 * Use Content-Type Switch: (TRUE/FALSE):											'use_content_type' => TRUE
 *   TRUE:      (default) Use a Content-Type Response header for the page
 *             +(need then also to set the Content Type + charset, below)
 *   FALSE:     Do not use a Content-Type header for the page
 *
 * Character Set: (string, http/1.1 only):										'charset' => 'ISO-8859-1'
 *   string:    The document charset; see also Content Type
 *             +See rfc2616-sec3.html#sec3.7 + rfc2616-sec14.html#sec14.17
 *   EMPTY:     (default) $_charset is set to 'ISO-8859-1'
 *
 * Content Encodings: (array):														'encodings' => array('gzip', ... )
 *   array :    Possible encodings to use in addition to 'identity'
 *             +See rfc2616-sec3.html#sec3.5
 *   EMPTY:     (default) $_encodings set to array('gzip','deflate','compress')
 *
 * Content Type: (string):																'type' => 'text/html'
 *   string:    The media-type for the Content-Type header; see also Character Set
 *             +See rfc2616-sec3.html#sec3.7 + rfc2616-sec3.html#sec3.10
 *   EMPTY:     (default) $_contentType is set to 'text/html'
 *
 * Use Content-Language Switch: (TRUE/FALSE):									'use_content_lang' => TRUE
 *   TRUE:      (default) Use a Content-Language Response header for the page
 *             +(need then also to set the lang, below)
 *   FALSE:     Do not use a Content-Language header for the page
 *
 * Language: (string):																	'lang' => 'en'
 *   string:   The document language
 *            +See rfc2616-sec14.html#sec14.12 + rfc2616-sec14.html#sec14.17
 *   EMPTY:    (default) $content_lang is set to 'en'
 *
 * Content Negotiation; Use Accept Switch: (TRUE/FALSE):						'use_accept' => FALSE
 *   TRUE:     (default) Respond to Accept (media) Request headers
 *            +See rfc2616-sec14.html#sec14.1; see also 'type' above
 *   FALSE:    Do not respond to Accept Request headers
 *
 * Content Negotiation; Use Accept-Charset Switch: (TRUE/FALSE):			'use_accept_charset' => FALSE
 *   TRUE:     (default) Respond to Accept-Charset Request headers
 *            +See rfc2616-sec14.html#sec14.2; see also Character Set
 *   FALSE:    Do not respond to Accept-Charset Request headers
 *
 * Content Negotiation; Use Accept-Encoding Switch: (TRUE, FALSE, 0-9):	'use_accept_encode' => TRUE
 *             Note: This is the only content negotiated INSIDE the Class.
 *   FALSE:    No attempt at compression
 *   0:        No compression (still encodes as gzip-ed)
 *   1:        Min compression
 *   ...       Some compression (integer from 1 to 9)
 *   9:        Max compression
 *   TRUE:     (default) Dynamic, load-balanced compression;
 *            +the higher the system load, the lower the compression.
 *
 * Content Negotiation; Use User-Agent Switch: (TRUE/FALSE):				'use_user_agent' => TRUE
 *   TRUE:     (default) Respond to User-Agent Request headers
 *            + (can affect Accept-Encoding negotiation)
 *   FALSE:    Do not respond to Accept-Encoding Request headers
 *
 * Content Negotiation; Use Accept-Language Switch: (TRUE/FALSE): 		'use_accept_lang' => FALSE
 *   TRUE:     Respond to Accept-Language Request headers
 *            +See rfc2616-sec14.html#sec14.4
 *   FALSE:    (default) Do not vary response by Accept-Language headers
 *
 * Content Negotiation; Use Accept-Ranges Switch: (TRUE/FALSE):			'use_accept_ranges' => FALSE
 *   TRUE:     Respond to Accept-Ranges Request headers
 *            +See rfc2616-sec14.html#sec14.5
 *            +Note: use of weak ETags will switch this to FALSE; $if_range may
 *            +be a mod-date, which is dodgy if the current page/file is not byte-
 *            +by-byte compatible, and this is decided by the 'weak_etag' switch.
 *   FALSE:    (default) Do not vary response by Accept-Ranges headers.
 *
 * Miscellaneous Variable for Weak-Etag generation (string/Empty): 		'other_var' => ''
 *   string     Added to end of weak eTag md5-seed-string; flags that some misc aspect
 *             +of the page has changed; a good example is page-personalisation after logon.
 *   EMPTY      (default)
 *
 * Send a User-Determined HTTP Header: (int/empty):							'http_status' => (empty)
 *   int        Send this-value HTTP header (plus status if `dupe_status_header` is TRUE).
 *              Note: will be over-ridden if 404 header is TRUE, else will over-ride all other
 *             +200, 206, 304, 406, 412 or 416 headers. Sends normal page content.
 *   FALSE      (default) Send a normal program-determined HTTP header.
 *
 * Send a 404 header: (TRUE/FALSE):													'404' => FALSE
 *   TRUE       Send a 404 header. The exact same page content will be delivered as with a
 *             +200 page; use this for custom 404 error pages.
 *   FALSE      (default) Send a normal header.
 *
 * Transform 404 headers to 410: (TRUE/FALSE):									'404_to_410' => TRUE
 *   TRUE       Send a 410 header when '404' == TRUE and is HTTP/1.1.
 *   FALSE      (default) Always send a 404 header when '404' == TRUE.
 *
 * Send a Vary header: (TRUE/FALSE):													'use_vary' => TRUE
 *   TRUE       (default) Send a Vary header if any Content Negotiation possible.
 *             +Note: page content may not be varied; the header will still be sent.
 *   FALSE      Do NOT send a Vary header, regardless.
 *
 * Use Duplicate `Status` headers: (TRUE/FALSE):								'dupe_status_header' => TRUE
 *   TRUE       (default) Send a `Status:` header together with the `HTTP/1.x` header to fix
 *             +display problems with MSIE in some circumstances.
 *             +See sendStatusHeader() for more info.
 *   FALSE      Do not send the duplicate header (just a `HTTP/1.x` header).
 *
 * Lower-Case Referer url-parts: (TRUE/FALSE):									'referer_lower_case' => TRUE
 *   TRUE:     (default) Lowercase all url-parts of the $referer array
 *            +Note: $referer['uri'] is always in original case
 *   FALSE:    Do not alter the case of any of the elements of the $referer array
 *
 * MSIE Error Content Fix: (TRUE/FALSE):											'msie_error_fix' => TRUE
 *   TRUE:     (default) Pad small content error-messages until > 512 bytes
 *            +Note: see compress() for more details.
 *   FALSE:    Do not pad the content of error-messages.
 *
 * Number of CPUs: (int/empty) (for best, at instantiation)					'cpu_number' => (int)
 *   (int)     (default 1) Number of CPUs in the server.
 *   empty:    Will set $_num_cpu to 1 (first call only)
 *
 * Request Header Preference-order (empty, filled-array):					'prefer' => array()
 *   ARRAY:     format: array('Request_Header_Code1', Request_Header_Code2, ... )
 *             +Request headers are preferred in the order given.
 *   EMPTY      See code for the preference-order.
 *
 * returns TRUE/FALSE (error)
 *
 * - 19 Aug 07 mod: last act now is to call _initResponse() (allows follow-up checks for 304/406/412 responses)
 * - 02 Nov 07 added 'use_last_modified' + 'use_expires'
 *             sometimes do NOT send a `Last-Modified' header (HTTP/1.0 proxies ignore ETags; can cause unwanted 304s).
 *             see  http://forums.modem-help.com/viewtopic.php?t=988
 *             also http://www.w3.org/Protocols/rfc2616/rfc2616-sec13.html#sec13.3.4
 * - 08 Oct 07 added 'use_user_agent' + 'use_vary'
 * - 06 Mar 07 added 'cpu_number'
 * - 18 Feb 07 added '404_to_410', 'http_status', 'msie_error_fix', 'referer_lower_case'
 * - 02 Ju1 06 added '404', 'dupe_status_header'
 * - 04 Jun 06 added Cache-Control values
 * - 23 Feb 06 added 'other_var'
 * - 02 Sep 05 initial -AK
 */
		function setup(
			$param	= array()		// format: array( $switch => $value, ... )
		) {
			if( $this->isError()) return FALSE;		// need to examine $_error

// print-on-declaration switch
			// No output during Class constructor
			if( !isset( $this->_noPrint )) {			// apply defaults on first call only
				if( !isset( $param['noprint']))					$param['noprint'] = FALSE;
			}
			if( isset( $param['noprint']))				$this->_noPrint = ( bool ) $param['noprint'];

// Essentials - Modification + Expiry
			// Modification date - Unix timestamp
			if( !isset( $this->_noLastModified )) {// apply defaults on first call only
				if( !isset( $param['use_last_modified']))		$param['use_last_modified'] = TRUE;
			}
			if( isset( $param['use_last_modified']))	$this->_noLastModified = !$param['use_last_modified'];

			if( !isset( $this->last_modified )) {	// apply defaults on first call only
				if( empty( $param['modified']))					$param['modified'] = $this->now;
			}
			if( isset( $param['modified']))				$this->last_modified = $param['modified'];

			// Expiry time - seconds (can be modified by Cache-Control, below)
			if( !isset( $this->_noExpires )) {		// apply defaults on first call only
				if( !isset( $param['use_expires']))				$param['use_expires'] = TRUE;
			}
			if( isset( $param['use_expires']))			$this->_noExpires = !$param['use_expires'];

			if( !isset( $this->expires )) {			// apply defaults on first call only
				if( !isset( $param['expiry']))					$param['expiry'] = 3600;
			}
			if( isset( $param['expiry']))					$this->expires = $this->now + $param['expiry'];

// Essentials - Cache-Control
			// Initialise defaults
			if( !isset( $this->_cache_control_response )) {	// apply defaults on first call only
				if( empty( $param['cache_control']))		$param['cache_control']['macro']	= 'cache-all';
			}

			if( !empty( $param['cache_control'])) {
				$zeroExpiry					= strtotime('Tue, 23 Feb 1999 18:30:00 GMT');
				if( isset( $param['cache_control']['macro'])) {
					$val	= $param['cache_control']['macro'];
					if( $val == 'cache-all') {
						$param['cache_control']['public']				= TRUE;
						$param['cache_control']['max-age']				= // intentionally left empty!
						$param['cache_control']['s-maxage']				= '';
					} else if( $val == 'cache-none') {
						$param['cache_control']['must-revalidate']	= // intentionally left empty!
						$param['cache_control']['no-cache']				= // intentionally left empty!
						$param['cache_control']['no-store']				= // intentionally left empty!
						$param['cache_control']['private']				= TRUE;
						$param['cache_control']['max-age']				= // intentionally left empty!
						$param['cache_control']['post-check']			= // intentionally left empty!
						$param['cache_control']['pre-check']			= // intentionally left empty!
						$param['cache_control']['s-maxage']				= 0;
						$param['cache_control']['pragma']				= 'no-cache';
					}
				}
				foreach( $param['cache_control'] as $key => $val ) {
					switch( $key ) {
						case 'max-age':
						case 's-maxage':
							if( empty( $val )) {
								$this->_cache_control_response[ $key ]	= (( $val === 0 ) or ( $val === '0'))
									? 0
									: (( $this->expires == $zeroExpiry )	// in case of multi-calls
										? (( isset( $param['expiry']))
											? $param['expiry']
											: 3600
											)
										: ( int ) $this->expires - $this->now
										);
							} else $this->_cache_control_response[ $key ]	= ( int ) $val;
							break;
						case 'must-revalidate':
						case 'no-cache':
						case 'no-store':
						case 'no-transform':
						case 'private':
						case 'proxy-revalidate':
						case 'public':
							$this->_cache_control_response[ $key ]	= TRUE;
							break;
						case 'post-check':
						case 'pre-check':
							$this->_cache_control_response[ $key ]	= ( int ) $val;
							break;
						case 'pragma':
							$this->pragma									= ( string ) $val;
					}	// switch( $key )
				}	// foreach( $param['cache_control'] as $key => $val )
				if(
					isset( $this->_cache_control_response['no-cache']) and 
					( $this->_cache_control_response['no-cache'] == TRUE )
				) $this->expires	= $zeroExpiry;
			}	// if( !empty( $param['cache_control']))

// http/1.1 - ETags
			// Use ETags
			if( !isset( $this->_noETag )) {			// apply defaults on first call only
				if( !isset( $param['use_etag']))					$param['use_etag'] = FALSE;
			}
			if( isset( $param['use_etag']))				$this->_noETag = !$param['use_etag'];

			// Use Weak ETags (academic unless $this->_noETag == FALSE)
			if( !isset( $this->_isWeakETag )) {		// apply defaults on first call only
				if( !isset( $param['weak_etag']))				$param['weak_etag'] = TRUE;
			}
			if( isset( $param['weak_etag']))				$this->_isWeakETag = ( bool ) $param['weak_etag'];

			// ETag - set from outside Class (make sure is unique)
			if( !empty( $param['etag'])) {
				$this->etag			= $param['etag'];
				$this->_isExtETag	= TRUE;
				$this->_noETag		= FALSE;
			}

			// Miscellaneous Weak-Etag Variable (academic unless $_noETag == FALSE + $_isExtETag == FALSE)
			if( !isset( $this->_other_var )) {		// apply defaults on first call only
				if( !isset( $param['other_var']))				$param['other_var'] = '';
			}
			if( isset( $param['other_var']))				$this->_other_var = ( string ) $param['other_var'];

// Number of CPUs (can affect Accept-Encoding)
			// Set the number of cpus in the server to ensure proper load-balancing
			if( !isset( $this->_num_cpu )) {			// apply defaults on first call only
				if( !isset( $param['cpu_number']))				$param['cpu_number']				= 1;
			}
			if( isset( $param['cpu_number'])) 			$this->_num_cpu		= ( int ) $param['cpu_number'];

// Content Negotiation; the actual negotiation occurs outside this Class (exception: Accept-Encoding)
			// Accept Request header; academic unless 'type' is also set
			// Content-Type Response header; academic unless 'type' is also set
			if( !isset( $this->_noAccept )) {		// apply defaults on first call only
				if( !isset( $param['use_accept']))				$param['use_accept']			= FALSE;
				if( !isset( $param['charset']))					$param['charset']				= 'ISO-8859-1';
				if( !isset( $param['type']))						$param['type']					= 'text/html';
				if( !isset( $param['use_content_type']))		$param['use_content_type']	= TRUE;
			}
			if( isset( $param['use_accept']))			$this->_noAccept			= !$param['use_accept'];
			if( isset( $param['charset']))				$this->_charset			= $param['charset'];
			if( isset( $param['type']))					$this->_contentType		= $param['type'];
			if( isset( $param['use_content_type']))	$this->_noContentType	= !$param['use_content_type'];

			// Accept-Charset Request header; academic unless 'charset' is also set
			if( !isset( $this->_noAcceptCharset )) {	// apply defaults on first call only
				if( !isset( $param['use_accept_charset']))	$param['use_accept_charset'] = FALSE;
			}
			if( isset( $param['use_accept_charset'])) $this->_noAcceptCharset = !$param['use_accept_charset'];

			// Accept-Encoding Request header (Compression)
			if( !isset( $this->_noAcceptEncode )) {	// apply defaults on first call only
				if( !isset( $param['use_accept_encode']))		$param['use_accept_encode']	= TRUE;
				if( !isset( $param['encodings']))				$param['encodings']				= array('gzip', 'deflate', 'compress', 'x-gzip', 'x-compress');
			}
			if( isset( $param['encodings']))				$this->_encodings			= $param['encodings'];
			if( isset( $param['use_accept_encode'])) {					// FALSE, 0-9 or TRUE
				if( $param['use_accept_encode'] === FALSE ) {			// no compression attempt
					$this->_noAcceptEncode	= TRUE;
					$this->level				= 0;
				} else {
					// sanity checks
					if( !extension_loaded('zlib')) {
						$this->_noAcceptEncode	= TRUE;
						$this->level				= 0;
						$this->_trigger_error('Conteg::setup: zlib required for compression.', E_USER_NOTICE );
					} elseif( ini_get('zlib.output_compression') != '') {		// compression handled externally
						$this->_noAcceptEncode	= TRUE;
						$this->level				= 0;
						$this->_trigger_error('Conteg::setup: zlib.output_compression activated.', E_USER_NOTICE );
					} elseif( ini_get('output_handler') == 'ob_gzhandler') {	// compression handled externally
						$this->_noAcceptEncode	= TRUE;
						$this->level				= 0;
						$this->_trigger_error('Conteg::setup: ob_gzhandler() registered.', E_USER_NOTICE );
					} elseif( !function_exists('crc32')) {
						$this->_noAcceptEncode	= TRUE;
						$this->level				= 0;
						$this->_trigger_error('Conteg::setup: crc32() not found, PHP >= 4.0.1 required.', E_USER_NOTICE );
					} else {
						$this->_noAcceptEncode	= FALSE;
						if(( $this->level = $param['use_accept_encode']) === TRUE )
							$this->level	= $this->getCompLevel();			// load-balanced compression
					}
				}
			}

			// Accept-Language Request header; this ONLY sets the Vary: Response header
			// Content-Language Response header; academic unless 'lang' is also set (consistency)
			if( !isset( $this->_noAcceptLang )) {		// apply defaults on first call only
				if( !isset( $param['use_accept_lang']))		$param['use_accept_lang']	= FALSE;
				if( !isset( $param['use_content_lang']))		$param['use_content_lang']	= TRUE;
				if( !isset( $param['lang'])) 						$param['lang']					= 'en';
			}
			if( isset( $param['use_accept_lang']))		$this->_noAcceptLang		= !$param['use_accept_lang'];
			if( isset( $param['use_content_lang'])) 	$this->_noContentLang	= !$param['use_content_lang'];
			if( isset( $param['lang']))					$this->content_lang		= $param['lang'];

			// Accept-Ranges Request header
			if( !isset( $this->_noAcceptRanges )) {	// apply defaults on first call only
				if( !isset( $param['use_accept_ranges']))		$param['use_accept_ranges']	= FALSE;
			}
			if( isset( $param['use_accept_ranges']))	$this->_noAcceptRanges	= !$param['use_accept_ranges'];
			if( $this->_isWeakETag )						$this->_noAcceptRanges	= TRUE;

			// User-Agent Request header
			if( !isset( $this->_noUserAgent )) {		// apply defaults on first call only
				if( !isset( $param['use_user_agent']))			$param['use_user_agent']		= TRUE;
			}
			if( isset( $param['use_user_agent']))		$this->_noUserAgent		= !$param['use_user_agent'];

			// Vary Response header
			if( !isset( $this->_noVary )) {				// apply defaults on first call only
				if( !isset( $param['use_vary']))					$param['use_vary']				= TRUE;
			}
			if( isset( $param['use_vary']))				$this->_noVary				= !$param['use_vary'];

// Apache-Notes compression-logging
			// Use Apache-Notes
			if( !isset( $this->_noNotes )) {			// apply defaults on first call only
				if( !isset( $param['use_apache_notes']))		$param['use_apache_notes']		= FALSE;
				if( !isset( $param['input']))						$param['input']					= 'instream';
				if( !isset( $param['output']))					$param['output']					= 'outstream';
				if( !isset( $param['ratio']))						$param['ratio']					= 'ratio';
			}
			if( isset( $param['use_apache_notes']))	$this->_noNotes			= !$param['use_apache_notes'];
			if( isset( $param['input']))					$this->_inputNote			= $param['input'];
			if( isset( $param['output']))					$this->_outputNote		= $param['output'];
			if( isset( $param['ratio']))					$this->_ratioNote			= $param['ratio'];

// Send a User-Determined or 404 header
			// Use User-Determined Status header (note: no checks)
			if( !isset( $this->_httpStatus )) {		// apply defaults on first call only
				if( !isset( $param['http_status']))				$param['http_status']	= NULL;
			}
			if( !empty( $param['http_status']))			$this->_httpStatus		= ( int ) $param['http_status'];
			// Use 404 Status header (note that will over-ride User-Determined Status)
			if( !isset( $this->_is404 )) {			// apply defaults on first call only
				if( !isset( $param['404']))						$param['404']			= FALSE;
			}
			if( isset( $param['404']))						$this->_is404				= ( bool ) $param['404'];
			if( $this->_is404 )								$this->_httpStatus		= 404;
			// Use 410 Status header for HTTP/1.1
			if( !isset( $this->_no410 )) {			// apply defaults on first call only
				if( !isset( $param['404']))						$param['404_to_410']	= TRUE;
			}
			if( isset( $param['404_to_410']))			$this->_no410				= !$param['404_to_410'];

// Duplicate `Status` headers
			// Use Duplicate `Status` headers
			if( !isset( $this->_noDupes )) {			// apply defaults on first call only
				if( !isset( $param['dupe_status_header']))	$param['dupe_status_header']	= TRUE;
			}
			if( isset( $param['dupe_status_header']))	$this->_noDupes		= !$param['dupe_status_header'];

// Lower-case Referer URL-parts
			// Make Referer URL-parts array lc
			if( !isset( $this->_noReferLC )) {		// apply defaults on first call only
				if( !isset( $param['referer_lower_case']))	$param['referer_lower_case']	= TRUE;
			}
			if( isset( $param['referer_lower_case']))	$this->_noReferLC	= !$param['referer_lower_case'];

// Fixup Error-Message Content for MSIE Browsers, if necessary
			// Pad content of Error-Messages when necessary
			if( !isset( $this->_noMSErrorFix )) {	// apply defaults on first call only
				if( !isset( $param['msie_error_fix']))			$param['msie_error_fix']			= TRUE;
			}
			if( isset( $param['msie_error_fix'])) 		$this->_noMSErrorFix	= !$param['msie_error_fix'];

// Request Header oddity
			// Preference-order; later is lesser; make sure all exist!
			if( !isset( $this->_preferOrder )) {	// apply defaults on first call only
				if( !isset( $param['prefer'])) $param['prefer'] = array(
					'IM',		// if_match
					'INM',	// if_none_match
					'IMS',	// if_modified_since
					'IUS'		// if_unmodified_since
				);
			}
			if( !empty( $param['prefer']))				$this->_preferOrder		= $param['prefer'];

			// Global page search/replace
			if( !isset( $this->_noSearch )) {		// apply defaults on first call only
				if( !isset( $param['search']))					$param['search']	= array(
					_GZIP_ENCODE_STAT		=> '(filled in later)',
					_GZIP_ENCODE_LEVEL	=> (( $this->_noAcceptEncode == TRUE ) ? '(off)' : $this->level )
				);
			}
			if( isset( $param['search'])) {
				if( $param['search'] === FALSE ) {
					$this->_noSearch	= TRUE;
				} else {
					$this->_noSearch	= FALSE;
					if( $this->setSearch( $param['search']) == FALSE ) return FALSE;
				}
			}

			// Final act: check for 304/406/412 results if called early
			return (( $this->_noPrint )
				? $this->_initResponse()
				: TRUE
				);
		}	// Conteg::setup()
/*
 * show() - Displays the page
 *
 * returns TRUE/FALSE (error)
 *
 * 2010-08-19 mod: _initResponse() set to act for final time
 * 2007-07-07 $file + $line added to headers_sent() -AK
 */
		function show(
			$refresh = FALSE	// ( TRUE ) force-refresh $data; (FALSE) reuse existing $data
		) {
			// sanity warnings
			if( headers_sent( $file, $line )) { return $this->_trigger_error("Conteg::show(): Cannot print; headers already sent in $file on line $line.", E_USER_WARNING );
			} else if( $this->isError()) return FALSE;

			// (for last time) set Response variables
			$this->_initResponse('Time, gentleman, please');

			// common HTTP/1.x Response headers
			if( !empty( $this->last_modified ))		header('Last-Modified: '. gmdate('D, d M Y H:i:s \G\M\T', $this->last_modified ));
			if( !empty( $this->expires ))				header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', $this->expires ));
			if( !empty( $this->pragma ))				header('Pragma: '. $this->pragma );
			if( !empty( $this->cache_control ))		header('Cache-Control: '. $this->cache_control );
			if( !empty( $this->content_type ))		header('Content-Type: '. $this->content_type );
			if( !empty( $this->content_lang ))		header('Content-Language: '. $this->content_lang );
			// common HTTP/1.1 Response headers
			if( !empty( $this->vary ))					header('Vary: '. $this->vary );
			if(( $this->_noETag == FALSE ) and ( !empty( $this->etag )))
																header('ETag: '. $this->etag );
			if( $this->_noAcceptRanges == FALSE )	header('Accept-Ranges: bytes');

			if( empty( $this->_httpStatus ) and								// can we avoid sending the data...?
				( $this->_is304 or $this->_is406 or $this->_is412 )
			) {																		// yes!
				ob_end_clean();
				if( $this->_is304 ) {
					$this->sendStatusHeader( 304 );
					header('Content-Length: 0');	// my server sending a mysterious 20 bytes body (?)
				} elseif( $this->_is406 ) {
					$this->sendStatusHeader( 406 );
					header('Cache-Control: private, max-age=0, must-revalidate');

					if( $this->method != 'HEAD') {
						header('Content-Type: text/plain');	// last header wins
						echo "HTTP/1.1 Error 406 Not Acceptable:\n\nAvailable server charsets and/or media-type not acceptable to client.\n".
						(( $this->_contentType ) ? "Media-Type: $this->_contentType\n" : '').
						(( $this->_charset ) ? "Charset: $this->_charset\n" : '');
					}
				} else {	// if( $this->_is304 ) elseif( $this->_is406 )
					$this->sendStatusHeader( 412 );
					header('Cache-Control: private, max-age=0, must-revalidate');

					if( $this->method != 'HEAD') {
						header('Content-Type: text/plain');	// last header wins
						echo "HTTP/1.1 Error 412 Precondition Failed: Precondition request failed positive evaluation\n";
					}
				}	// if( $this->_is304 ) elseif( $this->_is406 ) else
			} else {																	// ...no? oh, OK, go ahead
				if(( $this->_noSearch == FALSE )) {							// search + replace
					if(( $key = array_search( _GZIP_ENCODE_STAT, $this->search )) !== FALSE ) {
						// compression is run twice to get compression stats; run #1
						if( $this->compress() == FALSE ) return FALSE;	// error triggered
						$this->replace[ $key ]	= $this->stat;				// compression stats on page
						$refresh						= FALSE;						// just refreshed $data
					}
					if( !empty( $this->search )) {							// do the business
						if( empty( $this->data ) or $refresh ) {
							if(( $this->data = ob_get_contents()) === FALSE ) return $this->_trigger_error('Conteg::show(): No ob_contents to do search/replace on.', E_USER_ERROR );
						}
						$this->data = str_replace( $this->search, $this->replace, $this->data );
						$refresh						= FALSE;						// else will lose search/replace
					}
				}	// end search + replace

				if( $this->compress( $refresh ) == FALSE ) return FALSE;	// error triggered
				ob_end_clean();
// note: prog errors below this point cannot be seen
				if( $this->encoding == 'identity') {
					if( $this->method != 'HEAD') {
						if( empty( $this->_httpStatus ) and
							(( $this->_noAcceptRanges == FALSE ) and ( !empty( $this->range )))
						) {													// send HTTP/1.1 Range-content
							if( $this->_checkRange() == FALSE ) {	// error within the range - send a 416
								$this->sendStatusHeader( 416 );
								header("Content-Range: */$this->size");
							} else {											// range is fine - send a 206
								$this->sendStatusHeader( 206 );
								foreach( $this->range AS $begin => $end ) {	// currently, only one entry in array
									$len	= $end - $begin + 1;
									header("Content-Range: bytes $begin-$end/$this->size");
									header('Content-Length: '. $len );
									echo substr( $this->data, $begin, $len );
								}
							}	// if( $this->_checkRange() == FALSE ) else
						} else {												// send normal content, no encoding
							$_httpStatus	= ( empty( $this->_httpStatus ))
								? 200
								: $this->_httpStatus;
							$this->sendStatusHeader( $_httpStatus );
							header('Content-Length: '. $this->size );

							echo $this->data;
						}
					} else {	// if( $this->method != 'HEAD')
						// send headers only
						$_httpStatus	= ( empty( $this->_httpStatus ))
							? 200
							: $this->_httpStatus;
						$this->sendStatusHeader( $_httpStatus );
						echo '';
					}
				} else {	// if( $this->encoding == 'identity') (thus contents will be compressed)
					$_httpStatus	= ( empty( $this->_httpStatus ))
						? 200
						: $this->_httpStatus;
					$this->sendStatusHeader( $_httpStatus );
					header('Content-Encoding: '. $this->encoding );
					header('Content-Length: '. $this->gzsize );
					header('X-Content-Encoded-By: class.Conteg.'. $this->_version );

					if( $this->method != 'HEAD') {
						if( $this->_noNotes == FALSE ) {
							apache_note( $this->_inputNote, $this->size );
							apache_note( $this->_outputNote, $this->gzsize );
							apache_note( $this->_ratioNote, round(( $this->gzsize / $this->size ) * 100 ));
						}
						echo $this->gzdata;
					} else {
						// send headers only
						echo '';
					}
				}	// if( $this->encoding == 'identity') else
			}	// if( $this->_is304 or $this->_is406 or $this->_is412 ) else

			// the return allows post-processing; be *very* careful not to cause more output
			return TRUE;
		}	// Conteg::show()
/*
 * _checkRange() - Check $range for validity now that $size is known
 *                 Note: Currently, this Class does not deal with
 *                       multi-part ranges (it sends a 200 response)
 *
 * Returns TRUE (send a 206) or FALSE (send a 416)
 */
		function _checkRange() {
			if( isset( $this->range['begin']) and (( $begin = $this->range['begin']) > $this->size )) {
				return FALSE;
			} elseif( isset( $this->range['end']) and (( $end = $this->range['end']) > $this->size )) {
				return FALSE;
			}

			if( isset( $begin )) {
				$this->range	= array( $begin => ( $this->size - 1 ));
				return TRUE;
			} elseif( isset( $end )) {
				if( $end <= 0 ) return FALSE;
				$this->range	= array(( $this->size - $end ) => ( $this->size - 1 ));
				return TRUE;
			} else foreach( $this->range AS $begin => $end ) {	// just one element currently
				if( $begin >= $this->size ) return FALSE;
				if( $end >= $this->size ) {
					$begin			= 0;
					$end				= $this->size - 1;
					$this->range	= array( $begin => $end );
				}
			}

			return TRUE;
		}	// Conteg::_checkRange()
/*
 * _initRequest() - Set conditional Request headers + various browser-dependant variables + referer (sic)
 *
 *  Many stolen from phpMyAdmin defines.lib.php (later: Config.class.php)
 *  Returns TRUE
 *
 * - 07 Mar 08 bugfix re: Safari version detect
 * - 07 Sep 07 bugfix re: empty Q-values
 * - 05 Mar 07 bugfix re: Referer (['uri'] not included)
 * - 16 Feb 07 updated with Referer
 * - 04 Jun 06 updated with Cache-Control
 * - 25 Aug 05 added -AK
 */
		function _initRequest() {
			if( !empty( $GLOBALS['_SERVER'])) {
				$this->_SERVER_ARRAY	= '_SERVER';
			} elseif( !empty( $GLOBALS['HTTP_SERVER_VARS'])) {
				$this->_SERVER_ARRAY	= 'HTTP_SERVER_VARS';
			} else {
				$this->_SERVER_ARRAY	= 'GLOBALS';
			}

			global ${$this->_SERVER_ARRAY};

			// http protocol
			$this->protocol	= (( !empty( ${$this->_SERVER_ARRAY}['SERVER_PROTOCOL'])) and ( strtoupper( stripslashes( ${$this->_SERVER_ARRAY}['SERVER_PROTOCOL'])) == 'HTTP/1.1'))
				? 'HTTP/1.1'
				: 'HTTP/1.0';

			// http method
			$this->method		= ( empty( ${$this->_SERVER_ARRAY}['REQUEST_METHOD']))
				? 'GET'
				: strtoupper( stripslashes( ${$this->_SERVER_ARRAY}['REQUEST_METHOD']));

			// ssl
			$this->ssl			= ( empty( ${$this->_SERVER_ARRAY}['HTTPS']))
				? FALSE
				: strtoupper( stripslashes( ${$this->_SERVER_ARRAY}['HTTPS']));

// ETag-related calling-script variables begins
			// server port
			$this->_port		= ( empty( ${$this->_SERVER_ARRAY}['SERVER_PORT']))
				? '80'
				: stripslashes( ${$this->_SERVER_ARRAY}['SERVER_PORT']);

			// $_scriptName
			if( isset( ${$this->_SERVER_ARRAY}['SCRIPT_FILENAME'])) {
				$this->_scriptName = ${$this->_SERVER_ARRAY}['SCRIPT_FILENAME'];
			// see notes on PHP 4.3.2 at http://www.php.net/reserved.variables re: foll
			} elseif( isset( ${$this->_SERVER_ARRAY}['PATH_TRANSLATED'])) {
				$this->_scriptName = ${$this->_SERVER_ARRAY}['PATH_TRANSLATED'];
			} elseif( isset( ${$this->_SERVER_ARRAY}['REQUEST_URI'])) {
				$this->_scriptName = ${$this->_SERVER_ARRAY}['REQUEST_URI'];
			} else $this->_noETag = TRUE;

			// $_query
			$this->_query		= ( empty( ${$this->_SERVER_ARRAY}['QUERY_STRING']))
				? ''
				: ${$this->_SERVER_ARRAY}['QUERY_STRING'];

			// $_session; strictly, not necessary if SID is set, as will be in $_query
			if( !empty( $GLOBALS['_SESSION'])) {
				$this->_SESSION_ARRAY	= '_SESSION';
			} elseif( !empty( $GLOBALS['HTTP_SESSION_VARS'])) {
				$this->_SESSION_ARRAY	= 'HTTP_SESSION_VARS';
			}
			global ${$this->_SESSION_ARRAY};
			if(( !empty( ${$this->_SESSION_ARRAY} )) and (( $this->_session = session_id()) != '')) $this->_session = session_name() .'='. $this->_session;
// ETag-related calling-script variables ends

			// Request-Header variables
			if(( $this->protocol != 'HTTP/1.1') and ( $this->method == 'HEAD')) {
				// http/1.0 rfc1945.txt section 8.2: conditional Headers are ignored
			} else {
				// $if_modified_since
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_IF_MODIFIED_SINCE'])) {
					$temp = stripslashes( ${$this->_SERVER_ARRAY}['HTTP_IF_MODIFIED_SINCE']);
					if(( $p = strpos( $temp, ';')) !== FALSE ) $temp = substr( $temp, 0, $p );
					if(( $this->if_modified_since = strtotime( $temp )) == -1 )  $this->if_modified_since = '';
					if( $this->if_modified_since > $this->now ) $this->if_modified_since = '';
				}

				// $accept
				// media-type template: `type/sub-type[[;accept-extension=(digit|quoted-str)][;q=(digit[.digit])]][,]`
				// any 'accept-extension' are thrown away in this implementation
				// default q (quality) value is 10 (also max value); min is 0
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT'])) {
					$temp = strtolower( stripslashes( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT']));
					$match	= explode(',', $temp );
					if( !is_array( $match )) $match = array( $match );
					foreach( $match AS $val ) {
						if( strpos( $val, 'q=') === FALSE ) {
							$q		= 10;
							$val	= trim( $val );
						} else {
							preg_match('@^(.+);[ ]*q=(1.*|(?:0\.[0-9]+))@', $val, $str );
							if( empty( $str[ 2 ])) {	// $str[ 2 ] is Q-value
								// preg_match() has failed; may have to abort this one...
								if(( strpos( $val, ';')) === FALSE )	continue;
								else												$q	= 10;
							} else {
								$q	= ( $str[ 2 ] == 1 )
									? 10
									: ( int ) ( 10 * (( float ) $str[ 2 ]));
								$val	= trim( $str[ 1 ]);
							}
						}
						if(( $p = strpos( $val, ';')) !== FALSE ) $val = substr( $val, 0, $p );
						$this->accept[ $val ] = $q;
					}
					if( empty( $this->accept )) {	// more defensive programming
						$this->accept['*/*']	= 10;
					} else {
						arsort( $this->accept );		// highest q-value at the beginning
					}
				} else {	// if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT']))
					if( $this->_noAccept == FALSE ) {	// Accept negotiation is on
						// rfc2616-sec14.html#sec14.1 - default is client accepts all media types
						$this->accept['*/*']	= 10;
					}
				}	// end $accept

				// $accept_charset
				// charset template: `charset[;q=(digit[.digit])][,]`
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_CHARSET'])) {
					$temp = strtoupper( stripslashes( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_CHARSET']));
					$match	= explode(',', $temp );
					if( !is_array( $match )) $match = array( $match );
					foreach( $match AS $val ) {
						if( strpos( $val, 'Q=') === FALSE ) {
							$q		= 10;
							$val	= trim( $val );
						} else {
							preg_match('@^(.+);[ ]*Q=(1.*|(?:0\.[0-9]+))@', $val, $str );
							if( empty( $str[ 2 ])) {	// $str[ 2 ] is Q-value
								// preg_match() has failed; may have to abort this one...
								if(( strpos( $val, ';')) === FALSE )	continue;
								else												$q	= 10;
							} else {
								$q	= ( $str[ 2 ] == 1 )
									? 10
									: ( int ) ( 10 * (( float ) $str[ 2 ]));
								$val	= trim( $str[ 1 ]);
							}
						}
						if(( $p = strpos( $val, ';')) !== FALSE ) $val = substr( $val, 0, $p );
						$this->accept_charset[ $val ] = $q;
					}
					if( empty( $this->accept_charset )) {	// more defensive programming
						$this->accept_charset['*']	= 10;
					} else {
						if(( array_search('*', $this->accept_charset ) === FALSE ) and ( array_search('ISO-8859-1', $this->accept_charset ) === FALSE )) {
							$this->accept_charset['ISO-8859-1']	= 10;
						}
						arsort( $this->accept_charset );		// highest q-value at the beginning
					}
				} else {	// if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_CHARSET']))
					if( $this->_noAcceptCharset == FALSE ) {	// Accept-Charset negotiation is on
						// rfc2616-sec14.html#sec14.2 - default is any character set is acceptable
						$this->accept_charset['*']	= 10;
					}
				}	// end $accept_charset

				// $accept_encoding
				// encoding template: `encoding[;q=(digit[.digit])][,]`
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_ENCODING'])) {
					$temp = strtolower( stripslashes( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_ENCODING']));
					$match	= explode(',', $temp );
					if( !is_array( $match )) $match = array( $match );
					foreach( $match AS $val ) {
						if( strpos( $val, 'q=') === FALSE ) {
							$q		= 10;
							$val	= trim( $val );
						} else {
							preg_match('@^(.+);[ ]*q=(1.*|(?:0\.[0-9]+))@', $val, $str );
							if( empty( $str[ 2 ])) {	// $str[ 2 ] is Q-value
								// preg_match() has failed; may have to abort this one...
								if(( strpos( $val, ';')) === FALSE )	continue;
								else												$q	= 10;
							} else {
								$q	= ( $str[ 2 ] == 1 )
									? 10
									: ( int ) ( 10 * (( float ) $str[ 2 ]));
								$val	= trim( $str[ 1 ]);
							}
						}
						if(( $p = strpos( $val, ';')) !== FALSE ) $val = substr( $val, 0, $p );
						$this->accept_encoding[ $val ] = $q;
					}
					if( empty( $this->accept_encoding )) {	// more defensive programming
						$this->accept_encoding['identity']	= 10;
					} else {
						if( isset( $this->accept_encoding['*'])) {
							$val	= $this->accept_encoding['*'];
							if( empty( $this->accept_encoding['gzip']) and empty( $this->accept_encoding['x-gzip']))
								$this->accept_encoding['gzip']			= $val;
							if( empty( $this->accept_encoding['compress']) and empty( $this->accept_encoding['x-compress']))
								$this->accept_encoding['compress']		= $val;
							if( empty( $this->accept_encoding['deflate']))
								$this->accept_encoding['deflate']		= $val;
							if( empty( $this->accept_encoding['identity']))
								$this->accept_encoding['identity']		= $val;
						}
						foreach( $this->accept_encoding AS $key => $val ) {
							if( $val <= 0 ) unset( $this->accept_encoding[ $key ]);
						}
						// we depart from the rfc here; rfc2616 has situations where the combo of
						// accept-encodings can lead to sending a 406; we take the attitude that
						// no-encoding (identity) is always acceptable and the backstop
						if( empty( $this->accept_encoding )) {
							$this->accept_encoding['identity']	= 10;
						} else {
							arsort( $this->accept_encoding );	// highest q-value at the beginning
						}
					}
				} else {	// if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_ENCODING']))
					// rfc2616-sec14.html#sec14.3 (isset but empty) identity is only acceptable encoding
					// rfc2616-sec14.html#sec14.2 (not set at all) default is identity encoding
					$this->accept_encoding['identity']	= 10;
				}	// end $accept_encoding

				// $accept_language
				// language template: `language[;q=(digit[.digit])][,]`
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_LANGUAGE'])) {
					$temp = strtolower( stripslashes( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_LANGUAGE']));
					$match	= explode(',', $temp );
					if( !is_array( $match )) $match = array( $match );
					foreach( $match AS $val ) {
						if( strpos( $val, 'q=') === FALSE ) {
							$q		= 10;
							$val	= trim( $val );
						} else {
							preg_match('@^(.+);[ ]*q=(1.*|(?:0\.[0-9]+))@', $val, $str );
							if( empty( $str[ 2 ])) {	// $str[ 2 ] is Q-value
								// preg_match() has failed; may have to abort this one...
								if(( strpos( $val, ';')) === FALSE )	continue;
								else												$q	= 10;
							} else {
								$q	= ( $str[ 2 ] == 1 )
									? 10
									: ( int ) ( 10 * (( float ) $str[ 2 ]));
								$val	= trim( $str[ 1 ]);
							}
						}
						if(( $p = strpos( $val, ';')) !== FALSE ) $val = substr( $val, 0, $p );
						$this->accept_language[ $val ] = $q;
					}
					if( empty( $this->accept_language )) {	// more defensive programming
						$this->accept_language['*']	= 10;
					} else {
						arsort( $this->accept_language );	// highest q-value at the beginning
					}
				} else {	// if( !empty( ${$this->_SERVER_ARRAY}['HTTP_ACCEPT_LANGUAGE']))
					$this->accept_language['*']	= 10;
				}	// end $accept_language

				// Cache-Control request headers
				// $_cache_control_request['pragma'] (HTTP/1.x)
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_PRAGMA'])) {
					$this->_cache_control_request['pragma']	= strtolower( stripslashes( ${$this->_SERVER_ARRAY}['HTTP_PRAGMA']));
				}	// end $_cache_control_request['pragma']
				// $_cache_control_request['max-age'], ['no-cache'], + ['no-store'] (HTTP/1.1)
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_CACHE_CONTROL'])) {
					$temp = strtolower( stripslashes( ${$this->_SERVER_ARRAY}['HTTP_CACHE_CONTROL']));
					if( strstr( $temp, 'no-cache') != FALSE ) {
						$this->_cache_control_request['no-cache']	= TRUE;
					}
					if( strstr( $temp, 'no-store') != FALSE ) {
						$this->_cache_control_request['no-store']	= TRUE;
					}
					if( !isset( $this->_cache_control_request['no-cache'])) {
						// If a request includes no-cache, it SHOULD NOT include max-age.
						// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.4
						$temp	= explode(',', $temp );
						foreach( $temp as $str ) {
							if( strstr( $str, 'max-age') != FALSE ) {
								$this->_cache_control_request['max-age']	= ( int ) substr( $str, 8 );
								break;
							}
						}
					}
				}	// end Cache-Control request headers
			}	// if(( $this->protocol != 'HTTP/1.1') and ( $this->method == 'HEAD')) else

			if( $this->protocol == 'HTTP/1.1') {
				// $if_unmodified_since
				if( !empty( ${$this->_SERVER_ARRAY}['HTTP_IF_UNMODIFIED_SINCE'])) {
					$temp = stripslashes( ${$this->_SERVER_ARRAY}['HTTP_IF_UNMODIFIED_SINCE']);
					if(( $p = strpos( $temp, ';')) !== FALSE ) $temp = substr( $temp, 0, $p );
					if(( $this->if_unmodified_since = strtotime( $temp )) == -1 ) $this->if_unmodified_since = '';
					if( $this->if_unmodified_since > $this->now ) $this->if_unmodified_since = '';
				}
				if( $this->_isWeakETag == FALSE ) {	// MUST use the strong comparison function
					// $range - can only be used with strong ETags
					if( !empty( ${$this->_SERVER_ARRAY}['HTTP_RANGE'])) {
						$temp = stripslashes( ${$this->_SERVER_ARRAY}['HTTP_RANGE']);
						if(( $p	= strpos( $temp, ';')) !== FALSE ) $temp = substr( $temp, 0, $p );
						$temp	= explode(',', $temp );
						if( !is_array( $temp )) $temp = array( $temp );
						// now contains an array of `'begin-integer''-''end-integer'` pairs
						// either integer (but not both) may be empty
						foreach( $temp AS $val ) {
							if(( $p	= strpos( $val, '-')) !== FALSE ) {
								if( $p == 0 ) {
									$this->range['end']	= ( int ) substr( $val, 1 );			// no 'begin-integer'
								} elseif( $p == ( strlen( $val ) - 1 )) {
									$this->range['begin']	= ( int ) substr( $val, 0, -1 );	// no 'end-integer'
								} else {
									$start	= ( int ) substr( $val, 0, $p );
									$end		= ( int ) substr( $val, $p + 1 );
									if( $end >= $start ) $this->range[ $start ] = $end;
									else {	// invalid spec - ignore the whole thing
										$this->range	= array();
										break;
									}
								}
							}
						}
						if( count( $this->range ) > 1 ) {	// we do not currently send multipart/byteranges...
							$this->range	= array();			// ...so lie
						}
					}	// end $range
					// $if_match
					if( !empty( ${$this->_SERVER_ARRAY}['HTTP_IF_MATCH'])) {
						$temp = stripslashes( ${$this->_SERVER_ARRAY}['HTTP_IF_MATCH']);
						if(( $p	= strpos( $temp, ';')) !== FALSE ) $temp = substr( $temp, 0, $p );
						$this->if_match	= explode(',', $temp );
						if( !is_array( $this->if_match )) $this->if_match = array( $temp );
						// check validity
						foreach( $this->if_match AS $val ) {
							if( substr( $val, 0, 2 ) == 'W/') {	// whoops
								$this->if_match	= array();
								break;
							}
						}
					}	// end $if_match
					// $if_range
					if( !empty( ${$this->_SERVER_ARRAY}['HTTP_IF_RANGE']) and ( !empty( $this->range ))) {
						$temp = stripslashes( ${$this->_SERVER_ARRAY}['HTTP_IF_RANGE']);
						if(( $p	= strpos( $temp, ';')) !== FALSE ) $temp = substr( $temp, 0, $p );
						if( substr( $temp, 0, 2 ) == 'W/') {
							// is weak ETag - cannot do ranges
						} else {
							if(( substr( $temp, 0, 1 ) == '"') and ( substr( $temp, -1 ) == '"')) {	// ETag
								$this->if_range			= $temp;
								if( empty( $this->if_match )) {
									$this->if_match	= array( $temp );
								} else {
									$this->if_match[]	= $temp;
									$this->if_match	= array_unique( $this->if_match );
								}
							} else {																						// date
								if(( $this->if_range = strtotime( $temp )) == -1 )	$this->if_range = '';
								elseif( $this->if_range > $this->now )					$this->if_range = '';
								elseif( empty( $this->if_unmodified_since ))
									$this->if_unmodified_since	= $this->if_range;
								else if( $this->if_unmodified_since != $this->if_range ) {
									// a plague on both their houses
									$this->if_unmodified_since = $this->if_range = '';
								}
							}
						}
					}
				} else if(( $this->method == 'GET') or ( $this->method == 'HEAD')) {	// then weak is fine
					// $if_none_match
					if( !empty( ${$this->_SERVER_ARRAY}['HTTP_IF_NONE_MATCH'])) {
						$temp = stripslashes( ${$this->_SERVER_ARRAY}['HTTP_IF_NONE_MATCH']);
						if(( $p	= strpos( $temp, ';')) !== FALSE ) $temp = substr( $temp, 0, $p );
						$this->if_none_match	= explode(',', $temp );
						if( !is_array( $this->if_none_match )) $this->if_none_match = array( $temp );
					}
				}
			}	// if( $this->protocol == 'HTTP/1.1')

			// $user_agent
			if( !empty( ${$this->_SERVER_ARRAY}['HTTP_USER_AGENT'])) {
				$this->user_agent	= ${$this->_SERVER_ARRAY}['HTTP_USER_AGENT'];
				// 1 Platform
				if( strstr( $this->user_agent, 'Win')) {
					$this->browserOS = 'Win';
				} else if( strstr( $this->user_agent, 'Mac')) {
					$this->browserOS = 'Mac';
				} else if( strstr( $this->user_agent, 'Linux')) {
					$this->browserOS = 'Linux';
				} else if( strstr( $this->user_agent, 'Unix')) {
					$this->browserOS = 'Unix';
				} else if( strstr( $this->user_agent, 'OS/2')) {
					$this->browserOS = 'OS/2';
				} else {
					$this->browserOS = 'Other';
				}

				// 2 browser and version; $browserVersion accurate upto v12.34.56.78
				// must check everything else before Mozilla
				if( preg_match('@Opera(/| )([0-9]{1,2}(?:\.[0-9]{1,2}){0,3})@', $this->user_agent, $match )) {
					$this->browserVersion	= $match[ 2 ];
					$this->browserAgent		= 'OPERA';
				} elseif( preg_match('@MSIE ([0-9]{1,2}(?:\.[0-9]{1,2}){0,3})@', $this->user_agent, $match )) {
					$this->browserVersion	= $match[ 1 ];
					$this->browserAgent		= 'MSIE';
				} elseif( preg_match('@Firefox(/| )([0-9]{1,2}(?:\.[0-9]{1,2}){0,3})@', $this->user_agent, $match )) {
					$this->browserVersion	= $match[ 2 ];
					$this->browserAgent		= 'FIREFOX';
				} elseif( preg_match('@OmniWeb/([0-9]{1,2}(?:\.[0-9]{1,2}){0,3})@', $this->user_agent, $match )) {
					$this->browserVersion	= $match[ 1 ];
					$this->browserAgent		= 'OMNIWEB';
				// Konqueror 2.2.2 says Konqueror/2.2.2
				// Konqueror 3.0.3 says Konqueror/3
				} elseif( preg_match('@(Konqueror/)(.*)(;)@', $this->user_agent, $match )) {
					$this->browserVersion	= $match[ 2 ];
					$this->browserAgent		= 'KONQUEROR';
				} elseif( preg_match('@Mozilla/([0-9]{1,2}(?:\.[0-9]{1,2}){0,3})@', $this->user_agent, $match1 ) && preg_match('@Safari/([0-9]*)@', $this->user_agent, $match2 )) {
					$this->browserVersion	= $match1[ 1 ] . '.' . $match2[ 1 ];
					$this->browserAgent		= 'SAFARI';
				} elseif( preg_match('@Mozilla/([0-9]{1,2}(?:\.[0-9]{1,2}){0,3})@', $this->user_agent, $match )) {
					$this->browserVersion	= $match[ 1 ];
					$this->browserAgent		= 'MOZILLA';
				} else {
					$this->browserVersion	= 0;
					$this->browserAgent		= 'OTHER';
				}
			}	// end $user_agent

			// $referer
			// 05 Mar 07 bugfix: ['uri'] placed *below* parse_url() (else not included)
			if( !empty( ${$this->_SERVER_ARRAY}['HTTP_REFERER'])) {
				$REFERER						= $referer = ${$this->_SERVER_ARRAY}['HTTP_REFERER'];
				if( !$this->_noReferLC )	$referer	= strtolower( $REFERER );
				$this->referer				= parse_url( $referer );	// auto-urldecoded
				$this->referer['uri']	= $REFERER;
			}	// end $referer

			return TRUE;
		}	// Conteg::_initRequest()
/*
 * _initResponse() - Init page Response Headers
 *
 * parameter: $final_time	= FALSE
 *            this function may be called many times
 *            it concentrates on 304, 406 & 412 processing for all but the final call
 *
 * Returns TRUE
 *
 * - 19 Aug 10 mod: _initResponse() made multi-call, so that 304/406/412 calls can exit() immediately
 * - 08 Mar 08 bugfix, Content-Encoding: added missing check for empty $accept_encoding (prevent PHP Notices)
 *             bugfix, Accept-Charset: added missing check for empty $accept_charset (prevent PHP Notices)
 * - 08 Oct 07 all $vary processing now occurs here; may be switched off with $_noVary
 * - 10 Jun 06 added MSIE SSL test in Cache-Control
 * - 05 Jun 06 updated with Cache-Control
 * - 25 Aug 05 added -AK
 */
		function _initResponse(
			$final_time	= FALSE
		) {
			// initialise certain Class variables
			$this->cache_control	= '';
			$this->vary				= FALSE;
			$this->_is304			= FALSE;
			$this->_is406			= FALSE;
			$this->_is412			= FALSE;

			// Cache-Control

			// MSIE: 'no-cache' under SSL causes download failure; see
			// http://support.microsoft.com/default.aspx?scid=kb;en-us;815313
			if(
				( $this->browserAgent == 'MSIE') and $this->ssl and
				isset( $this->_cache_control_response['no-cache']) and 
				( $this->_cache_control_response['no-cache'] == TRUE )
			) $this->_cache_control_response['no-cache']	= FALSE;

			if( !empty( $this->_cache_control_response )) {
				foreach( $this->_cache_control_response as $key => $val ) {
					switch( $key ) {
						case 'max-age':
						case 'post-check':
						case 'pre-check':
						case 's-maxage':
							$this->cache_control	.= "$key=$val, ";
							break;
						default:
							if( $val ) $this->cache_control	.= "$key, ";
					}
				}
				// remove last 2 chars
				$this->cache_control	= substr( $this->cache_control, 0, -2 );
			}

			// Content-Type
			if( empty( $this->_contentType ) or ( $this->_noContentType == TRUE )) {
				$this->content_type	= '';
			} else {
				$this->content_type	= $this->_contentType . (( $this->protocol == 'HTTP/1.1')
					? (( empty( $this->_charset ))
						? ''
						: '; charset='. $this->_charset
						)
					: ''	// do not use charset for old clients - rfc2616-sec3.html#sec3.7
					);
			}

			// Content-Language
			if( $this->_noContentLang == TRUE ) {
				$this->content_lang	= '';
			}

			// If-Modified-Since
			// 2007-11-02 Foll line added for those rare occasions where must be no 304s
			if( $this->_noLastModified )	{ $this->last_modified	= 0; }
			if( $this->_noExpires )			{ $this->expires			= 0; }
			if(( !empty( $this->last_modified )) and ( !empty( $this->if_modified_since ))) {
				if(( $this->protocol != 'HTTP/1.1') and ( $this->method == 'HEAD')) {
					// rfc1945.txt: conditional Headers are ignored
				} else {
					$IMSis304 = ( $this->if_modified_since >= $this->last_modified );
				}
			}

			// Accept (media-type)
			if(( $this->_noAccept == FALSE ) and ( !empty( $this->_contentType ))) {
				$this->_is406 = ( $this->mediaTypeAccepted()) ? FALSE : TRUE;
			}

			// Accept-Charset
			if(
				( $this->_noAcceptCharset == FALSE ) and
				( !empty( $this->_charset )) and
				( !empty( $this->accept_charset )) and
				( $this->_is406 != TRUE )
			) {
				$this->_is406 = ( $this->charsetAccepted()) ? FALSE : TRUE;
			}

			if( $this->protocol == 'HTTP/1.1') {
				// If-Unmodified-Since
				if(( !empty( $this->last_modified )) and ( !empty( $this->if_unmodified_since ))) {
					$IUSis412 = ( $this->last_modified > $this->if_unmodified_since );
				}

				// ETags
				if( $this->_noETag == FALSE ) {	// use ETags
					if( $this->_isExtETag == FALSE ) {	// ETag set by Class
						if( $this->_isWeakETag ) {
							$this->etag	= 'W/"'. md5(
								$this->_scriptName.
								$this->_port.
								$this->_query.
								$this->_session.
								$this->last_modified.
								$this->content_type.
								$this->content_lang.
								$this->_charset.
								$this->_other_var
							). '"';
						} elseif(( $final_time !== FALSE ) and ( !empty( $this->data ))) {	// byte-identical ETag; faster algos welcomed
							$this->etag	= '"'. md5( $this->data ) .'"';
						}
					}
					if( !empty( $this->etag )) {
						// If-None-Match
						if( !empty( $this->if_none_match )) {
							$etag			= ( $this->_isWeakETag ) ? substr( $this->etag, 2 ) : $this->etag;
							$INMfound	= FALSE;
							foreach( $this->if_none_match AS $val ) {
								if(( strpos( $val, $etag ) !== FALSE ) or ( $val == '*')) {
									$INMfound	= TRUE;
									break;
								}
							}
						}
						// If-Match
						if( !empty( $this->if_match ) and ( $this->_isWeakETag == FALSE )) {
							$IMfound	= FALSE;
							foreach( $this->if_match AS $val ) {
								if(( strpos( $val, $this->etag ) !== FALSE ) or ( $val == '*')) {
									$IMfound	= TRUE;
									break;
								}
							}
						}	// end If-Match
					}	// if( !empty( $this->etag )
				}	// if( $this->_noETag == FALSE )
			}	// if( $this->protocol == 'HTTP/1.1')

			if( isset( $IMfound ) and ( $IMfound == FALSE )) $this->_is412 = TRUE;
			if( isset( $INMfound ) and ( $INMfound == TRUE )) {
				if( isset( $IMSis304 ) and ( $IMSis304 == FALSE )) {
					// rfc2616-sec14.html#sec14.26 - eTag matches but IMS does not - do nothing
				} else {
					if(( $this->method == 'GET') or ( $this->method == 'HEAD')) {
						$this->_is304 = TRUE;
					} else {
						$this->_is412 = TRUE;
					}
				}
			} elseif( isset( $INMfound ) and ( $INMfound == FALSE )) {
				// rfc2616-sec14.html#sec14.26 - ignore IMS if no eTags match
				if( isset( $IMSis304 )) unset( $IMSis304 );
			}
			if( isset( $IMSis304 ) and ( $IMSis304 == TRUE )) $this->_is304 = TRUE;
			if( isset( $IUSis412 ) and ( $IUSis412 == TRUE )) $this->_is412 = TRUE;

			// If-Range
			if(( $this->_noAcceptRanges == FALSE ) and ( !empty( $this->if_range )) and ( !empty( $this->range ))) {
				// If-Range modifies conditional behaviour:
				// if 'send a 412' then send the whole data instead (updated entity)
				if( $this->_is412 == TRUE ) {
					$this->_is412				= FALSE;
					if( $final_time !== FALSE ) {
						// only do this on last call
						$this->range				= array();
					}
				} else {
				// if 'send the data' then send just the ranges (not-modified entity)
				// note: gzip-encoded data is NOT sent in ranges
					$this->_noAcceptEncode	= TRUE;
				}
			}

			// Content-Encoding Negotiation
			if( $final_time !== FALSE ) {
				// only do this on last call
				$this->encoding	= 'identity';														// no 406s here
				if(( $this->_noAcceptEncode == FALSE ) and										// can compress
					(( $this->_noAcceptRanges == TRUE ) or empty( $this->range )) and		// do not compress ranges
					( !( $this->_is304 or $this->_is406 or $this->_is412 ))					// no point here either
				) {
					if(
						( !empty( $this->_encodings )) and
						( !empty( $this->accept_encoding )) and
						(( $this->encoding = $this->negotiateEncoding()) != FALSE )
					) {
						$this->encoding	= key( $this->encoding );
					}
				}	// end Content-Encoding Negotiation
			}	// ( $final_time !== FALSE )

			if(( $this->_is304 == TRUE ) and ( $this->_is412 == TRUE )) {
				// defensive programming; not supposed to happen (perhaps I mis-understood?)
				foreach( $this->_preferOrder AS $val ) {
					switch( $val ) {
						case 'IM':	// if_match
							if( isset( $IMfound ) and ( $IMfound == FALSE )) {
								$this->_is304 = FALSE;
								break 2;
							} else break;
						case 'INM':	// if_none_match
							if( isset( $INMfound ) and ( $INMfound == TRUE )) {
								if(( $this->method == 'GET') or ( $this->method == 'HEAD')) {
									$this->_is412 = FALSE;
								} else {
									$this->_is304 = FALSE;
								}
								break 2;
							} else break;
						case 'IUS':	// if_unmodified_since
							if( isset( $IUSis412 ) and ( $IUSis412 == TRUE )) {
								$this->_is304 = FALSE;
								break 2;
							} else break;
						case 'IMS':	// if_modified_since
						default:
							if( isset( $IMSis304 ) and ( $IMSis304 == TRUE )) {
								$this->_is412 = FALSE;
								break 2;
							} else break;
					}
				}
			}	// end resolve $_is304 == $_is412 == TRUE condundrum

			// Vary; header field-names used to negotiate content (for proxies)
			// the server may add to these + W3C page shows none!
			if( !$this->_noVary ) {
				if( $this->_noAccept == FALSE ) {
					$v				= 'Accept';
					$this->vary	.= ( empty( $this->vary ))
						? $v
						: (( strpos( $this->vary, $v ) === FALSE ) ? ",$v" : '');
				}
				if( $this->_noAcceptCharset == FALSE ) {
					$v				= 'Accept-Charset';
					$this->vary	.= ( empty( $this->vary ))
						? $v
						: (( strpos( $this->vary, $v ) === FALSE ) ? ",$v" : '');
				}
				if( $this->_noAcceptEncode == FALSE ) {
					$v				= 'Accept-Encoding';
					$this->vary	.= ( empty( $this->vary ))
						? $v
						: (( strpos( $this->vary, $v ) === FALSE ) ? ",$v" : '');
				}
				if( $this->_noAcceptLang == FALSE ) {
					$v				= 'Accept-Language';
					$this->vary	.= ( empty( $this->vary ))
						? $v
						: (( strpos( $this->vary, $v ) === FALSE ) ? ",$v" : '');
				}
				if( $this->_noUserAgent == FALSE ) {
					$v				= 'User-Agent';
					$this->vary	.= ( empty( $this->vary ))
						? $v
						: (( strpos( $this->vary, $v ) === FALSE ) ? ",$v" : '');
				}
			}

			return TRUE;
		}	// Conteg::_initResponse()
/*
 * _loadAvgBSD() - Gets the max() system load average from `sysctl' command
 *
 * The max() Load Average will be returned, or FALSE (command failed)
 *
 * 2009-04-17 renamed from `_loadAvgFreeBSD()' to `_loadAvgBSD()' + rewritten
 * 2006-08-16 number of cpus added to calculation -AK
 */
		function _loadAvgBSD() {
			if( $buffer = @`sysctl vm.loadavg|cut -d" " -f3-5` ) {
				$load		= explode(' ', $buffer );
				return max(( float ) $load[ 0 ], ( float ) $load[ 1 ], ( float ) $load[ 2 ]) / $this->_num_cpu;
			} else {
				return FALSE;
			}
		}
/*
 * _loadAvgLinux() - Gets the max() system load average from /proc/loadavg
 *
 * The max() Load Average will be returned, or FALSE (command failed)
 *
 * Note: `open_basedir' (php.ini) will require addition of `/proc/loadavg'
 *
 * 2009-04-17 file_get_contents() used in favour of previous fopen()/fclose()
 * 2006-08-16 number of cpus added to calculation -AK
 */
		function _loadAvgLinux() {
			if( $buffer = @file_get_contents('/proc/loadavg')) {
				$load		= explode(' ', $buffer );
				return max(( float ) $load[ 0 ], ( float ) $load[ 1 ], ( float ) $load[ 2 ]) / $this->_num_cpu;
			} else {
				return FALSE;
			}
		}
/*
 * _loadAvgUnix() - Gets the max() system load average from `uptime' command
 *
 * The max() Load Average will be returned, or FALSE (command failed)
 *
 * 2009-04-17 added as fall-back for generic Unix
 */
		function _loadAvgUnix() {
			if( $buffer = @`uptime` ) {
				ereg("averag(es|e): ([0-9][.][0-9][0-9]),([0-9][.][0-9][0-9]),([0-9][.][0-9][0-9]*)", $buffer, $load );
				return max(( float ) $load[ 2 ], ( float ) $load[ 3 ], ( float ) $load[ 4 ]) / $this->_num_cpu;
			} else {
				return FALSE;
			}
		}
/*
 * _trigger_error() - Sets $_error + issues notices
 *
 * Either exit() or return FALSE
 */
		function _trigger_error(
			$err,
			$level = E_USER_NOTICE
		) {
			switch( $level ) {
//				case E_NOTICE:				// default no print, no stop
				case E_USER_NOTICE:
					trigger_error( $err, $level );
					return FALSE;
//				case E_COMPILE_WARNING:	// default print, no stop
//				case E_CORE_WARNING:
				case E_USER_WARNING:
//				case E_WARNING:
					$this->_error .= "<br />\n$err";
					trigger_error( $err, $level );
					return FALSE;
//				case E_COMPILE_ERROR:	// default print, stop
//				case E_CORE_ERROR:
//				case E_ERROR:
//				case E_PARSE:
				case E_USER_ERROR:
				default:
					$this->_error .= "<br />\n$err";
					trigger_error( $err, $level );
					die();
			}	// switch( $level )
		}	// Conteg::_trigger_error()
	}	// class Conteg
// -------------- End of Class Declaration ----------------
/*
 * Help, Advice + Other Stuff
 * ==========================
 * (see accompanying text files for usage)
 * (also search for `Conteg' within Modem-Help forums)
 *
 * Requirements:
 * ------------
 *    PHP 4.3.0+: $file + $line bug-catching; file_get_contents()
 *    zlib:       Needed for compression encoding (odds are you have it).
 *    `ExtendedStatus On' in httpd.conf (Apache) (re: $_SERVER variables)
 *
 *
 * How to use:
 * ==========
 *
 *  Note before: SMP machines: use the setup parameter:
 *    'cpu_number' => (number-of-cpus)
 *
 *  Simplest possible usage requires 3 lines:
 *    1 Turn Output Buffering on.
 *    2 Include the Class file.
 *    3 On the *very* last line create an instance of the Class.
 *
 *    ------------Start of file---------------
 *    |<?php
 *    | ob_start();            // <==== line 1
 *    | include('Conteg.inc'); // <==== line 2
 *    |
 *    |... the page ...
 *    |
 *    | new Conteg();          // <==== line 3
 *    |?>
 *    -------------End of file----------------
 *
 *  Variations:
 *  ----------
 *    A `$param' array is available as a setup parameter to the Class constructor
 *+   (line 3). It is an array of `parameter-name' => `parameter-value' pairs.
 *
 *  Here is the simplest HTTP/1.0 usage:
 *
		$param	= array(
			'modified' => strtotime( $mdate ),	//  here, $mdate is string (default is time())
			'expiry'   => 864000						//  set expiry date 10 days from now
		);													//+ default is 1 hour
		$Encode	= new Conteg( $param );
 *
 *  Here is the simplest HTTP/1.1 usage:
 *
		$param	= array(
			'use_etag'	=> TRUE,						// default is Weak ETags
			'modified'	=> $mdate,					// here, $mdate is Unix timestamp
			'expiry'		=> 3600						// set expiry date 1 hour from time()
		);
		$Encode	= new Conteg( $param );
 *
 *
 *  Content Negotiation:
 *  ===================
 *
 *  Request Headers:
 *  ---------------
 *    Accept:              (negotiate outside Class) (media-type)
 *      $accept['type/sub-type']     = quality-integer (0-10)
 *                         Defaults:  'use_accept'         => FALSE
 *                                    'type'               => 'text/html'
 *                         Functions: getAccept()
 *                                    mediaTypeAccepted()
 *                                    isResponseNoContent()
 *                         accept:    (normal action)
 *                         or:        406 Not Acceptable
 *
 *    Accept-Charset:      (negotiate outside Class)
 *      $accept_charset['charset']   = quality-integer (0-10)
 *                         Defaults:  'use_accept_charset' => FALSE
 *                                    'charset'            => 'ISO-8859-1'
 *                         Functions: getAcceptCharset()
 *                                    charsetAccepted()
 *                                    isResponseNoContent()
 *                         accept:    (normal action)
 *                         or:        406 Not Acceptable
 *
 *    Accept-Encoding:     Auto-negotiated inside Class if TRUE
 *      $accept_encoding['encoding'] = quality-integer (0-10)
 *                         Defaults:  'use_accept_encode'  => TRUE
 *                                    'use_user_agent'     => TRUE
 *                                    'encodings'          => array('gzip','deflate','compress')
 *                         Functions: compress()
 *                                    getCompLevel()
 *                                    getSize()
 *                                    negotiateEncoding()
 *                                    setSearch()
 *                         accept:    load-balanced compression
 *                         or:        `identity' (no compression)
 *
 *    Accept-Language:     (negotiate outside Class)
 *      $accept_language['lang']     = quality-integer (0-10)
 *                         Defaults:  'use_accept_lang'    => FALSE
 *                                    'lang'               => 'en'
 *                         Function:  getAcceptLang()
 *                         accept:    (user decided)
 *                         or:        (user decided)
 *
 *    Accept-Ranges:       Auto-negotiated inside Class if TRUE
 *      $range['begin']              = int (byte)
 *      $range['end']                = int (byte)
 *                         Defaults:  'use_accept_ranges'  => FALSE
 *                         accept:    206 Partial Content
 *                         or:        416 Requested Range Not Satisfiable
 *                         Notes:     multipart/byteranges not accepted
 *                                    cannot be used with weak eTags
 *                                    switches off compression if used
 *
 *    If-Modified-Since:   Auto-negotiated inside Class if TRUE
 *      $if_modified_since           = timestamp
 *                         Defaults:  'use_last_modified'  => TRUE
 *                                    'use_expires'        => TRUE
 *                                    'modified'           => NULL
 *                                    'expiry'             => 3600 (1 hour)
 *                         Function:  isResponseNoContent()
 *                         accept:    304 Not Modified
 *                         or:        (normal action)
 *
 *    If-None-Match:       Auto-negotiated inside Class if TRUE
 *      $if_none_match[]             = eTags
 *                         Defaults:  'use_etag'           => FALSE
 *                                    'weak_etag'          => TRUE
 *                                    'etag'               => ''
 *                                    'other_var'          => ''
 *                         Function:  isResponseNoContent()
 *                         accept:    304 Not Modified
 *                         or:        412 Precondition Failed
 *
 *    If-Match:            Auto-negotiated inside Class if TRUE
 *      $if_match[]                  = eTags
 *                         Defaults:  same as If-None-Match
 *                         Function:  isResponseNoContent()
 *                         accept:    (normal action)
 *                         or:        412 Precondition Failed
 *
 *    If-Range:            Auto-negotiated inside Class if TRUE
 *      $if_range                    = timestamp/string (modified / eTag)
 *                         Defaults:  same as If-None-Match / If-Unmodified-Since
 *                         if 412:    send 200 OK
 *                         if 200:    send 206 Partial content
 *                         Notes:     eTags are added to $if_match[]
 *                                    dates are added to $if_unmodified_since
 *
 *    If-Unmodified-Since: Auto-negotiated inside Class if TRUE
 *      $if_unmodified_since         = timestamp
 *                         Defaults:  same as If-Modified-Since
 *                         Function:  isResponseNoContent()
 *                         accept:    (normal action)
 *                         or:        412 Precondition Failed
 *
 *  (Reported - no negotiation)
 *
 *    Referer:
 *      $referer[]                   = parse_url( $referer )
 *                         Defaults: 'referer_lower_case' => TRUE
 *                         Functions: getReferer()
 *
 *    User-Agent:
 *      $user_agent                  = string
 *      $browserAgent                = string
 *      $browserOS                   = string
 *      $browserVersion              = string
 *
 *  Response Headers:
 *  ----------------
 *    Content-Language:    Added if TRUE and not empty
 *                         Defaults:  'use_content_lang'   => TRUE
 *                                    'lang'               => 'en'
 *
 *    Content-Type:        Added if TRUE and not empty
 *                         Defaults:  'use_content_type'   => TRUE
 *                                    'type'               => 'text/html'
 *                                    'charset'            => 'ISO-8859-1'
 *
 *
 *  External Negotiation will require the 'noprint' parameter:
 *  --------------------
 *
		$Encode = new Conteg(
			array(
				'noprint'  => TRUE
			)
		);
 *    ...
 *    (negotiation, processing, etc.)
 *    ...
		$Encode->show();
 *
 *
 * 304, 406, 412 (early exit)
 * =============
 *
 *  Note before: The best way to lower system resources, reduce bandwidth + increase page-
 *               delivery speed is to send headers with no content (!)
 *
 *  Simplest possible HTTP/1.0 usage requires 5 lines:
 *    1 Turn Output Buffering on.
 *    2 Include the Class file.
 *    3 Create an instance of the Class:
 *      use `noprint' + `modified' parameters
 *    4 Check for `304' response-status:
 *      show+exit if so, else continue script
 *    5 On the *very* last line `show()' page
 *
 *    ------------Start of file---------------
 *    |<?php
 *    | ob_start();            // <==== line 1
 *    | include('Conteg.inc'); // <==== line 2
 *    | $Encode = new Conteg(  // <==== line 3
 *    |   array(
 *    |     'modified' => $mdate, // timestamp
 *    |     'noprint'  => TRUE
 *    |   )
 *    | );
 *    | if( $Encode->isResponseNoContent()) {
 *    |   $Encode->show();
 *    |   exit();              // <==== line 4
 *    | }
 *    |
 *    |... the rest of page ...
 *    |
 *    | $Encode->show();       // <==== line 5
 *    |?>
 *    -------------End of file----------------
 *
 *  Note after:  `modified' + other parameters can be delivered at any time after
 *               instantiation with setup(), as often as you wish. It makes no sense to
 *               use isResponseNoContent() until the complete set of relevant HTTP/1.0 or
 *               HTTP/1.1 control-parameters are set in Conteg.
 *               See accompanying text files, particularly for HTTP/1.1 usage.
 *
 *
 *  Cache-Control:
 *  =============
 *  HTTP/1.0: Governed by value of `Expires' + `Pragma: no-cache' headers.
 *            Expires: "If the date given is equal to or earlier than the value of the Date
 *+                    header, the recipient must not cache the enclosed entity."
 *+           http://www.w3.org/Protocols/rfc1945/rfc1945.txt sec 10.7
 *            Pragma:  Request header; sec 10.12 (also erroneously used in response headers)
 *
 *  HTTP/1.1: Cache-Control Request + Response headers.
 *            http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9 (see also sec 13.4)
 *
 *  Two functions are provided to be able to respond to request headers:
 *     requestNoCache()	- returns: TRUE : a fresh response has been requested
 *                                 (int): max requested age in secs of any cached-response
 *                                 FALSE: no such request
 *     requestNoStore()	- returns: TRUE : do not store (cache) this request
 *                                 FALSE: no such request
 *
 *  Setting Response Cache-Control headers:
 *  --------------------------------------
 *     As with all other responses, Conteg is controlled by an array key within the single
 *     parameter supplied to the constructor. In the case of Cache-Control headers this is:
 *
 *     'cache_control' => (array)
 *
 *     (note that this array key is, itself, an array of key => value pairs)
 *
		$Encode = new Conteg(
			array(
				'cache_control' => array(
					'macro' => 'cache-all'
				)
			)
		);
 *
 *     Any of the individual parts of the Cache-Control header may be set; see the comments
 *     to setup() for details. For your convenience, here are all of the sub-parts:
 *
		$Encode = new Conteg(
			array(
				'cache_control'	=> array(
					'max-age'		=> (int),			// secs; overrides the Expires header
					'must-revalidate',					// forces caches to validate every request with server
					'no-cache',
					'no-store',
					'no-transform',
					'post-check'	=> (int),
					'pre-check'		=> (int),
					'private',
					'proxy-revalidate',
					'public',
					's-maxage'		=> (int),			// secs; for shared (not private) caches
					'pragma'			=> (string),		// strictly, not Cache-Control

					'macro'			=> 'cache-all',	// cache under all circumstances (default)
					'macro'			=> 'cache-none'	// never cache
				)
			)
		);
 *
 *     Any `no-cache' value will cause the `Expires' value to be reset to a date in the past.
 *
 *  Vary headers:
 *  ============
 *     By default, a `Vary ' header is sent if the response suffers any form of Content negotiation.
 *     By default, the response is negotiated for `Accept-Encoding', and this is itself varied by User-Agent.
 *     Thus, by default a `Vary: User-Agent, Accept-Encoding' header will ALWAYS be sent. This behaviour is
 *     controlled by the Constructor setup() parameters:
 *
 *       'use_accept'         => FALSE
 *       'use_accept_charset' => FALSE
 *       'use_accept_encode'  => TRUE
 *       'use_accept_lang'    => FALSE
 *       'use_user_agent'     => TRUE
 *       'use_vary'           => TRUE
 *
 *     Setting `use_vary' to FALSE will prevent any `Vary' headers from being sent.
 *     Setting any other of the parameters above to `FALSE' will switch off Content Negotiation for that
 *     specific Request header, although the value of the header is still reported.
 *
 *     Important note: MSIE 4.x throws a (false) error message with Vary headers.
 *                     MSIE 5 + 6 will refuse to cache content (no 304s) for any except `Vary: User-Agent'.
 *                     (see also http://forums.modem-help.co.uk/viewtopic.php?p=1236)
 *
 *
 *  References:
 *  ==========
 *    compression: rfc2616 (Sections: 3.5, 14.3, 14.11)
 *    HTTP/1.1:    http://www.w3.org/Protocols/rfc2616/rfc2616.html
 *    HTTP/1.0:    http://www.w3.org/Protocols/rfc1945/rfc1945.txt
 *    Media-types: http://www.ietf.org/rfc/rfc1521.txt
 *
 *  Notes:
 *  =====
 *  1 Compression extended from Gzip_encode v0.67s.3; see
 *    http://leknor.com/code/gziped.php for stats on how a page may compress.
 *    www.modem-help.co.uk shows typical values of +70% at level 8/9 (reduction to
 *    one third of original size), with some pages better than 80% (reduction to
 *    one fifth). On admin-edit pages (masses of duplicated <select> drop-down boxes)
 *    reductions exceed 90%. Wow!
 *    The routine takes < 0.002 secs on a twin Xeon 2.4 GHz, Linux 2.6.
 *  2 Class also reports Referer (sic), Browser + OS-platform;
 *       Referer:               see getReferer() for details
 *       Browser + OS-platform: active use made within negotiateEncoding().
 *                              Further reports on other browsers, etc welcomed.
 *  3 Global search/replace available via `search' parameter. Default is
 *    compression stats, if declared in text.
 *  4 This Class is aimed at delivery of webpages/files via http protocol, which
 *    implies HTTP Status 200, 206, 304, 406, 412 or 416 (all auto-encoded) or 404/410.
 *    A user-determined http status may also be declared; it will over-ride any program
 *    status except 404/410. See also sendStatusHeader().
 *
 *  Change Log:
 *  ==========
 *    0.13.9:  isResponseNoContent() added (for early 304, 406, 416)      20 Aug 10
 *    0.13.8:  _loadAvgUnix() added, _loadAvgBSD(), _loadAvgLinux() +     17 Apr 09
 *+            getCompLevel() rewritten (thanks oriceon).
 *    0.13.7:  $browserVersion bugfix for Safari within _initRequest();   07 Mar 08
 *+            _initResponse() bugfixes: prevent PHP Notices (empty
 *+            Encodings or Charsets).
 *    0.13.6:  Added `use_last_modified' to Constructor parameter, for    02 Nov 07
 *+            those rare occasions where a 304 must NOT be sent, plus
 *+            `use_expires' to be consistent.
 *    0.13.5:  Bugfix: use of `Vary:' header made compliant; added        08 Oct 07
 *+            $_noUserAgent, $_noVary + setup() parameters 'use_user_agent'
 *+            'use_vary' (both TRUE by default).
 *    0.13.4:  Bugfix: _initRequest() for non-std Q-values                07 Sep 07
 *    0.13.3:  Bugfix: removed duplicate $if_match declaration; added     07 Jul 07
 *+            $file + $line to headers_sent() for better bug-catching.
 *    0.13.2:  Bugfixes to charsetAccepted() + mediaTypeAccepted()        30 Apr 07
 *    0.13.1:  Added 'cpu_number' to Constructor parameter ($_num_cpu)    05 Mar 07
 *+            bugfix for $referer (['uri'] not included in array)
 *    0.13:    Added $referer, getReferer(), '404_to_410', $_no410,       18 Feb 07
 *+            'http_status', $_httpStatus, 'msie_error_fix',
 *+            $_noMSErrorFix + sendStatusHeader() bugfix.
 *             See also http://forums.modem-help.co.uk/viewtopic.php?t=670
 *    0.12.3:  Bugfix for 'expiry' (was always 1 hr) (thank you Bob).     04 Oct 06
 *    0.12.2:  Added $_num_cpu for SMP boxes (like mine!).                18 Aug 06
 *    0.12.1:  Added sendStatusHeader() + 404 responses (used to create   02 Ju1 06
 *+            custom Error-pages).
 *    0.12:    Added requestNoCache(), requestNoStore(),                  04 Jun 06
 *+            $_cache_control_response + $_cache_control_request arrays.
 *    0.11:    BugFix in _initResponse() [INM not tripped but IMS is];    13 Mar 06
 *+            'other_var' added to setup() (ref: weak eTags) (bugfix).   23 Feb 06
 *    0.10:    Gzip_encode rewritten; Request + Response headers added.   25 Aug 05
 *             Browser detect added to negotiateEncoding() to fix blanks. 09 Sep 05
 *             Content-Negotiation completed for compression-Encoding.    16 Sep 05
 *
 * Thanks To:
 * =========
 *  Gzip-encoding originally from work by Sandy McArthur,Jr (see leknor.com).
 *  Response headers inspired by work by Alexandre Alapetite (alexandre.alapetite.net) and others.
 *
 * This version available from:
 *   http://www.modem-help.co.uk/downloads/non-modem/PHP/Conteg/
 *
 *
 * Constructor parameter array
 * ===========================
 *  These are all the possible array values within the single parameter supplied to
 *+ the constructor, and acted upon within setup(), with defaults.
 *  Note: none of the following is required - these are the program defaults:
 *
 array(
   '404'                => FALSE,									// higher precedence than 'http_status'
   '404_to_410'         => TRUE,										// see sendStatusHeader()
   'cache_control'      => array('macro' => 'cache-all'),	// see setup()
   'charset'            => 'ISO-8859-1',
   'cpu_number'         => 1,											// number of CPUs in server 
   'dupe_status_header' => TRUE,										// see sendStatusHeader()
   'encodings'          => array('gzip','deflate','compress'),
   'etag'               => '',
   'expiry'             => 3600,										// secs after time()
   'http_status'        => NULL,										// preferred to program-decided status
   'input'              => 'instream',								// Apache-Notes
   'lang'               => 'en',
   'modified'           => NULL,										// sets $last_modified to time()
   'msie_error_fix'     => TRUE,										// avoid MSIE `friendly` error pages
   'noprint'            => FALSE,
   'other_var'          => '',										// extra string to affect (weak) ETag
   'output'             => 'outstream',							// Apache-Notes
   'prefer'             => array(),
   'ratio'              => 'ratio',									// Apache-Notes
   'referer_lower_case' => TRUE,
   'search'             => array(),
   'type'               => 'text/html',
   'use_accept'         => FALSE,
   'use_accept_charset' => FALSE,
   'use_accept_encode'  => TRUE,
   'use_accept_lang'    => FALSE,
   'use_accept_ranges'  => FALSE,
   'use_apache_notes'   => FALSE,
   'use_content_lang'   => TRUE,
   'use_content_type'   => TRUE,
   'use_etag'           => FALSE,
   'use_expires'        => TRUE,
   'use_last_modified'  => TRUE,										// TRUE = negotiate If-Modified-Since + If-Unmodified-Since
   'use_user_agent'     => TRUE,
   'use_vary'           => TRUE,
   'weak_etag'          => TRUE
 )
 */
?>
