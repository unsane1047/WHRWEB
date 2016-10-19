<?php

namespace use REJ\XacmlBundle;

use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;

class Request implements RequestInterface{

	protected $namespaces;
	protected $returnPolicyIdList = false;
	protected $schemaLocations;
	protected $attributeSets = array();
	protected $cacheId = NULL;
	protected $cacheLifetime = NULL;
	protected $adviceOutput = array();
	protected $obligationOutput = array();

	public function translateCategory( $cat = NULL ){
		switch( $cat ){
			case 'environment':
				$cat = AttributeSet::ENVIRONMENT_CATEGORY;
			break;
			case 'action':
				$cat = AttributeSet::ACTION_CATEGORY;
			break;
			case 'resource':
				$cat = AttributeSet::RESOURCE_CATEGORY;
			break;
			case 'subject':
				$cat = AttributeSet::ACCESS_SUBJECT_CATEGORY;
			break;
			case 'recipient':
				$cat = AttributeSet::RECIPIENT_SUBJECT_CATEGORY;
			break;
			case 'intermediary subject':
				$cat = AttributeSet::INTERMEDIARY_SUBJECT_CATEGORY;
			break;
			case 'codebase':
				$cat = AttributeSet::CODEBASE_SUBJECT_CATEGORY;
			break;
			case 'requesting machine':
				$cat = AttributeSet::REQUESTING_MACHINE_SUBJECT_CATEGORY;
			break;
		}
		return $cat;
	}

	public function translateDataType( $type = NULL ){
		switch( $type ){
			case 'x500name':
				$type = Attribute::X500NAME;
			break;
			case 'rfc822name':
				$type = Attribute::RFC822NAME;
			break;
			case 'ipaddress':
				$type = Attribute::IPADDRESS;
			break;
			case 'dnsname':
				$type = Attribute::DNSNAME;
			break;
			case 'xpathexpression':
				$type = Attribute::XPATHEXPRESSION;
			break;
			case 'string':
				$type = Attribute::STRING;
			break;
			case 'boolean':
				$type = Attribute::BOOLEAN;
			break;
			case 'integer':
				$type = Attribute::INTEGER;
			break;
			case 'double':
				$type = Attribute::DOUBLE;
			break;
			case 'time':
				$type = Attribute::TIME;
			break;
			case 'date':
				$type = Attribute::DATE;
			break;
			case 'datetime':
				$type = Attribute::DATETIME;
			break;
			case 'anyuri':
				$type = Attribute::ANYURI;
			break;
			case 'hexbinary':
				$type = Attribute::HEXBINARY;
			break;
			case 'base64binary':
				$type = Attribute::BASE64BINARY;
			break;
			case 'daytimeduration':
				$type = Attribute::DAYTIMEDURATION;
			break;
			case 'yearmonthduration':
				$type = Attribute::YEARMONTHDURATION;
			break;
		}
		return $type;
	}

	public function returnPolicyIdList( $v = true ){
		$this->returnPolicyIdList = $v;
		return $this;
	}

	public function createCategory( $cat = NULL ){
		$category = new AttributeSet();
		$cat = $this->translateCategory( $cat );
		if( $cat !== NULL )
			$category->setCategory( $cat );
		return $category;
	}

	public function getAttribute( $cat, $id, $dataType = NULL, $xpath = '', $contextXpath = NULL ){
		$return = NULL;

		$cat = $this->translateCategory( $cat );
		$dataType = $this->translateDataType( $dataType );

		if( isset( $this->attributeSets[ $cat ] ) ){
			try{
				if( $id === 'content' )
					return $this->attributeSets[ $cat ]->selectFromContent( $xpath, $dataType, $contextXpath );
				return $this->attributeSets[ $cat ]->$id;
			}catch( MissingAttributeErrorException $e ){}
		}

		$e = new MissingAttributeErrorException( sprintf( 'attribute category %s was not provided by the submitted request or did not provide specified attribute %s', $cat, $id ) );
		$e->addMissingAttribute( $cat, $id, $dataType );
		throw $e;
	}

	public function addCategory( AttributeSet $cat ){
		$this->cacheId = NULL;
		$this->attributeSets[ $cat->getCategory() ] = $cat;
		return $this;
	}

	public function addAttribute( $cat, $id = NULL, $dataType = NULL, $value = NULL, $includeInResult = false ){
		$this->cacheId = NULL;
		$cat = $this->translateCategory( $cat );
		$dataType = $this->translateDataType( $dataType );
		if( $dataType === Attribute::BOOLEAN ){
			if( !is_array( $value ) )
				$value = array( $value );
			foreach( $value as $i => $v )
				$value[ $i ] = ( ( $v !== false && $v !== 'false' && $v !== 0 )? 'true': 'false' );
		}
		if( !isset( $this->attributeSets[ $cat ] ) )
			throw new MissingAttributeErrorException( 'cannot add attribute to a category that does not exist' );
		$attr = $this->attributeSets[ $cat ]->createAttr( $id, $dataType, $value, $includeInResult );
		$this->attributeSets[ $cat ]->addAttr( $attr );
		return $this;
	}

