<?php

namespace REJ\XacmlBundle\v3_0\Functions;

use REJ\XacmlBundle\AbstractFunctionLibrary;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\v2_0\Functions\FunctionLibrary as FL2;

class FunctionLibrary extends FL2{
	protected $version = '3.0';

	public function __construct( array $map = array(), array $unmap = array(), array $replace = array(), array $equalityPredicates = array() ){
		$map = array_merge( array(
			'urn:oasis:names:tc:xacml:3.0:function:access-permitted'
				=> array(
					'func' => array( $this, 'accessPermitted' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						1 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-equal'
				=> array(
					'func' => array( $this, 'dayTimeDurationEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-equal'
				=> array(
					'func' => array( $this, 'yearMonthDurationEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-equal-ignore-case'
				=> array(
					'func' => array( $this, 'stringEqualIgnoreCase' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::DAYTIMEDURATION,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::YEARMONTHDURATION,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
						),
						1 => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
						),
						1 => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::DAYTIMEDURATION,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::YEARMONTHDURATION,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::DAYTIMEDURATION,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::YEARMONTHDURATION,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::DAYTIMEDURATION,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::YEARMONTHDURATION,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DAYTIMEDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::YEARMONTHDURATION,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dateTime-add-dayTimeDuration'
					=> array(
					'func' => array( $this, 'dateTimeAddInterval' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATETIME
						),
						1 => array(
							'type' => Attribute::DAYTIMEDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dateTime-add-yearMonthDuration'
					=> array(
					'func' => array( $this, 'dateTimeAddInterval' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATETIME
						),
						1 => array(
							'type' => Attribute::YEARMONTHDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dateTime-subtract-dayTimeDuration'
					=> array(
					'func' => array( $this, 'dateTimeSubtractInterval' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATETIME
						),
						1 => array(
							'type' => Attribute::DAYTIMEDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dateTime-subtract-yearMonthDuration'
					=> array(
					'func' => array( $this, 'dateTimeSubtractInterval' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATETIME
						),
						1 => array(
							'type' => Attribute::YEARMONTHDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:date-add-yearMonthDuration'
					=> array(
					'func' => array( $this, 'dateAddInterval' ),
					'type'	=> Attribute::DATE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATE
						),
						1 => array(
							'type' => Attribute::YEARMONTHDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:date-subtract-yearMonthDuration'
					=> array(
					'func' => array( $this, 'dateSubtractInterval' ),
					'type'	=> Attribute::DATE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATE
						),
						1 => array(
							'type' => Attribute::YEARMONTHDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:any-of'
					=> array(
					'func' => array( $this, 'anyOf' ),
					'minArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						'general' => array(
							'acceptBag' => true,
							'acceptEmpty' => true
						),
						'passAsAttributes' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:all-of'
					=> array(
					'func' => array( $this, 'allOf' ),
					'minArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						'general' => array(
							'acceptBag' => true,
							'acceptEmpty' => true
						),
						'passAsAttributes' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:any-of-any'
					=> array(
					'func' => array( $this, 'anyOfAny' ),
					'minArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						'general' => array(
							'acceptBag' => true,
							'acceptEmpty' => true
						),
						'passAsAttributes' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:map'
					=> array(
					'func' => array( $this, 'map' ),
					'minArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						'general' => array(
							'acceptBag' => true,
							'acceptEmpty' => true
						),
						'passAsAttributes' => true
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-starts-with'
				=> array(
					'func' => array( $this, 'stringStartsWith' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-ends-with'
				=> array(
					'func' => array( $this, 'stringEndsWith' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-contains'
				=> array(
					'func' => array( $this, 'stringContains' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-substring'
				=> array(
					'func' => array( $this, 'stringSubstring' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 3,
					'maxArgs' => 3,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::INTEGER
						),
						2 => array(
							'type' => Attribute::INTEGER
						),
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:anyURI-starts-with'
				=> array(
					'func' => array( $this, 'anyUriStartsWith' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::ANYURI
						),
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:anyURI-ends-with'
				=> array(
					'func' => array( $this, 'anyUriEndsWith' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::ANYURI
						),
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:anyURI-contains'
				=> array(
					'func' => array( $this, 'anyUriContains' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						),
						1 => array(
							'type' => Attribute::ANYURI
						),
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:anyURI-substring'
				=> array(
					'func' => array( $this, 'stringSubstring' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 3,
					'maxArgs' => 3,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						1 => array(
							'type' => Attribute::INTEGER
						),
						2 => array(
							'type' => Attribute::INTEGER
						),
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-boolean'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::BOOLEAN
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-integer'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::INTEGER
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-double'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DOUBLE
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-time'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::TIME
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-date'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATE
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-dateTime'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DATETIME
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-anyURI'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-dayTimeDuration'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DAYTIMEDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-yearMonthDuration'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::YEARMONTHDURATION
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-x500Name'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::X500NAME
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-rfc822Name'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::RFC822NAME
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-ipAddress'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::IPADDRESS
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:string-from-dnsName'
				=> array(
					'func' => array( $this, 'stringFromType' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DNSNAME
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:boolean-from-string'
				=> array(
					'func' => array( $this, 'booleanFromString' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:integer-from-string'
				=> array(
					'func' => array( $this, 'integerFromString' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:double-from-string'
				=> array(
					'func' => array( $this, 'doubleFromString' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:time-from-string'
				=> array(
					'func' => array( $this, 'timeFromString' ),
					'type'	=> Attribute::TIME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:date-from-string'
				=> array(
					'func' => array( $this, 'dateFromString' ),
					'type'	=> Attribute::DATE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dateTime-from-string'
				=> array(
					'func' => array( $this, 'dateTimeFromString' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:anyURI-from-string'
				=> array(
					'func' => array( $this, 'anyUriFromString' ),
					'type'	=> Attribute::ANYURI,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dayTimeDuration-from-string'
				=> array(
					'func' => array( $this, 'dayTimeDurationFromString' ),
					'type'	=> Attribute::DAYTIMEDURATION,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:yearMonthDuration-from-string'
				=> array(
					'func' => array( $this, 'yearMonthDurationFromString' ),
					'type'	=> Attribute::YEARMONTHDURATION,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:x500Name-from-string'
				=> array(
					'func' => array( $this, 'x500NameFromString' ),
					'type'	=> Attribute::X500NAME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:rfc822Name-from-string'
				=> array(
					'func' => array( $this, 'rfc822NameFromString' ),
					'type'	=> Attribute::RFC822NAME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:ipAddress-from-string'
				=> array(
					'func' => array( $this, 'ipAddressFromString' ),
					'type'	=> Attribute::IPADDRESS,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:3.0:function:dnsName-from-string'
				=> array(
					'func' => array( $this, 'dnsNameFromString' ),
					'type'	=> Attribute::DNSNAME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING
						)
					)
				),
		), $map );

		$equalityPredicates = array_merge(
			array(
					Attribute::DAYTIMEDURATION => array( $this, 'dayTimeDurationEqual' ),
					Attribute::YEARMONTHDURATION => array( $this, 'yearMonthDurationEqual' )
			),
			$equalityPredicates
		);

		parent::__construct( $map, $unmap, $replace, $equalityPredicates );
	}

	/* equality predicates */
	protected function stringEqualIgnoreCase( $attr1, $attr2 ){
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$attr1 = mb_strtolower( $attr1 );
		$attr2 = mb_strtolower( $attr2 );
		$tmp = ( $attr1 === $attr2 )? 'true': 'false';
		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dayTimeDurationEqual( $attr1, $attr2 ){
		$attr1 = new \DateInterval( reset( $attr1 ) );
		$attr2 = new \DateInterval( reset( $attr2 ) );
		$return = ( ( $attr1 == $attr2 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function yearMonthDurationEqual( $attr1, $attr2 ){
		$attr1 = new \DateInterval( reset( $attr1 ) );
		$attr2 = new \DateInterval( reset( $attr2 ) );
		$return = ( ( $attr1 == $attr2 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	/* date and time arithmetic functions */
	protected function dateTimeAddInterval( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateInterval( reset( $attr2 ) );

		$attr1 = $attr1->add( $attr2 );
		$return = $attr1->format( DateTime::RFC3339 );
		return new Attribute( '', Attribute::DATETIME, $return );
	}

	protected function dateTimeSubtractInterval( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateInterval( reset( $attr2 ) );

		$attr1 = $attr1->sub( $attr2 );
		$return = $attr1->format( DateTime::RFC3339 );
		return new Attribute( '', Attribute::DATETIME, $return );
	}

	protected function dateAddInterval( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateInterval( reset( $attr2 ) );

		$attr1 = $attr1->add( $attr2 );
		$return = $attr1->format( 'Y-m-d' );
		return new Attribute( '', Attribute::DATETIME, $return );
	}

	protected function dateSubtractInterval( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateInterval( reset( $attr2 ) );

		$attr1 = $attr1->sub( $attr2 );
		$return = $attr1->format( 'Y-m-d' );
		return new Attribute( '', Attribute::DATETIME, $return );
	}

	/* string functions */
	protected function stringStartsWith( $attr1, $attr2 ){
		$return = 'false';
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$len = strlen( $attr1 );
		$return = ( ( substr( $attr2, 0, $len ) === $attr1 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function stringEndsWith( $attr1, $attr2 ){
		$return = 'false';
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$return = ( ( substr( $attr2, -strlen( $attr1 ) ) === $attr1 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function stringContains( $attr1, $attr2 ){
		$return = 'false';
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$return = ( ( strpos( $attr2, $attr1 ) !== false )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function stringSubstring( $attr1, $attr2, $attr3 ){
		$attr1 = (string)reset( $attr1 );
		$attr2 = abs( (int)reset( $attr2 ) );
		$attr3 = (int)reset( $attr3 );
		if( $attr3 == -1 )
			$attr3 = NULL;
		else
			$attr3 = abs( $attr3 );

		$return = mb_substr( $attr2, $attr2, $attr3 );

		if( $return === false || $return === '' )
			throw new ProcessingErrorException( 'unable to get substring from arguments.' );

		return new Attribute( '', Attribute::STRING, $return );
	}

	protected function anyUriStartsWith( $attr1, $attr2 ){
		$attr2 = $this->stringFromAnyUri( $attr2 );
		$attr2 = $attr2->getValue();
		return $this->stringStartsWith( $attr1, $attr2 );
	}

	protected function anyUriEndsWith( $attr1, $attr2 ){
		$attr2 = $this->stringFromAnyUri( $attr2 );
		$attr2 = $attr2->getValue();
		return $this->stringEndsWith( $attr1, $attr2 );
	}

	protected function anyUriContains( $attr1, $attr2 ){
		$attr2 = $this->stringFromAnyUri( $attr2 );
		$attr2 = $attr2->getValue();
		return $this->stringContains( $attr1, $attr2 );
	}

	protected function anyUriSubstring( $attr1, $attr2, $attr3 ){
		$attr1 = $this->stringFromAnyUri( $attr1 );
		$attr1 = $attr1->getValue();
		$return = $this->stringSubstring( $attr1, $attr2, $attr3 );
		trigger_error( 'uri substring not checked as sytactically correct, please implement this later', E_USER_WARNING );
		return $return;
	}

	protected function stringFromType( $attr1 ){
		$attr1 = '' . reset( $attr1 );
		return new Attribute( '', Attribute::STRING, $attr1 );
	}

	protected function booleanFromString( $attr1 ){
		$attr1 = (string)reset( $attr1 );
		if( $attr1 !== 'true' && $attr1 !== 'false' )
			return new IndeterminateResultException( 'boolean to string converstion fails' );
		return new Attribute( '', Attribute::BOOLEAN, $attr1 );
	}

	protected function integerFromString( $attr1 ){
		$attr1 = (int)reset( $attr1 );
		return new Attribute( '', Attribute::INTEGER, $attr1 );
	}

	protected function doubleFromString( $attr1 ){
		$attr1 = (double)reset( $attr1 );
		return new Attribute( '', Attribute::DOUBLE, $attr1 );
	}

	protected function timeFromString( $attr1 ){
		$attr1 = reset( $attr1 );
		$attr1 = new \DateTime( $attr1 );
		$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr1 = $attr1->format( 'H:i:sP' );
		return new Attribute( '', Attribute::TIME, $attr1 );
	}

	protected function dateFromString( $attr1 ){
		$attr1 = reset( $attr1 );
		$attr1 = new \DateTime( $attr1 );
		$attr1->setTime( 0, 0, 0 );
		$attr1 = $attr1->format( 'Y-m-dP' );
		return new Attribute( '', Attribute::DATE, $attr1 );
	}

	protected function dateTimeFromString( $attr1 ){
		$attr1 = reset( $attr1 );
		$attr1 = new \DateTime( $attr1 );
		$attr1 = $attr1->format( \DateTime::RFC3339 );
		return new Attribute( '', Attribute::DATETIME, $attr1 );
	}

	protected function anyUriFromString( $attr1 ){
		trigger_error( 'no real validation here. please implmenet' );
		return new Attribute( '', Attribute::ANYURI, $attr1 );
	}

	protected function dayTimeDurationFromString( $attr1 ){
		$attr1 = reset( $attr1 );

		try{
			$tmp = new \DateInterval( $tmp );
		}catch( \Exception $e ){
			throw new SyntaxErrorException( 'invalid daytimeduration from string conversion', 0, $e );
		}


		return new Attribute( '', Attribute::DAYTIMEDURATION, $attr1 );
	}

	protected function yearMonthDurationFromString( $attr1 ){
		$attr1 = reset( $attr1 );

		try{
			$tmp = new \DateInterval( $tmp );
		}catch( \Exception $e ){
			throw new SyntaxErrorException( 'invalid yearmonthduration from string conversion', 0, $e );
		}


		return new Attribute( '', Attribute::YEARMONTHDURATION, $attr1 );
	}

	protected function x500NameFromString( $attr1 ){
		throw new ProcessingErrorException( 'somebody who understands x500Names should implement this.' );
	}

	protected function rfc822NameFromString( $attr1 ){
		$attr1 = explode( '@', (string)reset( $attr1 ) );
		if( count( $attr1 ) != 2 )
			throw new SyntaxErrorException( 'improperly formatted string converted to rfc822Name' );

		$attr1[ 0 ] = mb_strtolower( $attr1[ 1 ] );

		$attr1 = implode( '@', $attr1 );

		return new Attribute( '', Attribute::RFC822NAME, $attr1 );
	}

	protected function ipAddressFromString( $attr1 ){
		$attr1 = reset( $attr1 );
		if( filter_var( $attr1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) === false )
			throw new SyntaxErrorException( 'invliad string to ip address conversion' );
		return new Attribute( '', Attribute::IPADDRESS, $attr1 );
	}

	protected function dnsNameFromString( $attr1 ){
		throw new ProcessingErrorException( 'somebody who understands DnsNames should implement this.' );
	}

	/* higher order bag functions */
	protected function anyOf( $op, ...$args ){
		$op = $op->getValue();
		$op = reset( $op );
		$arguments = array();
		$val = new Attribute( '' );
		$bag = NULL;

		foreach( $args as $arg ){
			if( count( $arg->getValue() ) < 2 )
				$arguments[] = $arg;
			else if( $bag !== NULL )
				throw new ProcessingErrorException( 'anyOf takes only one bag as an argument' );
			else{
				$arguments[] = $val;
				$val->setType( $arg->getType() );
				$bag = $arg->getValue();
			}
		}

		if( $bag === NULL ){
			$bag = array_pop( $arguments );
			$arguments[] = $val;
			$val->setType( $bag->getType() );
			$bag = $bag->getValue();
		}
			
		$return = 'false';

		foreach( $bag as $v ){
				$val->setValue( $v );
				$res = $this->callFunction( $op, count( $arguments ), ...$arguments );
				if( $res->getType() !== Attribute::BOOLEAN )
					throw new ProcessingErrorException( 'anyOf function must return Boolean' );

				$res = $res->getValue();
				if( reset( $res ) === 'true' ){
					$return = 'true';
					break;
			}
		}

		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function allOf( $op, ...$args ){
		$op = $op->getValue();
		$op = reset( $op );
		$arguments = array();
		$val = new Attribute( '' );
		$bag = NULL;

		foreach( $args as $arg ){
			if( count( $arg->getValue() ) < 2 )
				$arguments[] = $arg;
			else if( $bag !== NULL )
				throw new ProcessingErrorException( 'anyOf takes only one bag as an argument' );
			else{
				$arguments[] = $val;
				$val->setType( $arg->getType() );
				$bag = $arg->getValue();
			}
		}

		if( $bag === NULL ){
			$bag = array_pop( $arguments );
			$arguments[] = $val;
			$val->setType( $bag->getType() );
			$bag = $bag->getValue();
		}
			
		$return = 'true';

		foreach( $bag as $val ){
				$val->setValue( $val );
				$res = $this->callFunction( $op, count( $arguments), ...$arguments );
				if( $res->getType() !== Attribute::BOOLEAN )
					throw new ProcessingErrorException( 'anyOf function must return Boolean' );

				$res = $res->getValue();
				if( reset( $res ) === 'false' ){
					$return = 'false';
					break;
			}
		}

		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function anyOfAny( $op, ...$args ){
		$op = $op->getValue();
		$op = reset( $op );
		$return = 'false';
		$tmp = array( array() );

		foreach( $args as $key => $values ){
			$valarray = $values->getValues();
			$append = array();
			foreach( $tmp as $product ){
				foreach( $valarray as $item ){
					$product[ $key ] = new Attribute( '', $values->getType(), $item );
					$append[] = $product;
				}
			}
			$tmp = $append;
		}

		foreach( $tmp as $args ){
				$res = $this->callFunction( $op, count( $args), ...$args );

				if( $res->getType() !== Attribute::BOOLEAN )
					throw new ProcessingErrorException( 'anyOf function must return Boolean' );

				$res = $res->getValue();
				if( reset( $res ) === 'true' ){
					$return = 'true';
					break;
			}
		}

		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function map( $op, ...$args ){
		$op = $op->getValue();
		$op = reset( $op );
		$arguments = array();
		$val = new Attribute( '' );
		$return = array();
		$returnType = NULL;
		$bag = NULL;

		foreach( $args as $arg ){
			if( count( $arg->getValue() ) < 2 )
				$arguments[] = $arg;
			else if( $bag !== NULL )
				throw new ProcessingErrorException( 'anyOf takes only one bag as an argument' );
			else{
				$arguments[] = $val;
				$val->setType( $arg->getType() );
				$bag = $arg->getValue();
			}
		}

		if( $bag === NULL ){
			$bag = array_pop( $arguments );
			$arguments[] = $val;
			$val->setType( $bag->getType() );
			$bag = $bag->getValue();
		}
			
		foreach( $bag as $val ){
				$val->setValue( $val );
				$res = $this->callFunction( $op, count( $arguments ), ...$arguments );
				if( $returnType === NULL )
					$returnType = $res->getType();
				else if( $res->getType() !== $returnType )
					throw new ProcessingErrorException( 'inconsistent return types from mapped function' );
				$return = array_merge( $return, $res->getValue() );
		}

		return new Attribute( '', $returnType, $return );
	}

	/* other functions */
	protected function accessPermitted( $attr1, $attr2 ){
		throw new ProcessingErrorException( 'accessPermitted is not implemented' );
	}

}