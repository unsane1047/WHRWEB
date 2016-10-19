<?php

namespace REJ\XacmlBundle\v3_0\Obligations;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Interfaces\DecisionInterface;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Expressions\AttributeAssignmentExpression;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;

class AdviceExpressions{
	public $onlyCompileFor;
	
	public function onlyCompileFor( $i ){
		$this->onlyCompileFor = ( ( $i === DecisionInterface::DENY )? $i: DecisionInterface::PERMIT );
	}

	public function compile( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib = NULL ){
		$advice = array(
			DecisionInterface::DENY => array(),
			DecisionInterface::PERMIT => array()
		);

		if( !$r->isElement( 'AdviceExpressions' ) )
			throw new ProcessiongErrorException( 'Calling AdviceExpressions is only possible when reading an AdviceExpressions element.' );

		while( $r->read() ){
			if( $r->isElement( 'AdviceExpression' ) ){
				$id = $r->getAttribute( 'AdviceId' );
				$fulfillOn = $r->getAttribute( 'AppliesTo' );

				if( $fulfillOn === 'Permit' )
					$fulfillOn = DecisionInterface::PERMIT;
				else if( $fulfillOn === 'Deny' )
					$fulfillOn = DecisionInterface::DENY;
				else{
					$r->jumpToEndElement( 'AdviceExpressions' );
					throw new SyntaxErrorException( 'Unsupported FulfillOn value in AdviceExpression' );
				}

				if( isset( $onlyCompileFor ) && $onlyCompileFor !== $fulfillOn ){
					$r->jumpToEndElement( 'AdviceExpression' );
					continue;
				}

				$obligation = '<Advice';
				$obligation .= ' AdviceId="';
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
					else if( $r->isEndElement( 'AdviceExpression' ) )
						break;
				}

				$obligation .= "\n";
				$obligation .= "</Advice>\n";
				$advice[ $fulfillOn ][] = $obligation;
			}
			else if( $r->isEndElement( 'AdviceExpressions' ) )
				break;
		}

		return $advice;
	}
}