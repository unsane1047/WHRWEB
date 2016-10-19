<?php
namespace REJ\XacmlBundle\v3_0\Target;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Expressions\Match;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;

class AllOf{

	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib ){
		if( !$r->isElement( 'AllOf' ) )
			throw new ProcessingErrorException( 'evaluation failed. This is not an AllOf Tag' );

		$hasIndet = false;
		$hasFalse = false;
		$missingAttributes = array();

		while( $r->read() ){
			if( $r->isElement( 'Match' ) ){
				$res = new Match();
				try{
					if( !$res->evaluate( $r, $req, $funcLib ) ){
						$hasFalse = true;
						$r->jumpToEndElement( 'AllOf' );
						break;
					}
				}catch( IndeterminateResultException $e ){
					$hasIndet = true;
					while( $e = $e->getPrevious() ){
						if( $e instanceOf MissingAttributeErrorException ){
							$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
							break;
						}
					}
				}
			}
			else if( $r->isEndElement( 'AllOf' ) )
				break;
		}

		if( $hasFalse )
			return false;
		else if( $hasIndet ){
			if( count( $missingAttributes ) > 0 ){
				$e = new MissingAttributeErrorException();
				$e->addMissingAttributes( $missingAttributes );
				$e = new IndeterminateResultException( 'Indeterminate Result from AllOf evaluation', 0, $e );
			}
			else
				$e = new IndeterminateResultException( 'Indeterminate Result from AllOf evaluation' );
			throw $e;
		}
		return true;
	}
}