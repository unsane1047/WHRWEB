<?php

namespace REJ\XacmlBundle\v3_0;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Target\Target;
use REJ\XacmlBundle\v3_0\Expressions\Condition;
use REJ\XacmlBundle\v3_0\Expressions\VariableDefinition;
use REJ\XacmlBundle\v3_0\Obligations\AdviceExpressions;
use REJ\XacmlBundle\v3_0\Obligations\ObligationExpressions;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;

class Rule{
	protected $reader;
	protected $id;

	public function getId(){
		if( !isset( $this->id ) && isset( $this->reader ) )
			$this->id = $this->reader->getAttribute( 'RuleId' );
		return $this->id;
	}

	public function getIdent(){
		return $this->getId();
	}

	public function __construct( XacmlReaderInterface $reader ){
		$this->reader = $reader;
	}

	public function evaluate( RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables ){
		$r = $this->reader;
		$hasTarget = false;
		$conditionValue = true;
		$hasCondition = false;
		$finalDecision = new Decision();
		$missingAttributes = array();
		$advice = array(
			Decision::DENY => array(),
			Decision::PERMIT => array()
		);
		$obligations = array(
			Decision::DENY => array(),
			Decision::PERMIT => array()
		);

		if( !$r->isElement( 'Rule' ) ){
			$finalDecision->isIndeterminate( true );
			$finalDecision->isStatusProcessingError( true );
			$finalDecision->setStatusMessage( 'Attempt to evaluate Rule when no Rule node selected.' );
			return $d;
		}

		$id = $this->getId();
		if( $id === NULL ){
			$finalDecision->isIndeterminate( true );
			$finalDecision->isStatusSyntaxError( true );
			$finalDecision->setStatusMessage( 'This rule does not supply an id.' );
			return $d;
		}

		$effect = ( $r->getAttribute( 'Effect' ) !== NULL )? $r->getAttribute( 'Effect' ): 'Deny';
		$effect = ( ( $effect === 'Permit' )? Decision::PERMIT: Decision::DENY );

		while( $r->read() ){
			if( $r->isElement() ){
				$name = $r->elementName();
				if( $name == 'Description' ){
					continue;
				}
				else if( $name == 'Target' ){
					if( $hasTarget ){
						$hasCondition = true;
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( 'Invalid Rule specification. Multiple Target elements found.' );
						break;
					}

					$hasTarget = true;

					try{
						$t = new Target();
						if( !$t->evaluate( $r, $req, $funcLib ) ){
							$hasCondition = true;
							$r->jumpToEndElement( 'Rule' );
							$finalDecision->isNotApplicable( true );
							$finalDecision->isStatusOk( true );
							break;
						}
					}catch( IndeterminateResultException $e ){
						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
								break;
							}
						}
						$hasCondition = true;
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusOk( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						break;
					}catch( SyntaxErrorException $e ){
						$hasCondition = true;
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed Target in Rule. %s.', $e->getMessage() ) );
						break;
					}catch( \Exception $e ){
						$hasCondition = true;
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating Target in Rule. %s.', $e->getMessage() ) );
						break;
					}

				}
				else if( $name == 'Condition' ){
					if( $hasCondition ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( 'Invalid Rule specification. Multiple Condition elements found.' );
						break;
					}

					$hasCondition = true;

					try{
						$c = new Condition();
						if( !$c->evaluate( $r, $req, $funcLib, $variables ) ){
							$r->jumpToEndElement( 'Rule' );
							$finalDecision->isNotApplicable( true );
							$finalDecision->isStatusOk( true );
							break;
						}
						$finalDecision->setDecision( $effect );
						$finalDecision->isStatusOk( true );
					}catch( MissingAttributeErrorException $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr () );
						break;
					}catch( IndeterminateResultException $e ){
						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
								break;
							}
						}
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusOk( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						break;
					}catch( SyntaxErrorException $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed Condition in Rule. %s.', $e->getMessage() ) );
						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating Condition in Rule. %s.', $e->getMessage() ) );
						break;
					}

				}
				else if( $name == 'ObligationExpressions' ){
					if( !$hasCondition ){
						$hasCondition = true;
						$finalDecision->setDecision( $effect );
						$finalDecision->isStatusOk( true );
					}
					$tmp = new ObligationExpressions();
					$tmp->onlyCompileFor( $finalDecision->getDecision() );
					try{
						$obligations = $tmp->compile( $r, $req, $funcLib );
					}catch( SyntaxErrorException $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed ObligationExpressions in Rule. %s.', $e->getMessage() ) );
						break;
					}catch( IndeterminateResultException $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->setStatusMessage( 'Indeterminate Result when evaluating ObligationExpressions.' );

						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
								break;
							}
						}

						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating ObligationExpressions in Rule. %s.', $e->getMessage() ) );
						break;
					}
				}
				else if( $name == 'AdviceExpressions' ){
					if( !$hasCondition ){
						$hasCondition = true;
						$finalDecision->setDecision( $effect );
						$finalDecision->isStatusOk( true );
					}
					$tmp = new AdviceExpressions();
					$tmp->onlyCompileFor( $finalDecision->getDecision() );
					try{
						$advice = $tmp->compile( $r, $req, $funcLib );
					}catch( SyntaxErrorException $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed AdviceExpressions in Rule. %s.', $e->getMessage() ) );
						break;
					}catch( IndeterminateResultException $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->setStatusMessage( 'Indeterminate Result when evaluating AdviceExpressions.' );

						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
								break;
							}
						}

						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Rule' );
						$finalDecision->isIndeterminate( true );
						if( $effect == Decision::DENY )
							$finalDecision->isIndeterminateD( true );
						else if( $effect == Decision::PERMIT )
							$finalDecision->isIndeterminateP( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating AdviceExpressions in Rule. %s.', $e->getMessage() ) );
						break;
					}
				}
			}
			else if( $r->isEndElement( 'Rule' ) )
				break;

		}

		if( !$hasCondition )
			$finalDecision->setDecision( $effect );

		if( !$finalDecision->isStatusError() ){
			if( count( $missingAttributes ) > 0 ){
				$finalDecision->isStatusMissingAttribute( true );
				$finalDecision->addMissingAttrs( $missingAttributes );
			}
			else if( $finalDecision->isPermit() || $finalDecision->isDeny() ){
				$finalDecision->setAdvices( array_merge( $advice[ $finalDecision->getDecision() ], $finalDecision->getAdvice() ) );
				$finalDecision->setObligations( array_merge( $obligations[ $finalDecision->getDecision() ], $finalDecision->getObligations() ) );
			}
		}

		return $finalDecision;
	}
}