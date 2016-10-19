<?php
namespace REJ\XacmlBundle\v3_0\Target;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface,
use REJ\XacmlBundle\Interfaces\RequestInterface,
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary,
use REJ\XacmlBundle\v3_0\Target\AllOf,
use REJ\XacmlBundle\Exceptions\ProcessingErrorException,
use REJ\XacmlBundle\Exceptions\IndeterminateResultException,
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;

class AnyOf{

	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib ){
		if( !$r->isElement( 'AnyOf' ) )
			throw new ProcessingErrorException( 'evaluation failed. This is not an AnyOf Tag' );

		$hasMatch = false;
		$hasIndet = false;
		$missingAttributes = array();

		while( $r->read() ){
			if( $r->isElement( 'AllOf' ) ){
				$res = new AllOf();
				try{
					if( $res->evaluate( $r, $req, $funcLib ) ){
						$hasMatch = true;
						$r->jumpToEndElement( 'AnyOf' );
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
			else if( $r->isEndElement( 'AnyOf' ) )
				break;
		}

		if( $hasMatch )
			return true;
		else if( $hasIndet ){
			if( count( $missingAttributes ) > 0 ){
				$e = new MissingAttributeErrorException();
				$e->addMissingAttributes( $missingAttributes );
				$e = new IndeterminateResultException( 'Indeterminate Result from AnyOf evaluation', 0, $e );
			}
			else
				$e = new IndeterminateResultException( 'Indeterminate Result from AnyOf evaluation' );
			throw $e;
		}
		return false;
	}
}
