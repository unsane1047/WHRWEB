<?php

namespace REJ\XacmlBundle\v3_0\Expressions;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\v3_0\Expressions\abstractExpression;
use REJ\XacmlBundle\v3_0\Expressions\Apply;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;

class VariableDefinition extends abstractExpression{

	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables = NULL ){
		if( !$r->isElement( 'VariableDefinition' ) )
			throw new ProcessingErrorException( 'VariableDefinition interprets VariableDefinition nodes only' );

		$id = $r->getAttribute( 'VariableId' );

		if( $id === NULL )
			throw new SyntaxErrorException( 'Undefined VariableID on VariableDefinition element' );

		$return = new Attribute();

		while( $r->read() ){
			if( $r->isElement() ){
				$name = $r->elementName();
				if( $name == 'AttributeValue' ){
					$return = $this->attributeValue( $r, $req, $id );
					$r->jumpToEndElement( 'VariableDefinition' );
					break;
				}
				else if( $name == 'AttributeDesignator' ){
					try{
						$return = $this->attributeDesignator( $r, $req );
					}finally{
						$r->jumpToEndElement( 'VariableDefinition' );
					}

					break;
				}
				else if( $name == 'AttributeSelector' ){
					try{
						$requestAttribute = $this->attributeSelector( $r, $req );
					}finally{
						$r->jumpToEndElement( 'VariableDefinition' );
					}
					break;
				}
				else if( $name == 'VariableReference' ){
					try{
						if( $id === $r->getAttribute( 'VariableId' ) )
							throw new ProcessingErrorException( 'Circular Reference detected in VariableDefinition Element' );
						$return = $this->variableReference( $r, $variables );
					}finally{
						$r->jumpToEndElement( 'VariableDefinition' );
					}

					break;
				}
				else if( $name == 'Apply' ){
					try{
						$return = $this->apply( $r, $req, $funcLib, $variables );
					}finally{
						$r->jumpToEndElement( 'VariableDefinition' );
					}

					break;
				}
			}
			else if( $r->isEndElement( 'VariableDefinition' ) )
				break;
		}

		$return->setId( $id );
		return $return;
	}

}