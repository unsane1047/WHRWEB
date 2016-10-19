<?php
namespace REJ\XacmlBundle\v3_0;

use REJ\XacmlBundle\Interfaces\PolicySetInterface;
use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\v3_0\Target\Target;
use REJ\XacmlBundle\v3_0\Obligations\ObligationExpressions;
use REJ\XacmlBundle\v3_0\Obligations\AdviceExpressions;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Expressions\VariableDefinition;
use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\CombiningAlgorithms\DenyOverrides;
use REJ\XacmlBundle\CombiningAlgorithms\DenyOverridesLegacy;
use REJ\XacmlBundle\CombiningAlgorithms\DenyUnlessPermit;
use REJ\XacmlBundle\CombiningAlgorithms\FirstApplicable;
use REJ\XacmlBundle\CombiningAlgorithms\PermitOverrides;
use REJ\XacmlBundle\CombiningAlgorithms\PermitOverridesLegacy;
use REJ\XacmlBundle\CombiningAlgorithms\PermitUnlessDeny;

class Policy{
	protected $reader;
	protected $combiningAlgo;
	protected $version;
	protected $id;

	public function getId(){
		if( !isset( $this->id ) && isset( $this->reader ) )
			$this->id = $this->reader->getAttribute( 'PolicyId' );
		return $this->id;
	}

	public function getVersion(){
		if( !isset( $this->version ) && isset( $this->reader ) )
			$this->version = $this->reader->getAttribute( 'Version' );
		return $this->version;
	}

	public function getIdent(){
		return $this->getId() . ':' . $this->getVersion();
	}

	public function __construct( XacmlReaderInterface $reader ){
		$this->reader = $reader;
	}

