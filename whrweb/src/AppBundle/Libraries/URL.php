<?php

// src/AppBundle/Libraries/URL.php

namespace AppBundle\Libraries

use AppBundle\Libraries\UnicodeString;

/**
 * Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *	* Redistributions of source code must retain the above copyright
 *	  notice, this list of conditions and the following disclaimer.
 *
 *	* Redistributions in binary form must reproduce the above
 *	  copyright notice, this list of conditions and the following
 *	  disclaimer in the documentation and/or other materials provided
 *	  with the distribution.
 *
 *	* Neither the names of David R. Nadeau or NadeauSoftware.com, nor
 *	  the names of its contributors may be used to endorse or promote
 *	  products derived from this software without specific prior
 *	  written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
 * OF SUCH DAMAGE.
 */

/*
 * This is a BSD License approved by the Open Source Initiative (OSI).
 * See:  http://www.opensource.org/licenses/bsd-license.php
 */
 
 /*
  * modified by Reid Johnson 10/10/2016 to support url_to_absolute like a web browser would and to 
  * combine separate files with single functions into a class for ease of use
 */
class URL{

	private $baseURL;
	private $url;
	private $abs;

	function __construct( $url = '', $s = $_SERVER, $use_forwarded_host = false ){
		$this->abs = false;
		$this->setURL( $url );
		$this->baseURL = [
			'scheme' => NULL,
			'user' => NULL,
			'pass' => NULL,
			'host' => NULL,
			'port' => NULL,
			'path' => NULL,
			'query' => NULL,
			'fragment' => NULL
		];

		if( $s !== [] ){
			$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
			$sp       = strtolower( $s['SERVER_PROTOCOL'] );
			$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
			$port     = $s['SERVER_PORT'];
			$port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
			$host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
			$host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
			$baseURL = $protocol . '://' . $host;
			$this->setBaseURL( $baseURL );
		}

	}

	function __get( $name ){
		if( $name === 'url' )
			return $this->__toString();

		if( $name === 'absurl' )
			return $this->asAbsolute();

		if( $name === 'baseURL' || $name === 'baseUrl' || $name === 'baseurl' )
			return $this->getBaseURL();

		if( $name === 'path array' )
			return explode( '/', $this->url[ 'path' ] )

		if( isset( $this->url[ $name ] ) )
			return $this->url[ $name ];

		return NULL;
	}

	function __toString(){
		if( $this->abs )
			return $this->asAbsolute();
		return $this->asString();
	}

	function setAbsolute( $f = true ){
		$this->abs = $f;
		return $this;
	}

	function setURL ( $string ){
		$url = ( new UnicodeString( $url ) )->encode();
		$this->url = $this->split_url( $url, true );

		#this was added here to make this function the way a browser does and add scheme if the url seems to be a hostname rather than a path
		if( $this->url !== false &&
			( !isset( $this->url['scheme'] ) && !isset( $this->url['host'] ) ) &&
			( $contents = file_get_contents( __DIR__ . '\resources\topleveldomains.txt' ) ) !== false
		){
				$tmp = explode( '.', $this->url['path'][0] );
				$tmp = end( $tmp );
				if( isset( $tmp ) && $tmp != '' ){
					$tmp = '/^' . preg_quote( $tmp, '/' ) . '$/im';
					if( preg_match( $tmp, $contents ) ){
						$url = '//' . $url;
						$this->url = $this->split_url( $url, true );
					}
				}
		}

		if( $this->url === false ){
			$this->url = [
				'scheme' => NULL,
				'user' => NULL,
				'pass' => NULL,
				'host' => NULL,
				'port' => NULL,
				'path' => NULL,
				'query' => NULL,
				'fragment' => NULL
			];
		}

		return $this;
	}

	function setBaseURL( $string ){
			$baseURL = ( new UnicodeString( $baseURL ) )->encode();
			$this->baseURL = $this->split_url( $url, true, true );
			if( $this->baseURL === false ){
				$this->baseURL = [
					'scheme' => NULL,
					'user' => NULL,
					'pass' => NULL,
					'host' => NULL,
					'port' => NULL,
					'path' => NULL,
					'query' => NULL,
					'fragment' => NULL
				];
			}
		return $this;
	}

