<?php
// src/AppBundle/Libraries/UnicodeString.php

#######
# String object for making working with Unicode Strings easier in PHP
#######

namespace AppBundle\Libraries;

use AppBundle\Libraries\PhoneticMatch;

class UnicodeString{
	const byteOrderMark = '/^\x{FEFF}/u';
	const whitespaceChars = '/\pZ\pC\s/u';
	const wordSeperators = '/[ \x{00A0}\x{1361}\x{1680}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u';
	const wordSeperators3 = '[ \x{00A0}\x{1361}\x{1680}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]';
	const wordSeperators2 = '/[\x{00A0}\x{1361}\x{1680}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u';
	const tabChars = '\x{0009}\x{000B}';
	const newlineChars = '\r?\n?\x{000A}|\r?\n|\r|\n|\f|\x{0085}|\x{2028}|\x{2029}';
	const controlChars = '\x{0000}-\x{0008}\x{000E}-\x{0084}\x{0086}-\x{009F}\p{Cf}\p{Co}\p{Cn}';
	const xmlValidUTF8 = '[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]';
	const UTF8SUBCHAR = "\xEF\XBF\xBD";
	const UTF8SUBCHARINT = 65533;

	protected $string;

	public function set( $string, $encoding = 'UTF-8' ){
		if( $encoding === 'UTF8' )
			$encoding = 'UTF-8';
		$oldSub = mb_substitute_character();
		mb_substitute_character( self::UTF8SUBCHARINT );
		$this->string = mb_convert_encoding( $string, 'UTF-8', $encoding );
		mb_substitute_character( $oldSub );
		$this->string = $this->normalize( $this->string );
		return $this;
	}

	public function __construct( $string = '', $encoding = 'UTF-8' ){
		$this->set( $string, $encoding );
	}

	public function __toString(){
		return $this->encode();
	}

	public function encode( $encoding = 'UTF-8', $subCharacter = 'none', $forXML = false ){
		$ret = '';
		if( $encoding === 'UTF8' || $encoding === 'UTF-8' ){
			$encoding = 'UTF-8';
			$ret = $this->string;
		}
		else{
			$oldSub = mb_substitute_character();
			mb_substitute_character( $subCharacter );
			$ret = mb_convert_encoding( $string, $encoding, 'UTF-8' );
			mb_substitute_character( $oldSub );
		}

		if( $encoding === 'UTF-8' && $forXML )
			$ret = preg_replace( '/' . self::xmlValidUTF8 . '/u', self::UTF8SUBCHAR, $string );
		return $ret;
	}

	protected function normalize( $string ){
		if( class_exists( '\Normalizer' ) )
			$string = \Normalizer::normalize( $string, \Normalizer::FORM_C );
		else
			trigger_error( 'Unable to normalize input. Please install intl PCEL extension.', E_USER_WARNING );

		return $string;
	}

	public function entropy(){
		$h = 0;
		$size = mb_strlen( $this->string, 'UTF-8' );
		foreach( self::mb_count_chars( $this->string, 1, 'UTF-8' ) as $v ){
			$p = $v / $size;
			$h -= $p * log( $p ) / log( 2 );
		}
		return $h;
	}

	public function removeBOM(){
		$this->string = preg_replace( self::byteOrderMark, '', $this->string );
		return $this;
	}

	public function areEqual( UnicodeString ...$candidates ){
		foreach( $candidates as $str ){
			if( $this->encode( 'UTF8', self::UTF8SUBCHARINT) === $str->encode( 'UTF8', self::UTF8SUBCHARINT ) )
				return true;
		}
		return false;
	}

	public function areFuzzyEqual( $type = '', UnicodeString ...$candidates ){
		$match = new PhoneticMatch();

		$string = $this->__toString();
		$compareTo = array();
		foreach( $candidates as $c )
			$compareTo[] = $match->compute( $c->__toString(), $type );

		return $match->match( $string, $compareTo );
	}

	public function getFuzzyMatchKeys( $type = '' ){
		$match = new PhoneticMatch();
		return $match->compute( $this->__toString(), $type );
	}

	public function atBeginning( $needle ){
		return self::startsWith( $this->string, $needle );
	}

	public function atEnd( $needle ){
		return self::endsWith( $this->string, $needle );
	}

	public function trim( $trimchars = '' ){
		$this->string = preg_replace( '/^[\pZ\pC' . $trimchars . ']+|[\pZ\pC' . $trimchars . ']+$/u', '', $this->string );
		return $this;
	}

	public function replaceWordSeperators( $spaceChar = ' ' ){
		if( $spaceChar === ' ' )
			$this->string = preg_replace( self::wordSeperators2, $spaceChar, $this->string );
		else
			$this->string = preg_replace( self::wordSeperators, $spaceChar, $this->string );
		return $this;
	}

	public function replaceNewlineChars( $replacement = "\n" ){
		$this->string = preg_replace( '/' . self::newlineChars . '/u', $replacement, $this->string );
		return $this;
	}

	 #I assume this works because inserting any of the control characters
	 #actually causes this function to die with invalid utf8
	public function replaceControlChars( $replacement = '' ){
		$this->string = preg_replace( '/' . self::controlChars . '/u', $replacement, $this->string);
		return $this;
	}

	public function replaceTabChars( $replacement = '	' ){
		$this->string = preg_replace( '/' . self::tabChars . '/u', $replacement, $this->string );
		return $this;
	}

	public function asSingleLineInputSpacesOnly( $spaceChar = ' ' ){
		$this->replaceNewlineChars( $spaceChar )
			->replaceTabChars( $spaceChar )
			->replaceControlChars( $spaceChar )
			->replaceWordSeperators( $spaceChar );
		return $this;
	}

