<?php

namespace REJ\XacmlBundle\Interfaces;

interface XacmlReaderInterface{
	public function __get( $name );
	public function jumpToEndElement( $name = NULL, $clearErr = true );
	public function isElement( $name = NULL );
	public function isEndElement( $name = NULL );
	public function elementName();
	public function elementContent();
	public function close();
	public function expand( $clearErr = true);
	public function getAttribute( $name, $clearErr = true );
	public function next( $localname = NULL, $clearErr = true );
	public function open( $uri, $encoding = NULL, $clearErr = true );
	public function read( $clearErr = true);
	public function XML( $string, $encoding = NULL, $clearErr = true );
	public function hasSyntaxError();
	public function getLastError();
	public function getErrors( $level = NULL );
	public function clearErrors();
}