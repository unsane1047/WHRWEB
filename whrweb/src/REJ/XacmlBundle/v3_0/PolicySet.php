<?php

namespace REJ\XacmlBundle\v3_0;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\AbstractFunctionLibrary;
use REJ\XacmlBundle\Interfaces\PolicySetInterface;
use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\PolicyRetrievalPointInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;
use REJ\XacmlBundle\CombiningAlgorithms\DenyOverrides;
use REJ\XacmlBundle\CombiningAlgorithms\DenyOverridesLegacy;
use REJ\XacmlBundle\CombiningAlgorithms\DenyUnlessPermit;
use REJ\XacmlBundle\CombiningAlgorithms\FirstApplicable;
use REJ\XacmlBundle\CombiningAlgorithms\OnlyOneApplicable;
use REJ\XacmlBundle\CombiningAlgorithms\PermitOverrides;
use REJ\XacmlBundle\CombiningAlgorithms\PermitOverridesLegacy;
use REJ\XacmlBundle\CombiningAlgorithms\PermitUnlessDeny;
use REJ\XacmlBundle\v3_0\Target\Target;
use REJ\XacmlBundle\v3_0\Obligations\ObligationExpressions;
use REJ\XacmlBundle\v3_0\Obligations\AdviceExpressions;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;

class PolicySet implements PolicySetInterface{
	protected $reader;
	protected $prp;
	protected $combiningAlgo;
	protected $version;
	protected $id;
	protected $parentPolicySets = array();
	protected $decisionCache = array();
	protected $missingAttributes = array();

	public function __construct( XacmlReaderInterface $reader, PolicyRetrievalPointInterface $prp ){
		$this->reader = $reader;
		$this->prp = $prp;
	}

