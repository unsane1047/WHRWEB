<?php

namespace REJ\XacmlBundle\Exceptions;

use \DOMDocument;

class MissingAttributeErrorException extends \RuntimeException {
	protected $missingAttribs = array();

	public function addMissingAttribute( $category, $name, $dataType, $issuer = '', $value = '' ){
		$this->missingAttribs[] = array(
			'name' => $name,
			'category' => $category,
			'dataType' => $dataType,
			'issuer' => $issuer,
			'value' => $value
		);
	}

	public function addMissingAttributes( array $missingAttr = array() ){
		$this->missingAttribs = array_merge( $this->missingAttribs, $missingAttr );
	}

	public function getMissingAttr(){
		return $this->missingAttribs;
	}

	public function getMissingAttrDetail(){
		try{
			$dom = new DOMDocument( '1.0', 'UTF-8' );
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;

			$root = $dom->createElement( 'StatusDetail' );

			foreach( $this->missingAttribs as $attr ){
				if( !isset( $attr[ 'category' ] ) || empty( $attr[ 'category' ] ) )
					$attr[ 'category' ] = 'unknown';
				if( !isset( $attr[ 'name' ] ) || empty( $attr[ 'name' ] ) )
					$attr[ 'name' ] = 'unknown';
				if( !isset( $attr[ 'dataType' ] ) || empty( $attr[ 'dataType' ] ) )
					$attr[ 'dataType' ] = 'unknown';
				$tmp = $dom->createElement( 'MissingAttributeDetail' );
				$tmp->setAttribute( 'Category', $attr[ 'category' ] );
				$tmp->setAttribute( 'AttributeId', $attr[ 'name' ] );
				$tmp->setAttribute( 'DataType', $attr[ 'dataType' ] );
				if( isset( $attr[ 'issuer' ] ) && !empty( $attr[ 'issuer' ] ) )
					$tmp->setAttribute( 'Issuer', $attr[ 'issuer' ] );
				if( isset( $attr[ 'value' ] ) && !empty( $attr[ 'value' ] ) )
					$tmp->setAttribute( 'AttributeValue', $attr[ 'value' ] );
				$root->appendChild( $tmp );
			}

			return $dom->saveXML( $root );
		}catch( \Exception $e ){
			return '';
		}
	}

	public function clearAttrs(){
		$this->missingAttribs = array();
	}

	public function asMissingAttribDetail( $category, $name, $dataType, $issuer = '', $value = '' ){
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$dom->formatOutput = true;
		$root = $dom->createElement( 'missingAttributeDetail' );
		if( empty( $category ) )
			$category = 'unknown';
		if( empty( $name ) )
			$name = 'unknown';
		if( empty( $dataType ) )
			$dataType = 'unknown';
		$root->setAttribute( 'Category', $category );
		$root->setAttribute( 'AttributeId', $name );
		$root->setAttribute( 'DataType', $dataType );
		if( !empty( $issuer ) )
			$root->setAttribute( 'Issuer', $issuer );
		if( !empty( $value ) )
			$root->setAttribute( 'AttributeValue', $value );
		$dom->appendChild( $root );
		return $dom->saveXML( $root );
	}
}