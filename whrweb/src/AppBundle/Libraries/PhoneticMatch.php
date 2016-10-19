<?php
// src/AppBundle/Libraries/Phoneticmatch.php

######
# Phonetic Matching Library
#
# Library of functions for using fuzzy phonetic matching on strings
#
#####

namespace AppBundle\Libraries;

use AppBundle\Libraries\fuzzymatchstrategies\metaphone3,
	AppBundle\Libraries\fuzzymatchstrategies\doublemetaphone;
	
class PhoneticMatch{
	private $strategy = NULL;
	private $type = '';

	public function setType( $type = '' ){
		switch( strtolower( $type ) ){
			case 'soundex':
			case 'sx_':
			case 'sx':
				$this->strategy = 'soundex';
				$this->type = 'sx_';
				break;
			case 'metaphone3':
			case 'triplemetaphone':
			case 'm3_':
			case 'm3':
				$this->strategy = new metaphone3();
				$this->strategy->encodeVowels = true;
				$this->strategy->encodeExact = true;
				$this->type = 'm3_';
				break;
			case 'metaphone2':
			case 'doublemetaphone':
			case 'm2_':
			case 'm2':
				$this->strategy = new doublemetaphone();
				$this->type = 'm2_';
				break;
			case 'metaphone':
			case 'm1_':
			case 'm1':
			default:
				$this->strategy = 'metaphone';
				$this->type = 'm1_';
		}
	}

	public function compute( $string, $type = '' ){
		if( $this->strategy === NULL || $type !== '' )
			$this->setType( $type );

		$strategy = $this->strategy;

		$result = $strategy( $string );

		if( !is_array( $result ) )
			$result = array( $result, '' );

		if( !isset( $result[ 0 ] ) )
			$result[ 0 ] = '';

		if( !isset( $result[ 1 ] ) )
			$result[ 1 ] = '';

		if( $result[ 0 ] !== '' )
			$result[ 0 ] = $this->type . $result[ 0 ];

		if( $result[ 1 ] !== '' )
			$result[ 1 ] = $this->type . $result[ 1 ];

		return array( $result[ 0 ], $result[ 1 ] );
	}

	public function match( $input, $compareTo = array() ){
		if( !is_array( $compareTo ) )
			$compareTo = array( $compareTo );

		$inputKeys = array( '', '' );
		$matches = array();

		foreach( $compareTo as $index => $keys ){

			if( !isset( $keys[ 0 ] ) || $keys[ 0 ] == '' )
				continue;

			$type = substr( $keys[ 0 ], 0, 3 );

			if( $this->type !== $type || $inputKeys[ 0 ] == '' ){
				$this->setType( $type );
				$inputKeys = $this->compute( $input );
			}

			if( $keys[ 0 ] === $inputKeys[ 0 ] ){ #direct match of primary keys
				$matches[ $index ] = 1;
				continue;
			}

			if( $inputKeys[ 1 ] !== '' && $keys[ 0 ] === $inputKeys[ 1 ]
				|| $keys[ 1 ] !== '' && $keys[ 1 ] === $inputKeys[ 0 ]
			){ #primary matches secondary directly
				$matches[ $index ] = 2;
				continue;
			}

			if( strpos( $keys[ 0 ], $inputKeys[ 0 ] ) === 0
				|| strpos( $inputKeys[ 0 ], $keys[ 0 ] ) === 0
			){ #one primary key is a substring of the other
				$matches[ $index ] = 3;
				continue;
			}

			if(
				(
					$inputKeys[ 1 ] !== ''
					&& (
						strpos( $keys[ 0 ], $inputKeys[ 1 ] ) === 0
						|| strpos( $inputKeys[ 1 ], $keys[ 0 ] ) === 0
					)
				)
				|| (
					$keys[ 1 ] !== ''
					&& (
						strpos( $keys[ 1 ], $inputKeys[ 0 ] ) === 0
						|| strpos( $inputKeys[ 0 ], $keys[ 1 ] ) === 0
					)
				)
			){ #secondary is substring of primary
				$matches[ $index ] = 4;
				continue;
			}


			if( $keys[ 1 ] != '' && $inputKeys[ 1 ] != '' ){
				if( $keys[ 1 ] === $inputKeys[ 1 ] ){ #secondary keys match directly
					$matches[ $index ] = 5;
					continue;
				}

				if( strpos( $keys[ 1 ], $inputKeys[ 1 ]  ) === 0
					|| strpos( $inputKeys[ 1 ], $keys[ 1 ]  ) === 0
				){ #secondary keys are substrings of eachother
					$matches[ $index ] = 6;
					continue;
				}
			}

		}

		asort( $matches );

		return $matches;
	}
}