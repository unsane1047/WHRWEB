<?php

namespace REJ\XacmlBundle\v1_0\Functions;

use REJ\XacmlBundle\AbstractFunctionLibrary;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundleExceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;

class FunctionLibrary extends AbstractFunctionLibrary{
	protected $version = '1.0';
	protected $equalityPredicates = array();

	public function __construct( array $map = array(), array $unmap = array(), array $replace = array(), array $equalityPredicates = array() ){
		$map = array_merge( array(
			'urn:oasis:names:tc:xacml:1.0:function:string-equal'
				=> array(
					'func' => array( $this, 'stringEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-equal'
				=> array(
					'func' => array( $this, 'booleanEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-equal'
				=> array(
					'func' => array( $this, 'integerEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-equal'
				=> array(
					'func' => array( $this, 'doubleEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-equal'
				=> array(
					'func' => array( $this, 'dateEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-equal'
				=> array(
					'func' => array( $this, 'timeEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-equal'
				=> array(
					'func' => array( $this, 'dateTimeEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-equal'
				=> array(
					'func' => array( $this, 'anyUriEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-equal'
				=> array(
					'func' => array( $this, 'x500NameEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-equal'
				=> array(
					'func' => array( $this, 'rfc822NameEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-equal'
				=> array(
					'func' => array( $this, 'hexBinaryEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-equal'
				=> array(
					'func' => array( $this, 'base64BinaryEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-add'
				=> array(
					'func' => array( $this, 'integerAdd' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-add'
				=> array(
					'func' => array( $this, 'doubleAdd' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-subtract'
				=> array(
					'func' => array( $this, 'integerSubtract' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-subtract'
				=> array(
					'func' => array( $this, 'doubleSubtract' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-multiply'
				=> array(
					'func' => array( $this, 'integerMultiply' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-multiply'
				=> array(
					'func' => array( $this, 'doubleMultiply' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-divide'
				=> array(
					'func' => array( $this, 'integerDivide' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-divide'
				=> array(
					'func' => array( $this, 'doubleDivide' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-mod'
				=> array(
					'func' => array( $this, 'integerMod' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-abs'
				=> array(
					'func' => array( $this, 'integerAbs' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-abs'
				=> array(
					'func' => array( $this, 'doubleAbs' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:round'
				=> array(
					'func' => array( $this, 'mathRound' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:floor'
				=> array(
					'func' => array( $this, 'mathFloor' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-normalize-space'
				=> array(
					'func' => array( $this, 'stringNormilizeSpace' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-normalize-to-lower-case'
				=> array(
					'func' => array( $this, 'stringNormilizeToLowerCase' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-to-integer'
				=> array(
					'func' => array( $this, 'doubleToInteger' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-to-double'
				=> array(
					'func' => array( $this, 'integerToDouble' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:or'
				=> array(
					'func' => array( $this, 'logicalOr' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:and'
				=> array(
					'func' => array( $this, 'logicalAnd' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:n-of'
				=> array(
					'func' => array( $this, 'logicalNof' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
						),
						0 => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:not'
				=> array(
					'func' => array( $this, 'logicalNot' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-greater-than'
				=> array(
					'func' => array( $this, 'integerGreaterThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-greater-than-or-equal'
				=> array(
					'func' => array( $this, 'integerGreaterThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-less-than'
				=> array(
					'func' => array( $this, 'integerLessThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-less-than-or-equal'
				=> array(
					'func' => array( $this, 'integerLessThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-greater-than'
				=> array(
					'func' => array( $this, 'doubleGreaterThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-greater-than-or-equal'
				=> array(
					'func' => array( $this, 'doubleGreaterThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-less-than'
				=> array(
					'func' => array( $this, 'doubleLessThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-less-than-or-equal'
				=> array(
					'func' => array( $this, 'doubleLessThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-greater-than'
				=> array(
					'func' => array( $this, 'stringGreaterThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-greater-than-or-equal'
				=> array(
					'func' => array( $this, 'stringGreaterThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-less-than'
				=> array(
					'func' => array( $this, 'stringLessThanl' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-less-than-or-equal'
				=> array(
					'func' => array( $this, 'stringLessThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-greater-than'
				=> array(
					'func' => array( $this, 'timeGreaterThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-greater-than-or-equal'
				=> array(
					'func' => array( $this, 'timeGreaterThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-less-than'
				=> array(
					'func' => array( $this, 'timeLessThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-less-than-or-equal'
				=> array(
					'func' => array( $this, 'timeLessThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-greater-than'
				=> array(
					'func' => array( $this, 'dateTimeGreaterThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-greater-than-or-equal'
				=> array(
					'func' => array( $this, 'dateTimeGreaterThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-less-than'
				=> array(
					'func' => array( $this, 'dateTimeLessThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-less-than-or-equal'
				=> array(
					'func' => array( $this, 'dateTimeLessThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-greater-than'
				=> array(
					'func' => array( $this, 'dateGreaterThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-greater-than-or-equal'
				=> array(
					'func' => array( $this, 'dateGreaterThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-less-than'
				=> array(
					'func' => array( $this, 'dateLessThan' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-less-than-or-equal'
				=> array(
					'func' => array( $this, 'dateLessThanOrEqual' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
						),
						1 => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
						),
						1 => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
						),
						1 => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
						),
						1 => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::TIME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
						),
						1 => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::TIME,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::DATE,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
						),
						1 => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::DATE,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
						),
						1 => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::ANYURI,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
						),
						1 => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::ANYURI,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::HEXBINARY,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
						),
						1 => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::HEXBINARY,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::BASE64BINARY,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
						),
						1 => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::BASE64BINARY,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::X500NAME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME
						),
						1 => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::X500NAME,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-one-and-only'
				=> array(
					'func' => array( $this, 'oneAndOnly' ),
					'type'	=> Attribute::RFC822NAME,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-bag-size'
				=> array(
					'func' => array( $this, 'bagSize' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 1,
					'maxArgs' => 1,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-is-in'
				=> array(
					'func' => array( $this, 'isIn' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
						),
						1 => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-bag'
				=> array(
					'func' => array( $this, 'bag' ),
					'type'	=> Attribute::RFC822NAME,
					'minArgs' => 0,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:all-of-any'
				=> array(
					'func' => array( $this, 'allOfAny' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 3,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						1 => array(
							'acceptBag' => true
						),
						2 => array(
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:any-of-all'
				=> array(
					'func' => array( $this, 'anyOfAll' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 3,
					'maxArgs' => 3,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						1 => array(
							'acceptBag' => true
						),
						2 => array(
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:all-of-all'
				=> array(
					'func' => array( $this, 'allOfAll' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 3,
					'maxArgs' => 3,
					'argSpec' => array(
						0 => array(
							'type' => Attribute::ANYURI
						),
						1 => array(
							'acceptBag' => true
						),
						2 => array(
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-regexp-match'
				=> array(
					'func' => array( $this, 'stringRegexpMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-match'
				=> array(
					'func' => array( $this, 'x500NameMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-match'
				=> array(
					'func' => array( $this, 'rfc822NameMatch' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME
						)
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::TIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::DATE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::ANYURI,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::HEXBINARY,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::BASE64BINARY,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::X500NAME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-intersection'
				=> array(
					'func' => array( $this, 'intersection' ),
					'type'	=> Attribute::RFC822NAME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-at-least-one-member-of'
				=> array(
					'func' => array( $this, 'atLeastOneMemberOf' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::STRING,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::INTEGER,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::DOUBLE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::TIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::DATE,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::DATETIME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::ANYURI,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::HEXBINARY,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::BASE64BINARY,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-union'
				=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::X500NAME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-union'
					=> array(
					'func' => array( $this, 'union' ),
					'type'	=> Attribute::RFC822NAME,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-subset'
					=> array(
					'func' => array( $this, 'subset' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:string-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::STRING,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:boolean-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BOOLEAN,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:integer-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::INTEGER,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:double-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DOUBLE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:time-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::TIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:date-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATE,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:dateTime-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::DATETIME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:anyURI-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::ANYURI,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:hexBinary-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::HEXBINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:base64Binary-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::BASE64BINARY,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:x500Name-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::X500NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
			'urn:oasis:names:tc:xacml:1.0:function:rfc822Name-set-equals'
					=> array(
					'func' => array( $this, 'setEquals' ),
					'type'	=> Attribute::BOOLEAN,
					'minArgs' => 2,
					'maxArgs' => 2,
					'argSpec' => array(
						'general' => array(
							'type' => Attribute::RFC822NAME,
							'acceptBag' => true
						),
						'passType' => true
					)
				),
		), $map );

		$this->equalityPredicates = array_merge(
			array(
				Attribute::STRING
					=> array( $this, 'stringEqual' ),
				Attribute::BOOLEAN
					=> array( $this, 'booleanEqual' ),
				Attribute::INTEGER
					=> array( $this, 'integerEqual' ),
				Attribute::DOUBLE
					=> array( $this, 'doubleEqual' ),
				Attribute::DATE
					=> array( $this, 'dateEqual' ),
				Attribute::TIME
					=> array( $this, 'timeEqual' ),
				Attribute::DATETIME
					=> array( $this, 'dateTimeEqual' ),
				Attribute::ANYURI
					=> array( $this, 'anyUriEqual' ),
				Attribute::X500NAME
					=> array( $this, 'x500NameEqual' ),
				Attribute::RFC822NAME
					=> array( $this, 'rfc822NameEqual' ),
				Attribute::HEXBINARY
					=> array( $this, 'hexBinaryEqual' ),
				Attribute::BASE64BINARY
					=> array( $this, 'base64BinaryEqual' ),
			),
			$equalityPredicates
		);
		parent::__construct( $map, $unmap, $replace );
	}

	/* equality predicates */
	protected function stringEqual( $attr1, $attr2 ){
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$tmp = ( $attr1 === $attr2 )? 'true': 'false';
		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function booleanEqual( $attr1, $attr2 ){
		return $this->stringEqual( $attr1, $attr2 );
	}

	protected function integerEqual( $attr1, $attr2 ){
		$attr1 = (int)reset( $attr1 );
		$attr2 = (int)reset( $attr2 );
		$tmp = ( $attr1 === $attr2 )? 'true': 'false';
		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function doubleEqual( $attr1, $attr2 ){
		$attr1 = (double)reset( $attr1 );
		$attr2 = (double)reset( $attr2 );

		if( is_nan( $attr1 ) || is_nan( $attr2 ) )
			$tmp = 'false';
		else{
			$tmp = $attr1 - $attr2;
			$tmp = ( !is_nan( $tmp ) && ( abs( $tmp ) < self::$doubleEqualEpsilon ) )? 'true': 'false';
		}

		return new Attribute( '', Attribute::BOOLEAN, $tmp );

	}

	protected function dateEqual( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );
		$attr1->setTime( 0, 0, 0 );
		$attr2->setTime( 0, 0, 0 );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1 == $attr2 )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function timeEqual( $attr1, $attr2 ){
		$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr1 ) ) );
		$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr2 ) ) );

		$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1 == $attr2 )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateTimeEqual( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1 == $attr2 )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	//from here to end of equality predicates I am pretty sure these don't work quite right
	protected function anyUriEqual( $attr1, $attr2 ){
		return $this->stringEqual( $attr1, $attr2 );
	}

	protected function x500NameEqual( $attr1, $attr2 ){
		throw new ProcessingErrorException( 'x500 functions not implemented here. Please do so soon.' );
	}

	protected function rfc822NameEqual( $attr1, $attr2 ){
		$attr1 = explode( '@', (string)reset( $attr1 ) );
		$attr2 = explode( '@', (string)reset( $attr2 ) );

		if( count( $attr1 ) != 2 || count( $attr2 ) != 2 )
			throw new ProcessingErrorException( 'improperly formatted rfc822Name' );

		$attr1[ 0 ] = mb_strtolower( $attr1[ 0 ] );
		$attr2[ 0 ] = mb_strtolower( $attr2[ 0 ] );

		$attr1 = implode( '@', $attr1 );
		$attr2 = implode( '@', $attr2 );

		return $this->stringEqual( array( $attr1 ), array( $attr2 ) );
	}

	protected function hexBinaryEqual( $attr1, $attr2 ){
		return $this->stringEqual( $attr1, $attr2 );
	}

	protected function base64BinaryEqual( $attr1, $attr2 ){
		return $this->stringEqual( $attr1, $attr2 );
	}

	/* arithmetic functions */
	protected function integerAdd( ...$args ){
		$result = 0;

		for( $i = 0; $i++; $i < count( $args ) )
			$result = (int)( $result + (int)reset( $args[ $i ] ) );

		return new Attribute( '', Attribute::INTEGER, $result );
	}

	protected function doubleAdd( ...$args ){
		$result = (double)0;

		for( $i = 0; $i++; $i < count( $args ) )
			$result = (double)( $result + (double)reset( $args[ $i ] ) );

		return new Attribute( '', Attribute::DOUBLE, $result );
	}

	protected function integerSubtract( $attr1, $attr2 ){
		$result = (int)( (int)reset( $attr1 ) - (int)reset( $attr2 ) );
		return new Attribute( '', Attribute::INTEGER, $result );
	}

	protected function doubleSubtract( $attr1, $attr2 ){
		$result = (double)( (double)reset( $attr1 ) - (double)reset( $attr2 ) );
		return new Attribute( '', Attribute::DOUBLE, $result );
	}

	protected function integerMultiply( ...$args ){
		$result = 0;

		for( $i = 0; $i++; $i < count( $args ) )
			$result = (int)( $result * (int)reset( $args[ $i ] ) );

		return new Attribute( '', Attribute::INTEGER, $result );
	}

	protected function doubleMultiply( ...$args ){
		$result = (double)0;

		for( $i = 0; $i++; $i < count( $args ) )
			$result = (double)( $result * (double)reset( $args[ $i ] ) );

		return new Attribute( '', Attribute::DOUBLE, $result );
	}

	protected function integerDivide( $attr1, $attr2 ){
		$attr1 = (int)reset( $attr1 );
		$attr2 = (int)reset( $attr2 );
		if( $attr2 == 0 )
			return new IndeterminateResultException( 'zero divisor in integer division' );

		return new Attribute( '', Attribute::INTEGER, ( (int)( $attr1 / $attr2 ) ) );
	}

	protected function doubleDivide( $attr1, $attr2 ){
		$attr1 = (double)reset( $attr1 );
		$attr2 = (double)reset( $attr2 );
		$tmp = $this->round( array( $attr2 ) )->getValue();
		$tmp = $this->doubleEqual( $tmp, array( (double)0 ) )->getValue();
		if( reset( $tmp ) === 'true' )
			return new IndeterminateResultException( 'zero divisor in double division' );

		return new Attribute( '', Attribute::DOUBLE, ( (double)( $attr1 / $attr2 ) ) );
	}

	protected function integerMod( $attr1, $attr2 ){
		$attr1 = (int)reset( $attr1 );
		$attr2 = (int)reset( $attr2 );

		return new Attribute( '', Attribute::INTEGER, ( (int)( $attr1 % $attr2 ) ) );
	}

	protected function integerAbs( $attr1 ){
		$attr1 = (int)reset( $attr1 );
		return new Attribute( '', Attribute::INTEGER, ( (int)abs( $attr1 ) ) );
	}

	protected function doubleAbs( $attr1 ){
		$attr1 = (double)reset( $attr1 );
		return new Attribute( '', Attribute::DOUBLE, ( (double)abs( $attr1 ) ) );
	}

	protected function mathRound( $attr1 ){
		$attr1 = (double)reset( $attr1 );
		return new Attribute( '', Attribute::DOUBLE, ( (double)round( $attr1, 0, PHP_ROUND_HALF_EVEN ) ) );
	}

	protected function mathFloor( $attr1 ){
		$attr1 = (double)reset( $attr1 );
		return new Attribute( '', Attribute::DOUBLE, ( (double)floor( $attr1 ) ) );
	}

	/* string conversion functions */
	protected function stringNormalizeSpace( $attr1 ){
		$attr1 = (string)reset( $attr1 );
		$attr1 = trim( $attr1, " \t\n\r" );
		return new Attribute( '', Attribute::STRING, $attr1 );
	}

	protected function stringNormalizeToLowerCase( $attr1 ){
		$attr1 = (string)reset( $attr1 );
		$attr1 = mb_strtolower( $attr1 );
		return new Attribute( '', Attribute::STRING, $attr1 );
	}

	/* numeric data-type conversion functions */
	protected function doubleToInteger( $attr1 ){
		return new Attribute( '', Attribute::INTEGER, ( (int)reset( $attr1 ) ) );
	}

	protected function integerToDouble( $attr1 ){
		$attr1 = (double)$attr1;
		if( $attr1 === INF || $attr1 === NAN )
			throw new ProcessingErrorException( 'integer cannot be converted to double');
		return new Attribute( '', Attribute::DOUBLE, $attr1 );
	}

	/* logical functions */
	protected function logicalOr( ...$args ){
		foreach( $args as $a ){
			$a = ( reset( $a ) === 'true' ) ;
			if( $a )
				return new Attribute( '', Attribute::BOOLEAN, 'true' );
		}

		return new Attribute( '', Attribute::BOOLEAN, 'false' );
	}

	protected function logicalAnd( ...$args ){
		foreach( $args as $a ){
			$a = ( reset( $a ) === 'true' ) ;
			if( !$a )
				return new Attribute( '', Attribute::BOOLEAN, 'false' );
		}

		return new Attribute( '', Attribute::BOOLEAN, 'true' );
	}

	protected function logicalNof( ...$args ){
		$c = count( $args );
		$return = new Attribute( '', Attribute::BOOLEAN, 'true' );

		if( $c < 1 || $args[ 0 ] > $c - 1 )
			return new IndeterminateResultException( 'invalid logical Nof' );

		$n = array_shift( $args );
		$n = (int)reset( $n );

		while( ( $a = array_shift( $args ) ) !== NULL ){
			$a = ( reset( $a ) === 'true' ) ;
			if( $a ){
				$n--;
				if( $n == 0 )
					return $return;
			}

			if( count( $args ) < $n )
				return new Attribute( '', Attribute::BOOLEAN, 'false' );
		}

		return $return;
	}

	protected function logicalNot( $attr1 ){
		$attr1 = reset( $attr1 );
		if( $attr1 === 'true' )
			return new Attribute( '', Attribute::BOOLEAN, 'false' );
		else
			return new Attribute( '', Attribute::BOOLEAN, 'true' );
	}

	/* numeric comparison functions */
	protected function integerGreaterThan( $attr1, $attr2 ){
		$result = ( ( (int)reset( $attr1 ) > (int)reset( $attr2 ) )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function integerGreaterThanOrEqual( $attr1, $attr2 ){
		$result = ( ( (int)reset( $attr1 ) >= (int)reset( $attr2 ) )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function integerLessThan( $attr1, $attr2 ){
		$result = ( ( (int)reset( $attr1 ) < (int)reset( $attr2 ) )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function integerLessThanOrEqualTo( $attr1, $attr2 ){
		$result = ( ( (int)reset( $attr1 ) <= (int)reset( $attr2 ) )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function doubleGreaterThan( $attr1, $attr2 ){
		$tmp = $this->doubleEqual( $attr1, $attr2 )->getValue();
		if( reset( $tmp ) === 'true' )
			$result = 'false';
		else
			$result = ( ( (double)reset( $attr1 ) > (double)reset( $attr2 ) )? 'true': 'false' );

		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function doubleGreaterThanOrEqualTo( $attr1, $attr2 ){
		$tmp = $this->doubleEqual( $attr1, $attr2 )->getValue();
		if( reset( $tmp ) === 'true' )
			$result = 'true';
		else
			$result = ( ( (double)reset( $attr1 ) >= (double)reset( $attr2 ) )? 'true': 'false' );

		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function doubleLessThan( $attr1, $attr2 ){
		$tmp = $this->doubleEqual( $attr1, $attr2 )->getValue();
		if( reset( $tmp ) === 'true' )
			$result = 'false';
		else
			$result = ( ( (double)reset( $attr1 ) < (double)reset( $attr2 ) )? 'true': 'false' );

		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function doubleLessThanOrEqualTo( $attr1, $attr2 ){
		$tmp = $this->doubleEqual( $attr1, $attr2 )->getValue();
		if( reset( $tmp ) === 'true' )
			$result = 'true';
		else
			$result = ( ( (double)reset( $attr1 ) <= (double)reset( $attr2 ) )? 'true': 'false' );

		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	/* non-numeric comparison functions */

	protected function stringGreaterThan( $attr1, $attr2 ){
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$col = \Collator::create( 'root' );
		if( !isset( $col ) )
			throw new ProcessingErrorException( 'unable to create multibyte string collator' );

		$res = $col->compare( $attr1, $attr2 );
		if( $res === false )
			throw new ProcessingErrorException( 'failed to compare strings' );

		$result = ( ( $res > 0 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function stringGreaterThanOrEqual( $attr1, $attr2 ){
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$col = \Collator::create( 'root' );
		if( !isset( $col ) )
			throw new ProcessingErrorException( 'unable to create multibyte string collator' );

		$res = $col->compare( $attr1, $attr2 );
		if( $res === false )
			throw new ProcessingErrorException( 'failed to compare strings' );

		$result = ( ( $res >= 0 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function stringLessThan( $attr1, $attr2 ){
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$col = \Collator::create( 'root' );
		if( !isset( $col ) )
			throw new ProcessingErrorException( 'unable to create multibyte string collator' );

		$res = $col->compare( $attr1, $attr2 );
		if( $res === false )
			throw new ProcessingErrorException( 'failed to compare strings' );

		$result = ( ( $res < 0 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function stringLessThanOrEqual( $attr1, $attr2 ){
		$attr1 = (string)reset( $attr1 );
		$attr2 = (string)reset( $attr2 );
		$col = \Collator::create( 'root' );
		if( !isset( $col ) )
			throw new ProcessingErrorException( 'unable to create multibyte string collator' );

		$res = $col->compare( $attr1, $attr2 );
		if( $res === false )
			throw new ProcessingErrorException( 'failed to compare strings' );

		$result = ( ( $res <= 0 )? 'true': 'false' );
		return new Attribute( '', Attribute::BOOLEAN, $result );
	}

	protected function timeGreaterThan( $attr1, $attr2 ){
		$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr1 ) ) );
		$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr2 ) ) );

		$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() > $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function timeGreaterThanOrEqualTo( $attr1, $attr2 ){
		$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr1 ) ) );
		$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr2 ) ) );

		$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() >= $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function timeLessThan( $attr1, $attr2 ){
		$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr1 ) ) );
		$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr2 ) ) );

		$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() < $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function timeLessThanOrEqual( $attr1, $attr2 ){
		$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr1 ) ) );
		$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $attr2 ) ) );

		$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
		$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() <= $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateTimeGreaterThan( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() > $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateTimeGreaterThanOrEqualTo( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() >= $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateTimeLessThan( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() < $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateTimeLessThanOrEqual( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() <= $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateGreaterThan( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$attr1->setTime( 0, 0, 0 );
		$attr2->setTime( 0, 0, 0 );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() > $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateGreaterThanOrEqualTo( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$attr1->setTime( 0, 0, 0 );
		$attr2->setTime( 0, 0, 0 );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() >= $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateLessThan( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$attr1->setTime( 0, 0, 0 );
		$attr2->setTime( 0, 0, 0 );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() < $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	protected function dateLessThanOrEqual( $attr1, $attr2 ){
		$attr1 = new \DateTime( reset( $attr1 ) );
		$attr2 = new \DateTime( reset( $attr2 ) );

		$attr1->setTime( 0, 0, 0 );
		$attr2->setTime( 0, 0, 0 );

		$utc = new \DateTimeZone( 'UTC' );
		$attr1->setTimezone( $utc );
		$attr2->setTimezone( $utc );
		
		$tmp = ( $attr1->getTimestamp() <= $attr2->getTimestamp() )? 'true': 'false';

		return new Attribute( '', Attribute::BOOLEAN, $tmp );
	}

	/* bag functions */
	protected function oneAndOnly( $attr1, $type ){
		if( count( $attr1 ) > 1 )
			return new IndeterminateResultException( 'bag has too many values' );
		return new Attribute( '', $type, $attr1 );
	}

	protected function bagSize( $attr1 ){
		return new Attribute( '', Attribute::INTEGER, count( $attr1 ) );
	}

	protected function isIn( $attr1, $attr2, $type, $removeDups = false ){
		$return = 'false';
		$attr1 = reset( $attr1 );
		if( !isset( $this->equalityPredicates[ $type ] ) )
			throw new ProcessingErrorException( 'unsupported static type' );

		$function = $this->equalityPredicates[ $type ];

		foreach( $attr2 as $i => $val ){
			$args = array( array( $attr1 ), array( $val ) );

			$tmp = call_user_func_array( $function, $args );

			if( $tmp instanceOf IndeterminateResultException )
				return $tmp;

			$tmp = $tmp->getValue();
			if( reset( $tmp ) === 'true' ){
				$return = 'true';
				if( !$removeDups )
					break;
				unset( $attr2[ $i ] );
			}
		}

		if( $removeDups )
			return array( $return, $attr2 );

		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function bag(){
		$args = func_get_args();
		$type = array_pop( $args );
		$return = array();
		foreach( $args as $arg )
			$return = array_merge( $return, $arg );

		return new Attribute( '', $type, $return );
	}

	/* set functions */
	protected function intersection( $attr1, $attr2, $type ){ #most likely extremely slow, all of the set functions most likely are
		if( !isset( $this->equalityPredicates[ $type ] ) )
			throw new ProcessingErrorException( 'unsupported static type' );

		$return = array();

		foreach( $attr1 as $val ){
			$r = $this->isIn( array( $val ), $attr2, $type );
			if( $r instanceOf IndeterminateResultException )
				return $r;
			else if( $r === 'true' )
				$return[] = $val;
		}

		return new Attribute( '', $type, $return );
	}

	protected function atLeastOneMemberOf( $attr1, $attr2, $type ){
		if( !isset( $this->equalityPredicates[ $type ] ) )
			throw new ProcessingErrorException( 'unsupported static type' );

		$return = 'false';

		while( $val = array_shift( $attr1 ) ){
			$r = $this->isIn( array( $val ), $attr2, $type );
			if( $r instanceOf IndeterminateResultException )
				return $r;
			$r = $r->getValue();
			if( reset( $r ) === 'true' ){
				$return = 'true';
				break;
			}
		}

		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	protected function union( $attr1, $attr2, $type ){
		if( !isset( $this->equalityPredicates[ $type ] ) )
			throw new ProcessingErrorException( 'unsupported static type' );

		$tmp = array_merge( $attr1, $attr2 );
		$return = array();

		foreach( $tmp as $val ){
			$r = $this->isIn( array( $val ), $return, $type );
			if( $r instanceOf IndeterminateResultException )
				return $r;
			$r = $r->getValue();
			if( reset( $r ) === 'false' )
				$return[] = $val;
		}

		return new Attribute( '', $type, $return );
	}

	protected function subset( $attr1, $attr2, $type ){
		$return = 'true';
		foreach( $attr1 as $val ){
			$r = $this->isIn( array( $val ), $attr2, $type );
			if( $r instanceOf IndeterminateResultException )
				return $r;
			$r = $r->getValue();
			if( reset( $r ) === 'false' ){
				$return = 'false';
				break;
			}
		}
		return new Attribute( '', $type, $return );
	}

	protected function setEquals( $attr1, $attr2, $type ){
		$r1 = $this->subset( $attr1, $attr2, $type );
		if( $r1 instanceOf IndeterminateResultException )
			return $r1;
		$r1 = $r1->getValue();

		$r2 = $this->subset( $attr2, $attr1, $type );
		if( $r2 instanceOf IndeterminateResultException )
			return $r2;
		$r2 = $r2->getValue();

		return $this->logicalAnd( $r1, $r2 );
	}

	/* higher order bag functions */
	protected function allOfAny( $op, $attr1, $attr2, $type ){
		$res = array();
		$args = array( new Attribute( '', $type ), new Attribute( '', $type ) );
		
		foreach( $attr1 as $tmp1 ){
			$args[ 0 ]->setValue( $tmp1 );
			$ret = array( 'false' );
			foreach( $attr2 as $tmp2 ){
				$args[ 1 ]->setValue( $tmp2 );
				try{
					$tmp = $this->callFunction( $op, 2, ...$args );
					if( !$tmp instanceOf Attribute )
						throw new IndeterminateResultException();
					$tmp = $tmp->getValue();
					if( reset( $tmp ) === 'true' ){
						$ret[ 0 ] = 'true';
						break;
					}
				}catch( IndeterminateResultException $e ){}
			}
			$res[] = $ret;
		}

		return $this->logicalAnd( $res );
	}

	protected function anyOfAll( $op, $attr1, $attr2, $type ){
		return $this->allOfAny( $op, $attr2, $attr1, $type );
	}

	protected function allOfAll( $op, $attr1, $attr2, $type ){
		$ret = 'true';
		$args = array( new Attribute( '', $type ), new Attribute( '', $type ) );
		
		foreach( $attr1 as $tmp1 ){
			$args[ 0 ]->setValue( $tmp1 );
			foreach( $attr2 as $tmp2 ){
				$args[ 1 ]->setValue( $tmp2 );
				try{
					$tmp = $this->callFunction( $op, 2, ...$args );
					if( !$tmp instanceOf Attribute )
						throw new IndeterminateResultException();
					$tmp = $tmp->getValue();
					if( reset( $tmp ) != 'true' )
						throw new IndterminateResultException();
				}catch( IndeterminateResultException $e ){
					$ret = 'false';
					break 2;
				}
			}
		}

		return new Attribute( '', Attribute::BOOLEAN, $ret );
	}

	/* regular expression based functions */
	protected function stringRegexpMatch( $regex, $attr1 ){
		$regex = reset( $regex );
		$attr1 = reset( $attr1 );
		$return = 'false';
		$ret = preg_match( '/' . $regex . '/u', $attr1 );
		

		if( $ret === false )
			throw new ProcessingErrorException( 'regex match error' );

		if( $ret === 1 )
			$return = 'true';

		return new Attribute( '', Attribute::BOOLEAN, $return );
	}

	/* special match functions */
	protected function x500NameMatch( $attr1, $attr2 ){
		throw new ProcessingErrorException( 'x500NameMatch not implemented' );
	}

	protected function rfc822NameMatch( $attr1, $attr2 ){
		$attr1 = explode( '@', reset( $attr1 ), 2 );
		$attr2 = explode( '@', reset( $attr2 ), 2 );
		if( count( $attr2 ) < 2 )
			throw new ProcessingErrorException( 'invalid rfc822 name' );

		if( count( $attr1 ) < 2 || $attr1[ 0 ] === '' ){
			$attr1[ 1 ] = $attr1[ 0 ];
			$attr1[ 0 ] = $attr2[ 0 ];
		}

		$attr1[ 1 ] = mb_strtolower( $attr1[ 1 ] );
		$attr2[ 1 ] = mb_strtolower( $attr2[ 1 ] );

		$matchedLocal = ( $attr1[ 0 ] === $attr2[ 0 ] );
		$matchedDomain = ( $attr1[ 1 ] === $attr2[ 1 ] );
		if( !$matchedDomain && mb_substr( $attr1[ 1 ], 0, 1 ) === '.' ){
			$str1 = strrev( $attr1[ 1 ] );
			$str2 = strrev( $attr2[ 1 ] );
			$r = strpos( $str2, $str1 );
			if( $r === 0 )
				$matchedDomain = true;
		}
		
		return new Attribute( '', Attribute::BOOLEAN, ( ( $matchedLocal && $matchedDomain )? 'true': 'false' ) );
	}

}