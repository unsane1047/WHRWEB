<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\CombiningAlgorithms\AbstractCombiner;

class DenyOverrides extends AbstractCombiner{

	protected function _createDecision(){
		$d = new Decision();
		$d->isNotApplicable( true );
		return $d;
	}

	public function combine( Decision $d ){
		if( $d->isDeny() ){
			$this->hasDeny = true;
			$this->decision->setDecision( $d->getDecision() );
			$this->decision->setStatus( $d->getStatus() );
			$this->decision->setExtendedIndeterminate( $d->getExtendedIndeterminate() );
			$this->decision->setAdvices( $d->getAdvice() );
			$this->decision->setObligations( $d->getObligations() );
			return false;
		}
		else if( $d->isPermit() ){
			$this->hasPermit = true;
			$this->advice = array_merge( $this->advice, $d->getAdvice() );
			$this->obligations = array_merge( $this->obligations, $d->getObligations() );
		}
		else if( $d->isIndeterminate() ){
			if( $d->isIndeterminateD() )
				$this->hasIndetDeny = true;
			else if( $d->isIndeterminateP() )
				$this->hasIndetPermit = true;
			else
				$this->hasIndetDenyPermit = true;
		}

		return true;
	}

	public function getDecision(){
		if( !$this->hasDeny ){
			$this->hasDeny = true;
			if( $this->hasIndetDenyPermit
				|| ( $this->hasIndetDeny && ( $this->hasIndetPermit || $this->hasPermit ) ) ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateDP( true );
			}
			else if( $this->hasIndetDeny ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateD( true );
			}
			else if( $this->hasPermit ){
				$this->decision->isPermit( true );
				$this->decision->setAdvices( $this->advice );
				$this->decision->setObligations( $this->obligations );
			}
			else if( $this->hasIndetPermit ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateP( true );
			}
		}

		return $this->decision;
	}
}