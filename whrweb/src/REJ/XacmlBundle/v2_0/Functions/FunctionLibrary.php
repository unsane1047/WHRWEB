<?php

namespace REJ\XacmlBundle\v2_0\Functions;

use REJ\XacmlBundle\AbstractFunctionLibrary;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\v1_0\Functions\FunctionLibrary as FL1;

class FunctionLibrary extends FL1{
	protected $version = '2.0';

	public function __construct( array $map = array(), array $unmap = array(), array $replace = array(), array $equalityPredicates = array() ){
		$map = array_merge( array(
		'urn:oasis:names:tc:xacml:2.0:function:ipAddress-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::IPADDRESS,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::IPADDRESS,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:dnsName-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::DNSNAME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DNSNAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:ipAddress-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::IPADDRESS,
							'acceptBag' => true
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:dnsName-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DNSNAME,
							'acceptBag' => true
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:ipAddress-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::IPADDRESS,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::IPADDRESS,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:dnsName-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::DNSNAME,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DNSNAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:time-in-range'
				=> array(
					'func' => array( $this, 'timeInRange' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 3,
					'maxArgs' => 3,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:string-concatenate'
				=> array(
					'func' => array( $this, 'stringConcatenate' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:anyURI-regexp-match'
				=> array(
					'func' => array( $this, 'stringRegexpMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::ANYURI
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:ipAddress-regexp-match'
				=> array(
					'func' => array( $this, 'stringRegexpMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::IPADDRESS
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:dnsName-regexp-match'
				=> array(
					'func' => array( $this, 'stringRegexpMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::DNSNAME
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:rfc822Name-regexp-match'
				=> array(
					'func' => array( $this, 'stringRegexpMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::RFC822NAME
						)
					)
				),
		'urn:oasis:names:tc:xacml:2.0:function:x500Name-regexp-match'
				=> array(
					'func' => array( $this, 'stringRegexpMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::X500NAME
						)
					)
				),
		), $map );

		parent::__construct( $map, $unmap, $replace, $equalityPredicates );
	}

	protected function timeInRange( $attr1, $attr2, $attr3 ){
		if( strlen( $attr1 ) <= 8 )
			$attr1 .= 'Z';
		$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr1 ) ) );

		$tz = $attr1->getTimeZone();
		if( $tz === false )
			$tz = new \DateTimeZone( 'UTC' );

		if( strlen( $attr2 ) <= 8 )
			$attr2 .= $tz->getOffset();

		$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr2 ) ) );

		if( strlen( $attr3 ) <= 8 )
			$attr3 .= $tz->getOffset();

		$attr3 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr3 ) ) );

		$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr3->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
	
		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		$attr3->setTimezone( $utc );

		if( $attr3->getTimestamp() < $attr2->getTimestamp() )
			$attr3 = $attr2;

		$return = 'false';

		if( $attr2->getTimestamp() <= $attr1->getTimestamp()
			&& $attr1->getTimestamp() <= $attr3->getTimestamp() )
			$return = 'true';

		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function stringConcatenate( ...$args ){
		$args = array_map( 'reset', $args );
		$return = implode( '', $args );
		return new Attribute( '', Attribute::STRING, $return );
	}

}