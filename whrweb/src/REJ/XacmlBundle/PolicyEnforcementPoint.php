<?php

namespace REJ\XacmlBundle;

use REJ\XacmlBundle\Interfaces\PolicyDecisionPointInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\ObligationFactory;

//work with obligation so that they will actually do something, may need to change obligation factory so that it will return null when noextant obligation called
//pdp add policyIdList to all cached objects somehow
//pdp cache needs to implement flushing of cached decisions based on their attached policyIdList

//voter needs to know difference between recipient subject as being overridden for uac and when it is a developer using sudo
//need to find test cases to make sure we are actually standards compliant and then profile this shit to see if performance is going to be an issue

//later
//streaming reader does not support mixed version policy sets or any other version than 3.0
//streaming reader does not support policy or policy set version matching
//request export and import does not handle content properly, not high priority because I don't think we will be using this
//AttributSet does not retrieve from content properly, namespaces may be an issue and versions other than 3.0 will be interesting to use because they have a different root
//no object support and unfinished function libraries for versions other than 3.0
//may need to extend so that we can do the multiRequest feature at some point in the future
//may need to implement optional functionality in the future

class PolicyEnforcementPoint{
	protected $requestContext;
	protected $pdp;
	protected $obligationFactory;
	protected $denyBiased = false;
	protected $permitBiased = false;

	public function __construct( PolicyDecisionPointInterface $pdp = NULL, ObligationFactory $obl = NULL ){
		if( $obl === NULL ){
			$obl = new ObligationFactory();
			$obl->registerDefaults();
		}

		$this->setPDP( $pdp )
			->setObligationFactory( $obl );
	}

	public function setPDP( PolicyDecisionPointInterface $pdp ){
		$this->pdp = $pdp;
		return $this;
	}

	public function getPDP(){
		return $this->pdp;
	}

	public function setObligationFactory( ObligationFactory $obl = NULL ){
		$this->obligationFactory = $obl;
		return $this;
	}

	public function getObligationFactory(){
		return $this->obligationFactory;
	}

	public function setDenyBiased(){
		$this->denyBiased = true;
		$this->permitBiased = false;
		return $this;
	}

	public function setPermitBiased(){
		$this->permitBiased = true;
		$this->denyBiased = false;
		return $this;
	}

	public function setNeutral(){
		$this->permitBiased = false;
		$this->denyBiased = false;
		return $this;
	}

	public function flushDecisionCache( $policyIdList = array() ){
		if( $this->pdp !== NULL ){
			$this->pdp->flushCache( $policyIdList );
		}
			
	}

	public function enforce( RequestInterface $request ){
		$pdp = $this->getPDP();

		try{
			if( $pdp === NULL )
				throw new \Exception( 'No PDP Specified' );
			$decision = $pdp->decide( $request );
		}catch( \Exception $e ){
			throw new \InvalidArgumentException( 'Invalid PDP specification or policy root syntax invalid', 0, $e );
		}

		$oblFactory = $this->getObligationFactory();
		if( $oblFactory !== NULL )
			$oblFactory->outputTo( $request );

		if( $decision->hasObligations() ){
			try{
				if( $oblFactory === NULL )
					throw new \Exception( 'no obligation library specified' );
				while( ( $oblSpec = $decision->getNextObligation() ) !== NULL ){
						$obl = $oblFactory
							->getInstance( $oblSpec );
						$obl->fulfill();
				}
			}catch( \Exception $e ){
				//spec says must return deny if there is an unfulfillable obligation
				$decision->isDeny( true );
				$decision->isStatusProcessingError( true );
				$decision->clearObligations();
				$decision->clearAdvice();
			}
		}

		if( $decision->hasAdvice() ){
			while( ( $advSpec = $decision->getNextAdvice() ) !== NULL ){
				try{
					if( $oblFactory !== NULL ){
						$obl = $oblFactory
							->getInstance( $advSpec, true );
						if( $obl !== NULL )
							$obl->fulfill();
					}
				}catch( \Exception $e ){
					//advice problems do not stop return or fulfillment of decision, but we would like to know about them
					trigger_error( $e->getMessage(), E_USER_WARNING );
				}
			}
		}

		if( $this->denyBiased && !$decision->isPermit() && !$decision->isDeny() ){
			$decision->isDeny( true );
			$decision->clearObligations();
			$decision->clearAdvice();
		}
		else if( $this->permitBiased && !$decision->isDeny() && !$decision->isPermit() ){
			$decision->isPermit( true );
			$decision->clearObligations();
			$decision->clearAdvice();
		}

		return $decision;
	}

}