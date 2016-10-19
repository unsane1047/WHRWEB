<?php

namespace REJ\XacmlBundle\Interfaces;

use REJ\XacmlBundle\Attributes\AttributeSet;

interface RequestInterface{
	public function translateCategory( $cat = NULL );
	public function translateDataType( $type = NULL );
	public function getAttribute( $cat, $id, $dataType = NULL, $xpath = '', $contentXpath = NULL );
	public function returnPolicyIdList( $v = true );
	public function createCategory( $cat = NULL );
	public function addCategory( AttributeSet $cat );
	public function addAttribute( $cat, $id = NULL, $dataType = NULL, $value = NULL, $includeInResult = false );
	public function createFromRequest( $requestString, $format = 'XML' );
	public function saveXML( $version = '3.0' );
	public function __toString();
	public function getCacheId();
	public function getCacheLifetime();
	public function setCacheLifetime( $lifetime = 0 );
	public function observeCacheEffectingFunction( $op, $return, ...$args );
	public function setNewAdviceOutput( $key, $output );
	public function setNewObligationOutput( $key, $output );
	public function clearAdviceOutput();
	public function clearObligationOutput();
	public function getAdviceOutput();
	public function getObligationOutput();
}