	public function createFromRequest( $requestString, $format = 'XML' ){
		if( $format !== 'XML' )
			throw new ProcessingErrorException( 'only xml request import implemented at this time' );

		try{
			$this->cacheId = NULL;
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			$dom->formatOutput = true;
			$dom->preserveWhiteSpace = false;
			$dom->loadXML( $requestString, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NOERROR| LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOCDATA | LIBXML_COMPACT );

			$root = $dom->getElementsByTagName( 'Request' );
			$root = $root->item( 0 );
			if( $root === NULL
				|| $root->getAttribute( 'xmlns' ) !== 'urn:oasis:names:tc:xacml:3.0:core:schema:wd-17' )
				throw new ProcessingErrorException( 'This is not an XACML 3.0 request ' );

			$this->returnPolicyIdList = ( $root->getAttribute( 'ReturnPolicyIdList' ) === 'true' );

			if( $root->hasChildNodes() ){
				foreach( $root->childNodes as $attrSet ){
					$cat = $attrSet->getAttribute( 'Category' );
					$this->addCategory( $this->createCategory( $cat ) );
					if( $attrSet->hasChildNodes() ){
						foreach( $attrSet->childNodes as $attr ){
							if( $attr->nodeName === 'Content' ){
								$cont = $dom->saveXML( $attr );
								$cont = preg_replace( '/^.+\n/', '', $cont );
								$cat->addContent( $cont, true );
							}
							else if( $attr->nodeType == XML_ELEMENT_NODE
								&& $attr->nodeName === 'Attribute' ){
								$id = $attr->getAttribute( 'AttributeId' );
								$includeInResult = ( $attr->getAttribute( 'IncludeInResult' ) === 'true' );
								$value = array();
								$type = NULL;

								if( $attr->hasChildNodes() ){
									foreach( $attr->childNodes as $attrVal ){
										if( $attrVal->nodeName !== 'AttributeValue' )
											continue;

										$value[] = $attrVal->nodeValue;
										if( !empty( $type ) )
											$type = $attrVal->getAttribute( 'DataType' );
									}
								}

								$this->addAttribute( $cat, $id, $type, $value, $includeInResult );
							}
						}
					}
				}
			}

		}catch( \Exception $e ){
			throw new ProcessingErrorException( 'error reading in request', 0, $e );
		}
	}

	public function saveXML( $version = '3.0' ){ //implement other versions in future
		if( $version !== '3.0' )
			return '';

		try{
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			$dom->formatOutput = true;

			$root = $dom->createElement( 'Request' );
			$root->setAttribute( 'xmlns', 'urn:oasis:names:tc:xacml:3.0:core:schema:wd-17');
			$root->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			$root->setAttribute( 'xsi:schemaLocation', 'urn:oasis:names:tc:xacml:3.0:core:schema:wd-17 http://docs.oasis-open.org/xacml/3.0/xacml-core-v3-schema-wd-17.xsd' );
			$root->setAttribute( 'ReturnPolicyIdList', ( ( $this->returnPolicyIdList )? 'true': 'false' ) );
			$dom->appendChild( $root );

			foreach( $this->attributeSets as $cat ){
				$frag = $dom->createDocumentFragment();
				$frag->appendXML( $cat . '' );
				$root->appendChild( $frag );
			}

			return $dom->saveXML();
		}catch( \Exception $e ){
			return '';
		}
	}

	public function __toString(){
		return $this->saveXML();
	}

	public function getCacheId(){
		if( $this->cacheId === NULL ){
			$id = '';
			ksort( $this->attributeSets );
			foreach( $this->attributeSets as $cat => $a ){
				if( $cat === AttributeSet::ENVIRONMENT_CATEGORY )
					continue;
				$id .= $a->getCacheId();
			}

			$this->cacheId = md5( $id );
		}
		return $this->cacheId;
	}

	public function getCacheLifetime(){
		if( $this->cacheLifetime === NULL )
			return 3600;
		return $this->cacheLifetime;
	}

	public function setCacheLifetime( $lifetime = 60 ){
		$this->cacheLifetime = $lifetime;
	}

