<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\CombiningAlgorithms\AbstractCombiner;

class PermitOverridesLegacy extends AbstractCombiner{
	protected function _createDecision(){
		$d = new Decision();
		$d->isNotApplicable( true );
		return $d;
	}

	public function combine( Decision $d ){

		if( $d->isPermit() ){
			$this->decision->setDecision( $d->getDecision() );
			$this->decision->setStatus( $d->getStatus() );
			$this->decision->setExtendedIndeterminate( $d->getExtendedIndeterminate() );
			$this->decision->setAdvices( $d->getAdvice() );
			$this->decision->setObligations( $d->getObligations() );
			$this->hasPermit = true;
		}
		else if( $d->isDeny() ){
			$this->hasDeny = true;
			$this->advice = array_merge( $this->advice, $d->getAdvice() );
			$this->obligations = array_merge( $this->obligations, $d->getObligations() );
		}
		else if( $d->isIndeterminate() ){
			$this->hasIndetDenyPermit = true;
			if( $this->isRuleCombiner && $d->isIndeterminateP() )
				$this->potentialPermit = true;
		}

		return true;
	}

	public function getDecision(){
		if( !$this->hasPermit ){
			$this->hasPermit = true;
			if( $this->potentialPermit ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateP( true );
			}
			else if( $this->hasDeny ){
				$this->decision->isDeny( true );
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