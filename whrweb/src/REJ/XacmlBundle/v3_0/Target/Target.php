<?php

namespace REJ\XacmlBundle\v3_0\Target;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Target\AnyOf;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;

class Target{

	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib ){
		if( !$r->isElement( 'Target' ) )
			throw new ProcessingErrorException( 'evaluation failed. This is not a Target Tag' );

		$allMatch = true;
		$hasNoMatch = false;
		$missingAttributes = array();

		while( $r->read() ){
		
			if( $r->isElement( 'AnyOf' ) ){
				$res = new AnyOf();
				try{
					if( !$res->evaluate( $r, $req, $funcLib ) ){
						$hasNoMatch = true;
						$allMatch = false;
					}

				}catch( IndeterminateResultException $e ){
					$allMatch = false;
					while( $e = $e->getPrevious() ){
						if( $e instanceOf MissingAttributeErrorException ){
							$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
							break;
						}
					}
				}
			}
			else if( $r->isEndElement( 'Target' ) )
				break;
		}

		if( $allMatch )
			return true;
		else if( $hasNoMatch )
			return false;

		if( count( $missingAttributes ) > 0 ){
			$e = new MissingAttributeErrorException();
			$e->addMissingAttributes( $missingAttributes );
			$e = new IndeterminateResultException( 'Target is indeterminate.', 0, $e );
		}
		else
			$e = new IndeterminateResultException( 'Target is indeterminate.' );
		throw $e;
	}

}