	public function getId(){
		if( !isset( $this->id ) && isset( $this->reader ) )
			$this->id = $this->reader->getAttribute( 'PolicySetId' );
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

	public function setParents( array $arr ){
		$this->parentPolicySets = $arr;
	}

	public function setCachedDecisions( array &$decisionCache = array() ){
		$this->decisionCache = $decisionCache;
	}

	public function evaluate( RequestInterface $req, AbstractFunctionLibrary $funcLib = NULL ){
		$r = $this->reader;
		$prp = $this->prp;

		if( !( $funcLib instanceOf FunctionLibrary ) ){
			$funcLib = new FunctionLibrary();
			$funcLib->registerObserver( $req );
		}

		$hasDecision = false;
		$hasTarget = false;
		$targetIsIndeterminate = false;
		$finalDecision = NULL;
		$advice = array(
			Decision::DENY => array(),
			Decision::PERMIT => array()
		);
		$obligations = array(
			Decision::DENY => array(),
			Decision::PERMIT => array()
		);

		if( !$r->isElement( 'PolicySet' ) ){
			$d = new Decision();
			$d->isIndeterminate( true );
			$d->isStatusProcessingError( true );
			$d->setStatusMessage( 'Attempt to evaluate PolicySet when no PolicySet node selected.' );
			return $d;
		}

		$this->getId();
		$this->getVersion();
		$this->combiningAlgo = $r->getAttribute( 'PolicyCombiningAlgId' );

		if( $this->id === NULL || $this->version === NULL || $this->combiningAlgo === NULL ){
			$r->jumpToEndElement( 'PolicySet' );
			$d = new Decision();
			$d->isIndeterminate( true );
			$d->isStatusSyntaxError( true );
			$d->setStatusMessage( 'Incomplete PolicySet specification.' );
			return $d;
		}

		if( !$this->initializeAlgo() ){
			$r->jumpToEndElement( 'PolicySet' );
			$d = new Decision();
			$d->isIndeterminate( true );
			$d->isStatusProcessingError( true );
			$d->setStatusMessage( 'Invalid Policy Combining Algorithm.' );
			return $d;	
		}

		$this->parentPolicySets[] = $this->getIdent();

		while( $r->read() ){
			$decision = NULL;
			if( $r->isElement() ){
				$name = $r->elementName();
				if( $name == 'Description' ){
					continue;
				}
				else if( $name == 'PolicyIssuer' ){ // attributes of the policy issuer only for admin profile not implemented here
					$r->jumpToEndElement( 'PolicySet' );
					$d = new Decision();
					$d->isIndeterminate( true );
					$d->isStatusProcessingError( true );;
					$d->setStatusMessage( 'PolicyIssuer not supported.' );
					return $d;
				}
				else if( $name == 'PolicySetDefaults' ){ //check to make sure xpath version is 1.0 as that is all PHP supports
					$node = $r->expand();
					foreach( $node->childNodes as $n ){
						if( $n->nodeName == 'XPathVersion' ){
							if( $n->getAttribute( 'type' ) !== 'http://www.w3.org/TR/1999/REC-xpath-19991116' ){
								$r->jumpToEndElement( 'PolicySet' );
								$d = new Decision();
								$d->isIndeterminate( true );
								$d->isStatusProcessingError( true );
								$d->setStatusMessage( 'No support for XPathVersions other than 1.0 in underlying libraries.' );
								return $d;
							}
						}
					}
					$r->jumpToEndElement( 'PolicySetDefaults' );
					continue;
				}
				else if( $name == 'Target' ){
					if( $hasTarget ){
						$r->jumpToEndElement( 'PolicySet' );
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( 'Invalid PolicySet specification. Multiple Target Elements Found.' );
						break;
					}

					try{
						$hasTarget = true;
						$t = new Target();
						if( !$t->evaluate( $r, $req, $funcLib ) ){
							$r->jumpToEndElement( 'PolicySet' );
							$finalDecision = new Decision();
							$finalDecision->isNotApplicable( true );
							$finalDecision->isStatusOk( true );
							break;
						}
					}catch( IndeterminateResultException $e ){
						$targetIsIndeterminate = true;
						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$this->missingAttributes = array_merge( $this->missingAttributes, $e->getMissingAttr() );
								break;
							}
						}
					}catch( SyntaxErrorException $e ){
						$r->jumpToEndElement( 'PolicySet' );
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed Target in PolicySet. %s.', $e->getMessage() ) );
						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'PolicySet' );
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating Target in PolicySet. %s.', $e->getMessage() ) );
						break;
					}
				}
				else if( $name == 'Policy' || $name == 'PolicySet' ){
					if( !$hasDecision ){
						$p = $prp->getInline( $r );
						if( isset( $this->decisionCache[ $p->getIdent() ] ) )
							$decision = $this->decisionCache[ $p->getIdent() ];
						else{
							if( $p instanceOf PolicySetInterface ){ #detect circular references
								if( in_array( $p->getIdent(), $this->parentPolicySets ) ){
									$r->jumpToEndElement( 'PolicySet' );
									$finalDecision = new Decision();
									$finalDecision->isIndeterminate( true );
									$finalDecision->isStatusProcessingError( true );
									$finalDecision->setStatusMessage( 'Circular reference.' );
									break;
								}
								$p->setParents( $this->parentPolicySets );
								$p->setCachedDecisions( $this->decisionCache );
							}

							try{
								$decision = $p->evaluate( $req, $funcLib );
							}catch( SyntaxErrorException $e ){
								$decision = new Decision();
								$decision->isIndeterminate( true );
								$decision->isStatusSyntaxError( true );
								$decision->setStatusMessage( sprintf( 'Syntax Error evaluating Policy or Policy Set %s: %s', $p->getIdent(), $e->getMessage() ) );
							}catch( \Exception $e ){
								$decision->isIndeterminate( true );
								$decision->isStatusProcessingError( true );
								$decision->setStatusMessage( sprintf( 'Processing Error evaluating Policy or Policy Set %s: %s', $p->getIdent(), $e->getMessage() ) );
							}

							$this->decisionCache[ $p->getIdent() ] = $decision;
						}

						if( $decision->isStatusError() ){
							$r->jumpToEndElement( 'PolicySet' );
							$finalDecision = $decision;
							break;
						}

						if( $decision->isStatusMissingAttribute() )
							$this->missingAttributes = array_merge( $this->missingAttributes, $decision->getMissingAttributes() );
					}
					else
						$r->jumpToEndElement( $name );
				}
				else if( $name == 'PolicySetIdReference' || $name == 'PolicyIdReference' ){
					if( !$hasDecision ){
						if( !( $prp instanceOf PolicyRetrievalPointInterface ) ){
							$r->jumpToEndElement( 'PolicySet' );
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
							$finalDecision->isStatusProcessingError( true );
							$finalDecision->setStatusMessage( 'PolicyIdReference or PolicySetIdReference used but no PolicyFinder supplied.' );
							break;
						}

						$version = $r->getAttribute( 'Version' );
						$earliestVersion = $r->getAttribute( 'EarliestVersion' );
						$latestVersion = $r->getAttribute( 'LatestVersion' );
						$uri = trim( $r->expand()->textContent );

						try{
							$p = $prp->findSub( $uri, $version, $earliestVersion, $latestVersion );

							if( isset( $this->decisionCache[ $p->getIdent() ] ) )
								$decision = $this->decisionCache[ $p->getIdent() ];
							else{
								if( $p instanceOf PolicySetInterface ){ #detect circular references
									if( in_array( $p->getIdent(), $this->parentPolicySets ) ){
										$finalDecision = new Decision();
										$finalDecision->isIndeterminate( true );
										$finalDecision->isStatusProcessingError( true );
										$finalDecision->setStatusMessage( 'Circular reference.' );
										return $d;
									}
									$p->setParents( $this->parentPolicySets );
									$p->setCachedDecisions( $this->decisionCache );
								}
								$decision = $p->evaluate( $req, $funcLib );
							}

						}catch( SyntaxErrorException $e ){
							$decision = new Decision();
							$decision->isIndeterminate( true );
							$decision->isStatusSyntaxError( true );
							$decision->setStatusMessage( sprintf( 'Syntax error in external document: %s.', $e->getMessage() ) );
						}catch( \Exception $e ){
							$decision = new Decision();
							$decision->isIndeterminate( true );
							$decision->isStatusProcessingError( true );
							$decision->setStatusMessage( $e->getMessage() );
						}

						$this->decisionCache[ $p->getIdent() ] = $decision;

						if( $decision->isStatusError() ){
							$r->jumpToEndElement( 'PolicySet' );
							$finalDecision = $decision;
							break;
						}
						if( $decision->isStatusMissingAttribute() )
							$this->missingAttributes = array_merge( $this->missingAttributes, $decision->getMissingAttributes() );
					}
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
						$r->jumpToEndElement( 'PolicySet' );
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed ObligationExpressions in PolicySet. %s.', $e->getMessage() ) );
						break;
					}catch( IndeterminateResultException $e ){
						$r->jumpToEndElement( 'PolicySet' );
						if( !$hasDecision ){
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
						}
						$hasDecision = true;
						$finalDecision->setStatusMessage( 'Indeterminate Result when evaluating ObligationExpressions.' );

						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$this->missingAttributes = array_merge( $this->missingAttributes, $e->getMissingAttr() );
								break;
							}
						}

						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'PolicySet' );
						$hasDecision = true;
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating ObligationExpressions in PolicySet. %s.', $e->getMessage() ) );
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
						$r->jumpToEndElement( 'PolicySet' );
						$hasDecision = true;
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusSyntaxError( true );
						$finalDecision->setStatusMessage( sprintf( 'Malformed AdviceExpressions in PolicySet. %s.', $e->getMessage() ) );
						break;
					}catch( IndeterminateResultException $e ){
						$r->jumpToEndElement( 'PolicySet' );
						if( !$hasDecision ){
							$finalDecision = new Decision();
							$finalDecision->isIndeterminate( true );
						}
						$hasDecision = true;
						$finalDecision->setStatusMessage( 'Indeterminate Result when evaluating AdviceExpressions.' );

						while( $e = $e->getPrevious() ){
							if( $e instanceOf MissingAttributeErrorException ){
								$this->missingAttributes = array_merge( $this->missingAttributes, $e->getMissingAttr() );
								break;
							}
						}

						break;
					}catch( \Exception $e ){
						$r->jumpToEndElement( 'PolicySet' );
						$hasDecision = true;
						$finalDecision = new Decision();
						$finalDecision->isIndeterminate( true );
						$finalDecision->isStatusProcessingError( true );
						$finalDecision->setStatusMessage( sprintf( 'Processing Error when evaluating AdviceExpressions in PolicySet. %s.', $e->getMessage() ) );
						break;
					}
				}
			}
			else if( $r->isEndElement( 'PolicySet' ) )
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
			if( count( $this->missingAttributes ) > 0 ){
				$finalDecision->isStatusMissingAttribute( true );
				$finalDecision->addMissingAttrs( $this->missingAttributes );
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

			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:deny-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:ordered-deny-overrides':
				$this->combiningAlgo = new DenyOverrides();
			break;

			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:permit-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:ordered-permit-overrides':
				$this->combiningAlgo = new PermitOverrides();
			break;

			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:first-applicable':
				$this->combiningAlgo = new FirstApplicable();
			break;

			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:only-one-applicable':
				$this->combiningAlgo = new OnlyOneapplicable();
			break;
			
			
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:deny-unless-permit':
				$this->combiningAlgo = new DenyUnlessPermit();
			break;

			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:permit-unless-deny':
				$this->combiningAlgo = new PermitUnlessDeny();
			break;

			case 'urn:oasis:names:tc:xacml:1.1:policy-combining-algorithm:ordered-deny-overrides':
			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:deny-overrides':
				$this->combiningAlgo = new DenyOverridesLegacy();
			break;

			case 'urn:oasis:names:tc:xacml:1.1:policy-combining-algorithm:ordered-permit-overrides':
			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:permit-overrides':
				$this->combiningAlgo = new PermitOverridesLegacy();
			break;
		}

		$this->combiningAlgo->isPolicyCombiner();

		return true;
	}

}