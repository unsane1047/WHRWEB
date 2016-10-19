<?php

namespace REJ\XacmlBundle\Readers;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use \XMLReader;

class StreamingReader implements XacmlReaderInterface{
	const LOADDTD = XMLReader::LOADDTD;
	const DEFAULTATTRS = XMLReader::DEFAULTATTRS;
	const VALIDATE = XMLReader::VALIDATE;
	const SUBST_ENTITIES = XMLReader::SUBST_ENTITIES;

	public $reader;

	public function __construct(){
		$this->reader = new XMLReader();
	}

	public function __destruct(){
		$this->close();
		unset( $this->reader );
	}

	public function __get( $name ){
		return $this->reader->$name;
	}

	public function jumpToEndElement( $name = NULL, $clearErr = true ){
		if( $name == NULL )
			$name = $this->elementName();
		$this->next( $name, $clearErr );
		while( $this->reader->nodeType != XmlReader::END_ELEMENT && $this->next( $name, $clearErr ) ){}
		return;
	}

	public function elementName(){
		return $this->__get( 'name' );
	}

	public function elementContent(){
		while( !$this->isText() && $this->read() )
		return $this->__get( 'value' );
	}

	public function isElement( $name = NULL ){
		if( $name !== NULL )
			return ( $this->reader->nodeType == XMLReader::ELEMENT && $this->reader->name == $name );
		return $this->reader->nodeType == XMLReader::ELEMENT;
	}

	public function isEndElement( $name = NULL ){
		if( $name !== NULL )
			return ( $this->reader->nodeType == XMLReader::END_ELEMENT && $this->reader->name == $name );
		return $this->reader->nodeType == XMLReader::END_ELEMENT;
	}

	public function isNone(){
		return $this->reader->nodeType == XMLReader::NONE;
	}

	public function isAttribute(){
		return $this->reader->nodeType == XMLReader::ATTRIBUTE;
	}

	public function isText(){
		return $this->reader->nodeType == XMLReader::TEXT;
	}

	public function isCdata(){
		return $this->reader->nodeType == XMLReader::CDATA;
	}

	public function isEntityRef(){
		return $this->reader->nodeType == XMLReader::ENTITY_REF;
	}

	public function isEntity(){
		return $this->reader->nodeType == XMLReader::ENTITY;
	}

	public function isPi(){
		return $this->reader->nodeType == XMLReader::PI;
	}

	public function isComment(){
		return $this->reader->nodeType == XMLReader::COMMENT;
	}

	public function isDoc(){
		return $this->reader->nodeType == XMLReader::DOC;
	}

	public function isDocType(){
		return $this->reader->nodeType == XMLReader::DOC_TYPE;
	}

	public function isDocFragment(){
		return $this->reader->nodeType == XMLReader::DOC_FRAGMENT;
	}

	public function isNotation(){
		return $this->reader->nodeType == XMLReader::NOTATION;
	}

	public function isWhitespace(){
		return $this->reader->nodeType == XMLReader::WHITESPACE;
	}

	public function isSignificateWhitespace(){
		return $this->reader->nodeType == XMLReader::SIGNIFICANT_WHITESPACE;
	}

	public function isEndEntity(){
		return $this->reader->nodeType == XMLReader::END_ENTITY;
	}

	public function isXMLDeclaration(){
		return $this->reader->nodeType == XMLReader::XML_DECLARATION;
	}

	public function close(){
		$this->reader->close();
	}

