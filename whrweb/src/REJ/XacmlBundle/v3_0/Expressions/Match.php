<?php

namespace REJ\XacmlBundle\v3_0\Expressions;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Expressions\abstractExpression;

class Match extends abstractExpression{

	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables = NULL ){
		if( !$r->isElement( 'Match' ) )
			throw new ProcessingErrorException( 'evaluation failed. This is not a Match Tag' );

		$operation = $r->getAttribute( 'MatchId' );

		if( $operation == NULL ){
			$r->jumpToEndElement( 'Match' );
			throw new SyntaxErrorException( 'evaluation failed. MatchId not specified' );
		}

		$myAttribute = NULL;
		$requestAttribute = NULL;

		while( $r->read() ){
			if( $r->isElement() ){
				$name = $r->elementName();
				if( $name == 'AttributeValue' )
					$myAttribute = $this->attributeValue( $r, $req );
				else if( $name == 'AttributeDesignator' ){
					try{
						$requestAttribute = $this->attributeDesignator( $r, $req );
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Match' );
						throw $e;
					}
				}
				else if( $name == 'AttributeSelector' ){
					try{
						$requestAttribute = $this->attributeSelector( $r, $req );
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Match' );
						throw $e;
					}
				}
			}
			else if( $r->isEndElement( 'Match' ) )
				break;
		}

		if( $myAttribute === NULL
			|| $myAttribute->isEmpty()
			|| $requestAttribute === NULL )
			throw new SyntaxErrorException( 'Match does not properly specify AttributeValue and a AttributeDesignator or AttributeSelector for comparison.' );

		if( $requestAttribute->isEmpty() )
			return false;

		return $funcLib->match( $operation, $myAttribute, $requestAttribute );
	}

}