	function getBaseURL(){
		return new ULR( $this->baseURL, [], false );
	}

	function asAbsolute(){

		// If relative URL has a scheme, clean path and return.
		if( $this->url === false )
			return false;

		$r = $this->url;

		if( !empty( $r['scheme'] ) ){
			if( !empty( $r['path'] ) && $r['path'][0] == '/' )
				$r['path'] = $this->url_remove_dot_segments( $r['path'] );
			return $this->join_url( $r, true );
		}

		// Make sure the base URL is absolute.
		$b = $this->baseURL;

		if( $b === false || empty( $b['scheme'] ) || empty( $b['host'] ) )
			return false;

		$r['scheme'] = $b['scheme'];

		// If relative URL has an authority, clean path and return.
		if( isset( $r['host'] ) ){
			if( !empty( $r['path'] ) )
				$r['path'] = $this->url_remove_dot_segments( $r['path'] );
			return $this->join_url( $r, true );
		}

		unset( $r['port'] );
		unset( $r['user'] );
		unset( $r['pass'] );

		// Copy base authority.
		$r['host'] = $b['host'];
		if( isset( $b['port'] ) ) $r['port'] = $b['port'];
		if( isset( $b['user'] ) ) $r['user'] = $b['user'];
		if( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];

		// If relative URL has no path, use base path
		if( empty( $r['path'] ) ){
			if( !empty( $b['path'] ) )
				$r['path'] = $b['path'];
			if( !isset( $r['query'] ) && isset( $b['query'] ) )
				$r['query'] = $b['query'];
			return $this->join_url( $r, true );
		}

		// If relative URL path doesn't start with /, merge with base path
		if( $r['path'][0] != '/' ){
			$base = mb_strrchr( $b['path'], '/', true, 'UTF-8' );
			if( $base === false ) $base = '';
			$r['path'] = $base . '/' . $r['path'];
		}

		$r['path'] = $this->url_remove_dot_segments( $r['path'] );

		return $this->join_url( $r, true );
	}

	function asString(){
		return $this->join_url( $this->url, true );
	}

