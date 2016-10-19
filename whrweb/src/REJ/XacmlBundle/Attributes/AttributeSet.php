<?php
namespace REJ\XacmlBundle\Attributes;

use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;
use REJ\XacmlBundle\Attributes\Attribute;

class AttributeSet{
	//attribute categories
	const RESOURCE_CATEGORY = 'urn:oasis:names:tc:xacml:3.0:attribute-category:resource';
	const ACTION_CATEGORY = 'urn:oasis:names:tc:xacml:3.0:attribute-category:action';
	const ENVIRONMENT_CATEGORY = 'urn:oasis:names:tc:xacml:3.0:attribute-category:environment';
	const ACCESS_SUBJECT_CATEGORY = 'urn:oasis:names:tc:xacml:1.0:subject-category:access-subject';
	const RECIPIENT_SUBJECT_CATEGORY = 'urn:oasis:names:tc:xacml:1.0:subject-category:recipient-subject';
	const INTERMEDIARY_SUBJECT_CATEGORY = 'urn:oasis:names:tc:xacml:1.0:subject-category:intermediary-subject';
	const CODEBASE_SUBJECT_CATEGORY = 'urn:oasis:names:tc:xacml:1.0:subject-category:codebase';
	const REQUESTING_MACHINE_SUBJECT_CATEGORY = 'urn:oasis:names:tc:xacml:1.0:subject-category:requesting-machine';

	protected $attributes = [];
	protected $category = '';
	protected $content;
	protected $contentIsXML = false;
	protected $cacheId = NULL;

	public function createAttr( $id = NULL, $type = NULL, $value = NULL, $includeInResult = false ){
		return new Attribute( $id, $type, $value, $includeInResult );
	}

	public function getCategory(){
		return $this->category;
	}

	public function getAttributes(){
		return $this->attributes;
	}

	public function getContent(){
		return $this->content;
	}

	public function isContentXML(){
		return $this->contentIsXML;
	}

	public function __get( $name ){
		if( isset( $this->attributes[ $name ] ) )
			return $this->attributes[ $name ];

		$e = new MissingAttributeErrorException( sprintf( 'attribute named %s does not exist in category %s', $name, $this->category ) );
		$e->addMissingAttribute( $this->category, $name, 'unknown' );
		throw $e;
	}

	public function __isset( $name ){
		return isset( $this->attributes[ $name ] );
	}

	public function __unset( $name ){
		$this->cacheId = NULL;
		unset( $this->attributes[ $name ] );
	}

	public function addAttr( Attribute $attr ){
		if( $attr->getID() == '' || $attr->getType() == '' )
			throw new SyntaxErrorException( 'This attribute has not been properly initialized' );

		if( $attr->getValue() != '' ){
			$this->cacheId = NULL;
			$this->attributes[ $attr->getId() ] = $attr;
		}

		return $this;
	}

	public function setCategory( $cat ){
		$this->cacheId = NULL;
		$this->category = $cat;
		return $this;
	}

	public function addContent( $cont, $isXML = false ){
		$this->cacheId = NULL;
		$this->content = $cont;
		$this->contentIsXML = $isXML;
		return $this;
	}

	public function clear(){
		$this->cacheId = NULL;
		$this->attributes = array();
		$this->category = '';
		unset( $this->content );
		$this->contentIsXML = false;
		return $this;
	}

	//2.0 the xpath is rooted at the root of the request, so content should contain the whole request, which is stupid
	//3.0 the xpath is rooted at the root of the content element which this already works for
	//still need to figure out the namepacing stuff though because of quirks of php namepace support with xpath
	public function selectFromContent( $xpath, $dataType, $contextXpath = NULL ){ //needs to be updated so this will work with 2.0 spec differences and for somehow doing the namespaces right

		if( !$this->contentIsXML || empty( $xpath ) || empty( $dataType ) )
			throw new SyntaxErrorException( 'incorrect specification to select from content' );

		$xml = simplexml_load_string( $this->content );

		if( $xml === false )
			throw new SyntaxErrorException( 'unable to load content into XML parser' );

		if( $contextXpath !== NULL ){
			$arr  = $xml->xpath( $contextXpath );

			if( $arr === false || count( $arr ) > 1 )
				throw new SyntaxErrorException( 'unable to select context node from specified context path' );

			$xml = reset( $arr );
		}

		$result = $xml->xpath( $xpath );

		if( $result === false )
			throw new SyntaxErrorException( 'unable to follow context attribute selection path' );

		$res = $this->createAttrib();
		$res->setType( $dataType );
		$value = array();
		foreach( $result as $r ){
			$cont = $r->__toString();
			if( !empty( $cont ) )
				$value[] = $cont;
		}
		$res->setValue( $value );

		return $res;
	}

	public function __toString(){ //needs to be updated if there are differences with 2.0 or other specs, this is currently non-essential so I'm stopping work on this
		if( empty( $this->category ) )
			return '';

		try{
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			$dom->formatOutput = true;

			$attrsElem = $dom->createElement( 'Attributes' );
			$attrsElem->setAttribute( 'Category', $this->category );

			$dom->appendChild( $attrsElem );

			if( !empty( $this->content ) ){
				$content = $dom->createElement( 'Content' );
				$dom->appendChild( $content );

				if( $this->contentIsXML ){
					$fragment = $dom->createDocumentFragment();
					$fragment->appendXML( $this->content );
				}
				else
					$fragment = $dom->createTextNode( $this->content );

				$content->appendChild( $fragment );
			}

			foreach( $this->attributes as $attr ){
				$attrElem = $dom->createElement( 'Attribute' );
				$attrElem->setAttribute( 'IncludeInResult', ( ( $attr->getIncludeInResult() )? 'true': 'false' ) );
				$attrElem->setAttribute( 'AttributeId', $attr->getID() );
				$attrsElem->appendChild( $attrElem );

				$attrValueElem = $dom->createElement( 'AttributeValue' );
				$attrValueElem->setAttribute( 'DataType', $attr->getType() );
				$value = $attr->getValue();

				foreach( $value as $v ){
					$attrValueElem->appendChild( $dom->createTextNode( $v ) );
					$attrElem->appendChild( $attrValueElem );
				}
			}

			return $dom->saveXML( $attrsElem );
		}catch( \Exception $e ){
			return '';
		}
	}

	public function getCacheId(){
		if( $this->cacheId === NULL ){
			$id = $this->category . $this->content;
			ksort( $this->attributes );
			foreach( $this->attributes as $attr )
				$id .= $attr->getCacheId();
			$this->cacheId = md5( $id );
		}
		return $this->cacheId;
	}
}