	public function expand( $clearErr = true ){
		$return = $this->reader->expand();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function getAttribute( $name, $clearErr = true ){
		$return = $this->reader->getAttribute( $name );
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function getAttributeNo( $index, $clearErr = true ){
		$return = $this->reader->getAttributeNo( $index );
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function getAttributeNs( $localName, $namespaceURI, $clearErr = true ){
		$return = $this->reader->getAttributeNs( $localName, $namespaceURI );
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function getParserProperty( $property ){
		return $this->reader->getParserProperty( $property );
	}

	public function isValid( $clearErr = true ){
		$return = $this->reader->isValid();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function lookupNamespace( $prefix, $clearErr = true ){
		$return = $this->reader->lookupNamespace( $prefix );
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function moveToAttribute( $name, $clearErr = true ){
		$return = $this->reader->moveToAttribute( $name );
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function moveToAttributeNo( $index, $clearErr = true ){
		$return = $this->reader->moveToAttributeNo( $index );
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function moveToAttributeNs( $localName, $namespaceURI, $clearErr = true ){
		$return = $this->reader->moveToAttributeNs( $localName, $namespaceURI );
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function moveToElement( $clearErr = true ){
		$return = $this->reader->moveToElement();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function moveToFirstAttribute( $clearErr = true ){
		$return = $this->reader->moveToFirstAttribute();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function moveToNextAttribute( $clearErr = true ){
		$return = $this->reader->moveToNextAttribute();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function next( $localname = NULL, $clearErr = true ){
		$return = NULL;
		if( $localname === NULL )
			$return = $this->reader->next();
		$return = $this->reader->next( $localname );

		$flag = $this->hasSyntaxError();

		if( $clearErr )
			$this->clearErrors();
		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );

		return $return;
	}

	public function open( $uri, $encoding = NULL, $clearErr = true ){
		$this->close();
		try{
			$ret = $this->reader->open( $uri, $encoding, LIBXML_COMPACT
									| LIBXML_NOCDATA
									| LIBXML_NONET
									| LIBXML_NOWARNING
									| LIBXML_NOENT );
		}catch( \Exception $e ){
			$ret = false;
		}

		$flag = $this->hasSyntaxError() || $ret === false ;

		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );

		return $this;
	}

	public function read( $clearErr = true ){
		$return = $this->reader->read();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();
		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function readInnerXML( $clearErr = true ){
		$return = $this->reader->readInnerXML();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();
		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function readOuterXML( $clearErr = true ){
		$return = $this->reader->readOuterXML();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();
		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function readString( $clearErr = true ){
		$return = $this->reader->readString();
		$flag = $this->hasSyntaxError();
		if( $clearErr )
			$this->clearErrors();
		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );
		return $return;
	}

	public function setParserProperty( $property, $value ){
		return $this->reader->setParserProperty( $property, $value );
	}

	public function setRelaxNGSchema( $filename ){
		return $this->reader->setRelaxNGSchema( $filename );
	}

	public function setRelaxNGSchemaSource( $source ){
		return $this->reader->setRelaxNGSchemaSource( $source );
	}

	public function setSchema( $filename ){
		return $this->reader->setSchema( $filename );
	}

	public function XML( $string, $encoding = NULL, $clearErr = true ){
		$this->close();
		try{
			$ret = $this->reader->XML( $string, $encoding, LIBXML_COMPACT
									| LIBXML_NOCDATA
									| LIBXML_NONET
									| LIBXML_NOWARNING
									| LIBXML_NOENT );
		}catch( \Exception $e ){
			$ret = false;
		}

		$flag = $this->hasSyntaxError() || $ret === false ;

		if( $clearErr )
			$this->clearErrors();

		if( $flag )
			throw new SyntaxErrorException( 'Unable to continue parsing. Please check xml syntax.' );

		return $this;
	}

	protected function convertError( \LibXMLError $e ){
		$error = '';

		switch( $e->level ){
			case LIBXML_ERR_WARNING:
				$error .= 'WARNING';
			break;

			case LIBXML_ERR_ERROR:
				$error .= 'ERROR';
			break;

			default:
				$error .= 'FATAL ERROR';
			break;
		}

		$line = $e->line - 1;
		$error .= sprintf( " %i: %s\nLine: %i", $e->code, $e->message, $line );
		return $error;
	}

	public function hasSyntaxError(){
		$errors = libxml_get_errors();
		$err = array();
		$hasErr = false;
		foreach( $errors as $e ){
			if( $e->level == LIBXML_ERR_ERROR
				|| $e->level == LIBXML_ERR_FATAL ){
				$hasErr = true;
				break;
			}
		}
		return $hasErr;
	}

	public function getLastError(){
		$e = libxml_get_last_error();

		if( $e !== false )
			return $e->convertError( $e );
		return NULL;
	}

	public function getErrors( $level = NULL ){
		$errors = libxml_get_errors();
		$err = array();
		foreach( $errors as $e )
			$err[ $e->level ] = $this->convertError( $e );

		if( $level === NULL ){
			$tmp = array();
			foreach( $err as $lev )
				$tmp = array_merge( $tmp, $lev );
			$err = $tmp;
		}
		else if( isset( $err[ $level ] ) )
			$err = $err[ $level ];
		else
			$err = array();
			
		return $err;
	}

	public function clearErrors(){
		return libxml_clear_errors();
	}
}