<?php

namespace REJ\XacmlBundle\Attributes;

class Attribute{
	const X500NAME = 'urn:oasis:names:tc:xacml:1.0:data-type:x500Name';
	const RFC822NAME = 'urn:oasis:names:tc:xacml:1.0:data-type:rfc822Name';
	const IPADDRESS = 'urn:oasis:names:tc:xacml:2.0:data-type:ipAddress';
	const DNSNAME = 'urn:oasis:names:tc:xacml:2.0:data-type:dnsName';
	const XPATHEXPRESSION = 'urn:oasis:names:tc:xacml:3.0:data-type:xpathExpression';
	const STRING = 'http://www.w3.org/2001/XMLSchema#string';
	const BOOLEAN = 'http://www.w3.org/2001/XMLSchema#boolean';
	const INTEGER = 'http://www.w3.org/2001/XMLSchema#integer';
	const DOUBLE = 'http://www.w3.org/2001/XMLSchema#double';
	const TIME = 'http://www.w3.org/2001/XMLSchema#time';
	const DATE = 'http://www.w3.org/2001/XMLSchema#date';
	const DATETIME = 'http://www.w3.org/2001/XMLSchema#dateTime';
	const ANYURI = 'http://www.w3.org/2001/XMLSchema#anyURI';
	const HEXBINARY = 'http://www.w3.org/2001/XMLSchema#hexBinary';
	const BASE64BINARY = 'http://www.w3.org/2001/XMLSchema#base64Binary';
	const DAYTIMEDURATION = 'http://www.w3.org/2001/XMLSchema#dayTimeDuration';
	const YEARMONTHDURATION = 'http://www.w3.org/2001/XMLSchema#yearMonthDuration';

	protected $id;
	protected $type = NULL;
	protected $value;
	protected $includeInResult;

	public function __construct( $id = NULL, $type = NULL, $value = NULL, $includeInResult = false ){
		if( !empty( $id ) )
			$this->setID( $id );
		if( !empty( $type ) )
			$this->setType( $type );
		if( !empty( $value ) )
			$this->setValue( $value );

		$this->includeInResult = $includeInResult;
	}

	public function setID( $id ){
		$this->id = $id;
		return $this;
	}

	public function setType( $type ){
		$this->type = $type;
		return $this;
	}

	public function setValue( $value ){
		if( !is_array( $value ) )
			$value = array( $value );
		$this->value = $value;
		return $this;
	}

	public function includeInResult( $bool = true ){
		$this->includeInResult = $bool;
		return $this;
	}

	public function getIncludeInResult(){
		return $this->includeInResult;
	}

	public function getID(){
		return $this->id;
	}

	public function getType(){
		return $this->type;
	}

	public function getValue(){
		return $this->value;
	}

	public function isEmpty(){
		return ( count( $this->value ) < 1 || empty( $this->type ) );
	}

	public function asAttribAssign( $category = '', $issuer = '' ){
		if( $this->isEmpty() )
			return '';

		try{
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			$dom->formatOutput = true;

			foreach( $this->getValue() as $v ){
				$attrsElem = $dom->createElement( 'AttributeAssignment' );
				$attrsElem->setAttribute( 'AttributeId', $this->getID() );
				$attrsElem->setAttribute( 'DataType', $this->getType() );

				if( !empty( $category ) )
					$attrsElem->setAttribute( 'Category', $category );
				if( !empty( $issuer ) )
					$attrsElem->setAttribute( 'Issuer', $issuer );

				$attrsElem->appendChild( $dom->createTextNode( $v ) );

				$dom->appendChild( $attrsElem );
			}

			return $dom->saveXML();
		}catch( \Exception $e ){
			return '';
		}
	}

	public function getCacheId(){
		return md5( serialize( array( $this->id, $this->type, $this->value ) ) );
	}
}