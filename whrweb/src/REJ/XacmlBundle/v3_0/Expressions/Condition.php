<?php

namespace REJ\XacmlBundle\v3_0\Expressions;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException,
use REJ\XacmlBundle\v3_0\Expressions\abstractExpression;
use REJ\XacmlBundle\v3_0\Expressions\Apply;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;

class Condition extends abstractExpression{

	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables = NULL ){
		$return = true;

		if( !$r->isElement( 'Condition' ) )
			throw new ProcessingErrorException( 'Condition interprets Condition nodes only' );

		while( $r->read() ){
			if( $r->isElement() ){
				$name = $r->elementName();
				if( $name == 'AttributeValue' ){
					try{
						if( $r->getAttribute( 'DataType' ) != Attribute::BOOLEAN )
							throw new ProcessingErrorException( 'Incorrect return type for AttributeValue in Condition' );
						if( trim( $r->readString() ) === 'false' )
							$return = false;
					}finally{
						$r->jumpToEndElement( 'Condition' );
					}
					
					break;
				}
				else if( $name == 'AttributeDesignator' ){
					try{
						$tmp = $this->attributeDesignator( $r, $req );
						if( $tmp->isEmpty() || $tmp->getType() !== Attribute::BOOLEAN )
							throw new ProcessingErrorException( 'Incorrect return type for AttributeDesignator in Condition' );
						$return = in_array( 'true', $tmp->getValue(), true );
					}finally{
						$r->jumpToEndElement( 'Condition' );
					}

					break;
				}
				else if( $name == 'AttributeSelector' ){
					try{
						$tmp = $this->attributeSelector( $r, $req );
						if( $tmp->isEmpty() || $tmp->getType() !== Attribute::BOOLEAN )
							throw new ProcessingErrorException( 'Incorrect return type for AttributeSelector in Condition' );
						$return = in_array( 'true', $tmp->getValue(), true );
					}finally{
						$r->jumpToEndElement( 'Condition' );
					}
					break;

				}
				else if( $name == 'VariableReference' ){
					try{
						$tmp = $this->variableReference( $r, $variables );
						if( $tmp->isEmpty() || $tmp->getType() !== Attribute::BOOLEAN )
							throw new ProcessingErrorException( 'Incorrect return type for VariableReference in Condition' );
						$return = in_array( 'true', $tmp->getValue(), true );
					}finally{
						$r->jumpToEndElement( 'Condition' );
					}
				}
				else if( $name == 'Apply' ){
					try{
						$tmp = $this->apply( $r, $req, $funcLib, $variables );
						if( $tmp->getType() !== Attribute::BOOLEAN )
							throw new ProcessingErrorException( 'Incorrect return type for top level Apply in Condition' );
						$return = in_array( 'true', $tmp->getValue(), true );
					}finally{
						$r->jumpToEndElement( 'Condition' );
					}
					break;
				}

			}
			else if( $r->isEndElement( 'Condition' ) )
				break;
		}

		return $return;
	}

}