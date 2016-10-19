<?php

namespace REJ\XacmlBundle\v3_0\Expressions;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\v3_0\Expressions\abstractExpression;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;

class Apply extends abstractExpression{
	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables = NULL ){
		$arguments = array();

		if( !$r->isElement( 'Apply' ) )
			throw new ProcessingErrorException( 'Apply only interprets Apply Nodes' );

		$operation = $r->getAttribute( 'FunctionId' );

		if( $operation === NULL ){
			$r->jumpToEndElement( 'Apply' );
			throw new SyntaxErrorException( 'Apply fails to specify FunctionId' );
		}

		while( $r->read() ){
			if( $r->isElement() ){
				$name = $r->elementName();

				if( $name == 'Description' ){
					continue;
				}
				else if( $name == 'AttributeValue' ){
					$tmp = $this->attributeValue( $r, $req );
					$arguments[] = $tmp;
				}
				else if( $name == 'AttributeDesignator' ){
					try{
						$tmp = $this->attributeDesignator( $r, $req );
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Apply' );
						throw $e;
					}
					$arguments[] = $tmp;
				}
				else if( $name == 'AttributeSelector' ){
					try{
						$tmp = $this->attributeSelector( $r, $req );
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Apply' );
						throw $e;
					}
					$arguments[] = $tmp;
				}
				else if( $name == 'VariableReference' ){
					try{
						$tmp = $this->variableReference( $r, $variables );
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Apply' );
						throw $e;
					}
					$arguments[] = $tmp;
				}
				else if( $name == 'Apply' ){
					try{
						$tmp = $this->apply( $r, $req, $funcLib );
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Apply' );
						throw $e;
					}
					$arguments[] = $tmp;
				}
				else if( $name == 'Function' )
					$arguments[] = $this->functionElement( $r );
			}
			else if( $r->isEndElement( 'Apply' ) )
				break;
		}

		return $funcLib->apply( $operation, $arguments );
	}
}