	public function evaluate( RequestInterface $req, FunctionLibrary $funcLib = NULL ){
		$r = $this->reader;
		if( $funcLib === NULL )
			$funcLib = new FunctionLibrary();
		$hasDecision = false;
		$hasTarget = false;
		$targetIsIndeterminate = false;
		$finalDecision = NULL;
		$variables = new AttributeSet();
		$variables->setCategory( 'variables' );
		$advice = array(
			Decision::DENY => array(),
			Decision::PERMIT => array()
		);
		$obligations = array(
			Decision::DENY => array(),
			Decision::PERMIT => array()
		);
		$missingAttributes = array();

		if( !$r->isElement( 'Policy' ) ){
			$d = new Decision();
			$d->isIndeterminate( true );
			$d->isStatusProcessingError( true );
			$d->setStatusMessage( 'Policy evaluates only Policy nodes.' );
			return $d;
		}

		$this->getId();
		$this->getVersion();
		$this->combiningAlgo = $r->getAttribute( 'RuleCombiningAlgId' );

		if( $this->id === NULL || $this->version === NULL || $this->combiningAlgo === NULL ){
			$r->jumpToEndElement( 'Policy' );
			$d = new Decision();
			$d->isIndeterminate( true );
			$d->isStatusSyntaxError( true );
			$d->setStatusMessage( 'Incomplete Policy specification.' );
			return $d;
		}

		if( !$this->initializeAlgo() ){
			$r->jumpToEndElement( 'Policy' );
			$d = new Decision();
			$d->isIndeterminate( true );
			$d->isStatusProcessingError( true );
			$d->setStatusMessage( 'Invalid Rule Combining Algorithm.' );
			return $d;	
		}

		while( $r->read() ){
			$decision = NULL;

			if( $r->isElement() ){
				$name = $r->elementName();
				if( $name == 'Description' ){
					continue;
				}
				else if( $name == 'PolicyIssuer' ){ // attributes of the policy issuer only for admin profile not implemented here
					$r->jumpToEndElement( 'Policy' );
					$d = new Decision();
					$d->isIndeterminate( true );
					$d->isStatusProcessingError( true );
					$d->setStatusMessage( 'PolicyIssuer not supported.' );
					return $d;
				}
				else if( $name == 'PolicyDefaults' ){ //check to make sure xpath version is 1.0 as that is all PHP supports
					$node = $r->expand();
					foreach( $node->childNodes as $n ){
						if( $n->nodeName == 'XPathVersion' ){
							if( $n->getAttribute( 'type' ) !== 'http://www.w3.org/TR/1999/REC-xpath-19991116' ){
								$r->jumpToEndElement( 'Policy' );
								$d = new Decision();
								$d->isIndeterminate( true );
								$d->isStatusProcessingError( true );
								$d->setStatusMessage( 'No support for XPathVersions other than 1.0 in underlying libraries.' );
								return $d;
							}
						}
					}
					$r->jumpToEndElement( 'PolicyDefaults' );
					continue;
				}
				else if( $name == 'VariableDefinition' ){
					if( !$hasDecision ){
						try{
							$tmp = new VariableDefinition();
							$tmp = $tmp->evaluate( $r, $req, $funcLib, $variables );
							$variables->addAttr( $tmp );
						}catch( MissingAttributeErrorException $e ){
							$r->jumpToEndElement( 'Policy' );
							$hasDecision = true;
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
							$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
							break;
						}catch( IndeterminateResultException $e ){
							$r->jumpToEndElement( 'Policy' );
							$hasDecision = true;
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
							$missingAttrs = false;
							while( $e = $e->getPrevious() ){
								if( $e instanceOf MissingAttributeErrorException ){
									$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
									$missingAttrs = true;
									break;
								}
							}
							if( !$missingAttrs ){
								$d->setStatus( PolicyDecisionPoint::PROCESSING_ERROR );
								$d->setStatusMessage( 'Circular Reference in VariableDefinition.' );
							}
							else
								$d->setStatusMessage( 'Missing Attributes in VariableDefinition.' );

							break;
						}catch( SyntaxErrorException $e ){
							$r->jumpToEndElement( 'Policy' );
							$hasDecision = true;
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
							$finalDecision->isStatusSyntaxError( true );
							$finalDecision->setStatusMessage( sprintf( 'Syntax Error when evaluating VariableDefinition in Policy. %s.', $e->getMessage() ) );
							break;
						}catch( \Exception $e ){
							$r->jumpToEndElement( 'Policy' );
							$hasDecision = true;
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
							$finalDecision->isStatusProcessingError( true );
							$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating VariableDefinition in Policy. %s.', $e->getMessage() ) );
							break;
						}
					}
					else
						$r->jumpToEndElement( 'VariableDefinition' );
				}
				else if( $name == 'Target' ){
					if( $hasTarget ){
						$r->jumpToEndElement( 'Policy' );
						$d = new Decision();
						$d->isIndeterminate( true );
						$d->isStatusSyntaxError( true );
						$d->setStatusMessage( 'Invalid Policy specification. Multiple Target Elements Found.' );
						break;
					}

					$hasTarget = true;

					try{
						$t = new Target();
						if( !$t->evaluate( $r, $req, $funcLib ) ){
							$r->jumpToEndElement( 'Policy' );
							$finalDecision = new Decision();
							$finalDecision->isNotApplicable( true );
							$finalDecision->isStatusOk( true );
							break;
						}
					}catch( IndeterminateResultException $e ){
						$targetIsIndeterminate = true;
						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
								break;
							}
						}
					}catch( SyntaxErrorException $e ){
						$r->jumpToEndElement( 'Policy' );
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed Target in Policy. %s.', $e->getMessage() ) );
						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Policy' );
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating Target in Policy. %s.', $e->getMessage() ) );
						break;
					}
				}
				else if( $name == 'Rule' ){
					if( !$hasDecision ){
						try{
							$t = new Rule( $r );
							$decision = $t->evaluate( $req, $funcLib, $variables );
						}catch( \Exception $e ){
							$decision = new Decision();
							$decision->isIndeterminate( true );
							$decision->isStatusProcessingError( true );
							$decision->setStatusMessage( sprintf( 'Processing Error when evaluating Rule in Policy. %s.', $e->getMessage() ) );
						}

						if( $decision->isStatusError() ){
							$r->jumpToEndElement( 'Policy' );
							return $decision;
						}

						if( $decision->isStatusMissingAttribute() )
							$missingAttributes = array_merge( $missingAttributes, $decision->getMissingAttributes() );
					}
					else
						$r->jumpToEndElement( $name );
				}
				else if( $name == 'ObligationExpressions' ){
					if( $targetIsIndeterminate ){
						$r->jumpToEndElement( 'ObligationExpressions' );
						continue;
					}

					$tmp = new ObligationExpressions();

					if( $hasDecision )
						$tmp->onlyCompileFor( $finalDecision->getDecision() );

					try{
						$obligations = $tmp->compile( $r, $req, $funcLib );
					}catch( SyntaxErrorException $e ){
						$r->jumpToEndElement( 'Policy' );
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed ObligationExpressions in Policy. %s.', $e->getMessage() ) );
						break;
					}catch( IndeterminateResultException $e ){
						$r->jumpToEndElement( 'Policy' );
						if( !$hasDecision ){
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
						}
						$hasDecision = true;
						$finalDecision->setStatusMessage( 'Indeterminate Result when evaluating ObligationExpressions.' );

						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
								break;
							}
						}

						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Policy' );
						$hasDecision = true;
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating ObligationExpressions in Policy. %s.', $e->getMessage() ) );
						break;
					}
				}
				else if( $name == 'AdviceExpressions' ){
					if( $targetIsIndeterminate ){
						$r->jumpToEndElement( 'AdviceExpressions' );
						continue;
					}

					$tmp = new AdviceExpressions();
					if( $hasDecision )
						$tmp->onlyCompileFor( $finalDecision->getDecision() );

					try{
						$advice = $tmp->compile( $r, $req, $funcLib );
					}catch( SyntaxErrorException $e ){
						$r->jumpToEndElement( 'Policy' );
						$hasDecision = true;
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed AdviceExpressions in Policy. %s.', $e->getMessage() ) );
						break;
					}catch( IndeterminateResultException $e ){
						$r->jumpToEndElement( 'Policy' );
						if( !$hasDecision ){
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
						}
						$hasDecision = true;
						$finalDecision->setStatusMessage( 'Indeterminate Result when evaluating AdviceExpressions.' );

						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$missingAttributes = array_merge( $missingAttributes, $e->getMissingAttr() );
								break;
							}
						}

						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'Policy' );
						$hasDecision = true;
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating AdviceExpressions in Policy. %s.', $e->getMessage() ) );
						break;
					}
				}

			}
			else if( $r->isEndElement( 'Policy' ) )
				break;

			if( !$hasDecision ){
				if( $decision !== NULL )
					$hasDecision = !$this->combiningAlgo->combine( $decision );
				if( $hasDecision )
					$finalDecision = $this->combiningAlgo->getDecision();
			}

		}

		if( $finalDecision === NULL )
			$finalDecision = $this->combiningAlgo->getDecision();

		if( $targetIsIndeterminate ){
			$finalDecision->clearAdvice();
			$finalDecision->clearObligations();
			if( $finalDecision->isPermit() ){
				$finalDecision->isIndeterminate( true );
				$finalDecision->isIndeterminateP( true );
				$finalDecision->isTargetIndeterminate( true );
			}
			else if( $finalDecision->isDeny() ){
				$finalDecision->isIndeterminate( true );
				$finalDecision->isIndeterminateD( true );
				$finalDecision->isTargetIndeterminate( true );
			}
		}

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

	private function initializeAlgo( $algo = '' ){
		if( !empty( $algo ) )
			$this->combiningAlgo = $algo;

		switch( $this->combiningAlgo ){
			default:
				return false;
			break;

			case 'urn:oasis:names:tc:xacml:3.0:rule-combining-algorithm:deny-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:rule-combining-algorithm:ordered-deny-overrides':
				$this->combiningAlgo = new DenyOverrides();
			break;

			case 'urn:oasis:names:tc:xacml:3.0:rule-combining-algorithm:permit-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:rule-combining-algorithm:ordered-permit-overrides':
				$this->combiningAlgo = new PermitOverrides();
			break;

			case 'urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:first-applicable':
				$this->combiningAlgo = new FirstApplicable();
			break;

			case 'urn:oasis:names:tc:xacml:3.0:rule-combining-algorithm:deny-unless-permit':
				$this->combiningAlgo = new DenyUnlessPermit();
			break;

			case 'urn:oasis:names:tc:xacml:3.0:rule-combining-algorithm:permit-unless-deny':
				$this->combiningAlgo = new PermitUnlessDeny();
			break;

			case 'urn:oasis:names:tc:xacml:1.1:rule-combining-algorithm:ordered-deny-overrides':
			case 'urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:deny-overrides':
				$this->combiningAlgo = new DenyOverridesLegacy();
			break;

			case 'urn:oasis:names:tc:xacml:1.1:rule-combining-algorithm:ordered-permit-overrides':
			case 'urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:permit-overrides':
				$this->combiningAlgo = new PermitOverridesLegacy();
			break;
		}

		$this->combiningAlgo->isRuleCombiner();
		return true;
	}

}