	#take char and return unicode code point
	public static function uniord($c) {
		if (ord($c{0}) >=0 && ord($c{0}) <= 127)
			return ord($c{0});
		if (ord($c{0}) >= 192 && ord($c{0}) <= 223)
			return (ord($c{0})-192)*64 + (ord($c{1})-128);
		if (ord($c{0}) >= 224 && ord($c{0}) <= 239)
			return (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
		if (ord($c{0}) >= 240 && ord($c{0}) <= 247)
			return (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
		if (ord($c{0}) >= 248 && ord($c{0}) <= 251)
			return (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
		if (ord($c{0}) >= 252 && ord($c{0}) <= 253)
			return (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
		if (ord($c{0}) >= 254 && ord($c{0}) <= 255)    //  error
			return false;
		return 0;
	}

	# sort of like count_chars for mb, no mode 0, 2, or 4 because the just don't work with mb charsets
	public static function mb_count_chars( $input = '', $mode = 1, $encoding = 'UTF-8' ) {
		$mode = (int)$mode;
		if( $mode == 2 || $mode == 4 || $mode > 4 || $mode <= 0 )
			throw new \Exception( 'Unsupported mb_count_chars mode.' );

		$l = mb_strlen( $input, $encoding );

		$unique = array();

		for( $i = 0; $i < $l; $i++ ){
			$char = mb_substr( $input, $i, 1, $encoding );
			if( !array_key_exists( $char, $unique ) )
				$unique[ $char ] = 0;
			$unique[ $char ]++;
		}

		if( $mode == 3 ){
			foreach( $unique as $c => $f ){
				if( $f > 1 )
					unset( $unique[ $c ] );
			}
			$unique = implode( '', $unique );
		}
		
		return $unique;
	}

	# change the encoding specified in standard terms to a name useable by mysql
	public static function mysqlEncodingName( $encoding = 'UTF-8' ){
		$set_translation = array(
				'Big5' => 'big5',
				'DEC8' => 'dec8',
				'cp850' => 'cp850',
				'hp8' => 'hp8',
				'KOI8-R' => 'koi8r',
				'cp1252' => 'latin1',
				'iso-8859-1' => 'latin2',
				'swe7' => 'swe7',
				'ASCII' => 'ascii',
				'EUC-JP' => 'ujis',
				'Shift-JIS' => 'sjis',
				'iso-8859-8' => 'hebrew',
				'TIS620' => 'tis620',
				'EUC-KR' => 'euckr',
				'KOI8-U' => 'koi8u',
				'GB2312' => 'gb2312',
				'iso-8859-7' => 'greek',
				'cp1250' => 'cp1250',
				'GBK' => 'gbk',
				'iso-5589-9' => 'latin5',
				'ARMSCII-8' => 'armscii8',
				'UTF-8' => 'utf8mb4',
				'UCS-2' => 'ucs2',
				'cp866' => 'cp866',
				'keybcs2' => 'keybcs2',
				'macce' => 'macce',
				'macroman' => 'macroman',
				'cp852' => 'cp852',
				'iso-8859-13' => 'latin7',
				'UTF-8mb4' => 'utf8mb4',
				'cp1251' => 'cp1251',
				'UTF-16' => 'utf16',
				'cp1256' => 'cp1256',
				'cp1257' => 'cp1257',
				'UTF-32' => 'utf32',
				'binary' => 'binary',
				'GEOSTD8' => 'geostd8',
				'cp932' => 'cp932',
				'UJIS' => 'eucjpms'
		);

		if( isset( $set_translation[ $encoding ] ) )
			$to_encoding = $set_translation[ $encoding ];
		else
			$to_encoding = $set_translation[ 'UTF-8' ];

		return $to_encoding;
	}

	public static function multi_implode( $glue = '', $array ){
		$ret = array();
		foreach( $array as $item ){
			if( is_array( $item ) )
				$ret[] = self::multi_implode( $glue, $item );
			else
				$ret[] = $item;
			$ret[] = $glue;
		}
		array_pop( $ret );
		return new static( implode( '', $ret ) );
	}

   /**
     * Generates a random string of given $length. DO NOT CONSIDER THIS CRYPTOGRAPHICALLY SECURE
     *
     * @param Integer $length The string length.
     * @return String The randomly generated string.
     */
	public static function randomString( $length ){
		$seed = '`A~B!C1D@E2F#G3H$I4J%K5L^M6N&O7P*Q8R(S9T)U0V_W-X+Y=Z{a[b}c]d|e:f;g"h\'i<j,q>l.m?n\\o/pqrtsuvwxyz';
		$max = strlen( $seed ) - 1;

		$string = '';
		for ( $i = 0; $i < $length; ++$i )
			$string .= $seed{intval( mt_rand( 0.0, $max ) )};

		return new static( $string );
	}

	public static function startsWith( $haystack, $needle ){
		if( !is_array( $needle ) ){
			$length = strlen( $needle );
			return ( substr( $haystack, 0, $length ) === $needle );
		}
		else{
			$return = false;
			foreach( $needle as $tmp ){
				if( self::startsWith( $haystack, $tmp ) ){
					$return = true;
					break;
				}
			}
			return $return;
		}
	}

	public static function endsWith( $haystack, $needle ){
		if( !is_array( $needle ) )
			return ( substr( $haystack, -strlen( $needle ) ) === $needle );
		else{
			$return = false;
			foreach( $needle as $n ){
				if( self::endsWith( $haystack, $n ) ){
					$return = true;
					break;
				}
			}
		}
		return $return;
	}

}