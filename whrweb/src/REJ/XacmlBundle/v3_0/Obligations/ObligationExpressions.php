<?php

namespace REJ\XacmlBundle\v3_0\Obligations;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Interfaces\DecisionInterface;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Expressions\AttributeAssignmentExpression;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;

class ObligationExpressions{
	public $onlyCompileFor;
	
	public function onlyCompileFor( $i ){
		$this->onlyCompileFor = ( ( $i === DecisionInterface::DENY )? $i: DecisionInterface::PERMIT );
	}

	public function compile( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib ){
		$obligations = array(
			DecisionInterface::DENY => array(),
			DecisionInterface::PERMIT => array()
		);

		if( !$r->isElement( 'ObligationExpressions' ) )
			throw new ProcessiongErrorException( 'Calling ObligationExpressions is only possible when reading an ObligationExpressions element.' );

		while( $r->read() ){
			if( $r->isElement( 'ObligationExpression' ) ){
				$id = $r->getAttribute( 'ObligationId' );
				$fulfillOn = $r->getAttribute( 'FulfillOn' );

				if( $fulfillOn === 'Permit' )
					$fulfillOn = DecisionInterface::PERMIT;
				else if( $fulfillOn === 'Deny' )
					$fulfillOn = DecisionInterface::DENY;
				else{
					$r->jumpToEndElement( 'ObligationExpressions' );
					throw new SyntaxErrorException( 'Unsupported FulfillOn value in ObligationExpression' );
				}

				if( isset( $onlyCompileFor ) && $onlyCompileFor !== $fulfillOn ){
					$r->jumpToEndElement( 'ObligationExpression' );
					continue;
				}

				$obligation = '<Obligation';
				$obligation .= ' ObligationId="';
				$obligation .= $id;
				$obligation .= "\">\n";

				while( $r->read() ){
					if( $r->isElement( 'AttributeAssignmentExpression' ) ){
						try{
							$tmp = new AttributeAssignmentExpression();
							$tmp = $tmp->evaluate( $r, $req, $funcLib );
							$obligation .= str_replace( "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n", '',  $tmp );
						}catch( \Exception $e ){
							$r->jumpToEndElement( 'ObligationExpressions' );
							throw $e;
						}
					}
					else if( $r->isEndElement( 'ObligationExpression' ) )
						break;
				}

				$obligation .= "</Obligation>\n";
				$obligations[ $fulfillOn ][] = $obligation;
			}
			else if( $r->isEndElement( 'ObligationExpressions' ) )
				break;
		}

		return $obligations;
	}
}