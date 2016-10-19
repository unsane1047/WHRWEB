<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\CombiningAlgorithms\AbstractCombiner;

class DenyOverridesLegacy extends AbstractCombiner{

	protected function _createDecision(){
		$d = new Decision();
		$d->isNotApplicable( true );
		return $d;
	}

	public function combine( Decision $d ){

		if( $d->isDeny() ){
			$this->decision->setDecision( $d->getDecision() );
			$this->decision->setStatus( $d->getStatus() );
			$this->decision->setExtendedIndeterminate( $d->getExtendedIndeterminate() );
			$this->decision->setAdvices( $d->getAdvice() );
			$this->decision->setObligations( $d->getObligations() );
			$this->hasDeny = true;
		}
		else if( $d->isPermit() ){
			$this->hasPermit = true;
			$this->advice = array_merge( $this->advice, $d->getAdvice() );
			$this->obligations = array_merge( $this->obligations, $d->getObligations() );
		}
		else if( $d->isIndeterminate() ){

			if( !$this->isRuleCombiner ){
				$this->decision->isDeny( true );
				$this->hasDeny = true;
				return false;
			}

			$this->hasIndetDenyPermit = true;

			if( $d->isIndeterminateD() )
				$this->potentialDeny = true;
		}

		return true;
	}

	public function getDecision(){
		if( !$this->hasDeny ){
			$this->hasDeny = true;
			if( $this->potentialDeny ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateD( true );
			}
			else if( $this->hasPermit ){
				$this->decision->isPermit( true );
				$this->decision->setAdvices( $this->advice );
				$this->decision->setObligations( $this->obligations );
			}
			else if( $this->hasIndetDenyPermit ){
				$this->decision->isIndeterminate( true );
			}
		}
		return $this->decision;
	}
}