	public function observeCacheEffectingFunction( $op, $return, ...$args ){
		switch( $op ){
			default:
			break;

			case 'urn:oasis:names:tc:xacml:1.0:function:time-equal':
				$type = 'time';
				$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 0 ] ) ) );
				$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 1 ] ) ) );
				$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
				$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
			case 'urn:oasis:names:tc:xacml:1.0:function:date-equal':
				if( !isset( $attr1 ) ){
					$type = 'date';
					$attr1 = new \DateTime( reset( $args[ 0 ] ) );
					$attr2 = new \DateTime( reset( $args[ 1 ] ) );
					$attr1->setTime( 0, 0, 0 );
					$attr2->setTime( 0, 0, 0 );
				}
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-equal':
				if( !isset( $attr1 ) ){
					$type = 'datetime';
					$attr1 = new \DateTime( reset( $attr1 ) );
					$attr2 = new \DateTime( reset( $attr2 ) );
				}
				$utc = new \DateTimeZone( 'UTC' );
				$attr1->setTimezone( $utc );
				$attr2->setTimezone( $utc );

				if( $type === 'datetime' || $type === 'time' )
					$tmp = -1;
				else if( reset( $result ) === 'true' )
					$tmp = strtotime( 'tomorrow 00:00:00', $attr1->getTimestamp() ) - time();
				else{
					$attr1 = $attr1->getTimestamp();
					$attr2 = $attr2->getTimestamp();
					$use = $attr1;
					if( $attr2 > $attr1 )
						$use = $attr2;
					$tmp = time() - $use;
				}

				if( $tmp === 0 )
					$tmp = 10;

				if( $tmp < $this->cacheLifetime || $this->cacheLifetime === 0 )
					$this->setCacheLifetime( $tmp );
			break;

			case 'urn:oasis:names:tc:xacml:1.0:function:time-greater-than-or-equal':
			case 'urn:oasis:names:tc:xacml:1.0:function:time-greater-than':
				$type = 'time';
				$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 0 ] ) ) );
				$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 1 ] ) ) );
				$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
				$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
			case 'urn:oasis:names:tc:xacml:1.0:function:date-greater-than-or-equal':
			case 'urn:oasis:names:tc:xacml:1.0:function:date-greater-than':
				if( !isset( $attr1 ) ){
					$type = 'date';
					$attr1 = new \DateTime( reset( $args[ 0 ] ) );
					$attr2 = new \DateTime( reset( $args[ 1 ] ) );
					$attr1->setTime( 0, 0, 0 );
					$attr2->setTime( 0, 0, 0 );
				}
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-greater-than-or-equal':
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-greater-than':
				if( !isset( $attr1 ) ){
					$type = 'datetime';
					$attr1 = new \DateTime( reset( $attr1 ) );
					$attr2 = new \DateTime( reset( $attr2 ) );
				}
				$utc = new \DateTimeZone( 'UTC' );
				$attr1->setTimezone( $utc );
				$attr2->setTimezone( $utc );
				if( reset( $result ) === 'true' && $type !== 'time' )
					$tmp = 0;
				else if( $type === 'time' && reset( $result ) === 'true' )
					$tmp = strtotime( 'tomorrow 00:00:00' ) - time();
				else{
					$tmp = $attr2->getTimestamp() - $attr1->getTimestamp();
					if( $tmp === 0 )
						$tmp = -1;
				}

				if( $this->cacheLifetime === NULL || ( $tmp !== 0 && ( $tmp < $this->cacheLifetime || $this->cacheLifetime === 0 ) ) )
					$this->setCacheLifetime( $tmp );
			break;

			case 'urn:oasis:names:tc:xacml:1.0:function:time-less-than-or-equal':
			case 'urn:oasis:names:tc:xacml:1.0:function:time-less-than':
				$type = 'time';
				$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 0 ] ) ) );
				$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 1 ] ) ) );
				$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
				$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
			case 'urn:oasis:names:tc:xacml:1.0:function:date-less-than-or-equal':
			case 'urn:oasis:names:tc:xacml:1.0:function:date-less-than':
				if( !isset( $attr1 ) ){
					$type = 'date';
					$attr1 = new \DateTime( reset( $args[ 0 ] ) );
					$attr2 = new \DateTime( reset( $args[ 1 ] ) );
					$attr1->setTime( 0, 0, 0 );
					$attr2->setTime( 0, 0, 0 );
				}
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-less-than-or-equal':
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-less-than':
				if( !isset( $attr1 ) ){
					$type = 'datetime';
					$attr1 = new \DateTime( reset( $attr1 ) );
					$attr2 = new \DateTime( reset( $attr2 ) );
				}
				$utc = new \DateTimeZone( 'UTC' );
				$attr1->setTimezone( $utc );
				$attr2->setTimezone( $utc );
				if( reset( $result ) === 'false' && $type !== 'time' )
					$tmp = 0;
				else if( $type === 'time' && reset( $result ) === 'false' )
					$tmp = strtotime( 'tomorrow 00:00:00' ) - time();
				else{
					$tmp = $attr1->getTimestamp() - $attr2->getTimestamp();
					if( $tmp === 0 )
						$tmp = -1;
				}

				if( $this->cacheLifetime === NULL || ( $tmp !== 0 && ( $tmp < $this->cacheLifetime || $this->cacheLifetime === 0 ) ) )
					$this->setCacheLifetime( $tmp );
			break;

			case 'urn:oasis:names:tc:xacml:1.0:function:time-is-in':
			case 'urn:oasis:names:tc:xacml:1.0:function:date-is-in':
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-is-in':
			case 'urn:oasis:names:tc:xacml:1.0:function:time-at-least-one-member-of':
			case 'urn:oasis:names:tc:xacml:1.0:function:date-at-least-one-member-of':
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-at-least-one-member-of':
			case 'urn:oasis:names:tc:xacml:1.0:function:time-subset':
			case 'urn:oasis:names:tc:xacml:1.0:function:date-subset':
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-subset':
			case 'urn:oasis:names:tc:xacml:1.0:function:time-set-equals':
			case 'urn:oasis:names:tc:xacml:1.0:function:date-set-equals':
			case 'urn:oasis:names:tc:xacml:1.0:function:dateTime-set-equals':
				$this->setCacheLifetime( -1 ); //probably need to acually have an algorithm here, not just give up on caching
			break;

			case 'urn:oasis:names:tc:xacml:2.0:function:time-in-range':
				if( strlen( $args[ 0 ] ) <= 8 )
					$args[ 0 ] .= 'Z';
				$attr1 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 0 ] ) ) );

				$tz = $attr1->getTimeZone();

				if( $tz === false )
					$tz = new \DateTimeZone( 'UTC' );

				if( strlen( $args[ 1 ] ) <= 8 )
					$args[ 1 ] .= $tz->getOffset();

				$attr2 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 1 ] ) ) );

				if( strlen( $args[ 2 ] ) <= 8 )
					$attr3 .= $tz->getOffset();

				$attr3 = new \DateTime( str_replace( '24:00:00' , '00:00:00', reset( $args[ 2 ] ) ) );

				$attr1->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
				$attr2->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
				$attr3->setDate( date( 'Y' ), date( 'm' ), date( 'd' ) );
			
				$utc = new \DateTimeZone( 'UTC' );
				$attr1->setTimezone( $utc );
				$attr2->setTimezone( $utc );
				$attr3->setTimezone( $utc );

				if( $attr3->getTimestamp() < $attr2->getTimestamp() )
					$attr3 = $attr2;
				
				if( reset( $return ) === 'true' )
					$tmp = $attr3 - $attr1;
				else{
					$attr1 = $attr1->getTimestamp();
					$attr2 = $attr2->getTimestamp();
					$attr3 = $attr3->getTimestamp();
					if( $attr1 < $attr2 )
						$tmp = $attr2 - $attr1;
					else
						$tmp = strtotime( 'tomorrow 00:00:00' ) - $attr1;
				}

				if( $tmp === 0 )
					$tmp = 10;
				if( $this->cacheLifetime === NULL || ( $tmp < $this->cacheLifetime || $this->cacheLifetime === 0 ) )
					$this->setCacheLifetime( $tmp );
			break;
		}

		return;
	}

	public function setNewAdviceOutput( $key, $output, $overwrite = false ){
		if( isset( $this->adviceOutput[ $key ] ) && !$overwrite ){
			if( !is_array( $this->adviceOutput[ $key ] ) )
				$this->adviceOutput[ $key ] = array( $this->adviceOutput[ $key ] );
			$this->adviceOutput[ $key ][] = $output;
		}
		else
			$this->adviceOutput[ $key ] = $output;
		return $this;
	}

	public function setNewObligationOutput( $key, $output, $overwrite = false ){
		if( isset( $this->obligationOutput[ $key ] ) && !$overwrite ){
			if( !is_array( $this->obligationOutput[ $key ] ) )
				$this->obligationOutput[ $key ] = array( $this->obligationOutput[ $key ] );
			$this->obligationOutput[ $key ][] = $output;
		}
		else
			$this->obligationOutput[ $key ] = $output;
		return $this;
	}

	public function clearAdviceOutput(){
		$this->adviceOutput = array();
	}

	public function clearObligationOutput(){
		$this->obligationOutput = array();
	}

	public function getAdviceOutput(){
		return $this->adviceOutput;
	}

	public function getObligationOutput(){
		return $this->obligationOutput();
	}
}