	protected function url_remove_dot_segments( $path ){
		// multi-byte character explode
		$inSegs  = preg_split( '!/!u', $path );
		$outSegs = array();
		foreach( $inSegs as $seg ){
			if( $seg == '' || $seg == '.')
				continue;
			if( $seg == '..' )
				array_pop( $outSegs );
			else
				array_push( $outSegs, $seg );
		}
		$outPath = implode( '/', $outSegs );
		if( $path[0] == '/' )
			$outPath = '/' . $outPath;
		// compare last multi-byte character against '/'
		if( $outPath != '/' && (mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
			$outPath .= '/';
		return $outPath;
	}

	protected function split_url( $url, $decode = true, $path_as_array = false ){
		$xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
		$xpchar        = $xunressub . ':@%';
		$xscheme       = '([a-zA-Z][a-zA-Z\d+-.]*)';
		$xuserinfo     = '((['  . $xunressub . '%]*)' . '(:([' . $xunressub . ':%]*))?)';
		$xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';
		$xipv6         = '(\[([a-fA-F\d.:]+)\])';
		$xhost_name    = '([a-zA-Z\d-.%]+)';
		$xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
		$xport         = '(\d*)';
		$xauthority    = '((' . $xuserinfo . '@)?' . $xhost . '?(:' . $xport . ')?)';
		$xslash_seg    = '(/[' . $xpchar . ']*)';
		$xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
		$xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
		$xpath_abs     = '(/(' . $xpath_rel . ')?)';
		$xapath        = '(' . $xpath_authabs . '|' . $xpath_abs . '|' . $xpath_rel . ')';
		$xqueryfrag    = '([' . $xpchar . '/?' . ']*)';
		$xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' . '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';
		$parts         = array(
			'scheme' => NULL,
			'user' => NULL,
			'pass' => NULL,
			'host' => NULL,
			'port' => NULL,
			'path' => NULL,
			'query' => NULL,
			'fragment' => NULL
		);

		// Split the URL into components.
		if( !preg_match( '!' . $xurl . '!u', $url, $m ) )
			return false;

		if( !empty($m[2]) )
			$parts['scheme']  = strtolower($m[2]);

		if( !empty($m[7]) ){
			if( isset( $m[9] ) )
				$parts['user']    = $m[9];
			else
				$parts['user']    = '';
		}

		if( !empty($m[10]) )
			$parts['pass']    = $m[11];

		if( !empty($m[13]) )
			$h=$parts['host'] = $m[13];
		else if( !empty($m[14]) )
			$parts['host']    = $m[14];
		else if( !empty($m[16]) )
			$parts['host']    = $m[16];
		else if( !empty( $m[5] ) )
			$parts['host']    = '';

		if( !empty($m[17]) )
			$parts['port']    = $m[18];

		if( !empty($m[19]) )
			$parts['path']    = $m[19];
		else if( !empty($m[21]) )
			$parts['path']    = $m[21];
		else if( !empty($m[25]) )
			$parts['path']    = $m[25];

		if( !empty($m[27]) )
			$parts['query']   = $m[28];

		if( !empty($m[29]) )
			$parts['fragment']= $m[30];

		if( $path_as_array && !$decode )
			$parts['path'] = explode('/', $parts['path']);

		if( !$decode )
			return $parts;

		if( !empty($parts['user']) )
			$parts['user']     = rawurldecode( $parts['user'] );
		if( !empty($parts['pass']) )
			$parts['pass']     = rawurldecode( $parts['pass'] );
		if( !empty($parts['path']) )
			$parts['path']     = rawurldecode( $parts['path'] );
		if( isset($h) )
			$parts['host']     = rawurldecode( $parts['host'] );
		if( !empty($parts['query']) )
			$parts['query']    = rawurldecode( $parts['query'] );
		if( !empty($parts['fragment']) )
			$parts['fragment'] = rawurldecode( $parts['fragment'] );
		if( $path_as_array )
			$parts['path'] = explode('/', $parts['path']);

		return $parts;
	}

	protected function join_url( $parts, $encode = true ){
		if( $encode ){
			if( isset( $parts['user'] ) )
				$parts['user']     = rawurlencode( $parts['user'] );
			if( isset( $parts['pass'] ) )
				$parts['pass']     = rawurlencode( $parts['pass'] );
			if( isset( $parts['host'] ) && !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
				$parts['host']     = rawurlencode( $parts['host'] );
			if( !empty( $parts['path'] ) ){
				if( is_array( $parts['path'] ) )
					$parts['path'] = implode('/', $parts['path']);
				$parts['path']     = preg_replace( '!%2F!ui', '/', rawurlencode( $parts['path'] ) );
			}
			if( isset( $parts['query'] ) ){
				parse_str( $parts['query'], $tmp );
				$parts['query'] = http_build_query( $tmp, 'var_', '&' );
			}
			if( isset( $parts['fragment'] ) )
				$parts['fragment'] = rawurlencode( $parts['fragment'] );
		}

		$url = array();
		if( !empty( $parts['scheme'] ) ){
			$url[] = $parts['scheme'];
			$url[] = ':';
		}
		if( isset( $parts['host'] ) ){
			$url[] = '//';
			if( isset( $parts['user'] ) ){
				$url[] = $parts['user'];
				if( isset( $parts['pass'] ) ){
					$url[] = ':';
					$url[] = $parts['pass'];
				}
				$url[] = '@';
			}
			if( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) ){
				$url[] = '[';
				$url[] = $parts['host'];
				$url[] = ']'; // IPv6
			}
			else
				$url[] = $parts['host'];             // IPv4 or name
			if( isset( $parts['port'] ) ){
				$url[] = ':';
				$url[] = $parts['port'];
			}
			if( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
				$url[] = '/';
		}
		if( !empty( $parts['path'] ) )
			$url[] = $parts['path'];
		if( isset( $parts['query'] ) ){
			$url[] = '?';
			$url[] = $parts['query'];
		}
		if( isset( $parts['fragment'] ) ){
			$url[] = '#';
			$url[] = $parts['fragment'];
		}

		return implode( '', $url );
	}

}
