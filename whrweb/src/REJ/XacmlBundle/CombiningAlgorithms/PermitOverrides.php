<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\CombiningAlgorithms\AbstractCombiner;

class PermitOverrides extends AbstractCombiner{

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
			return false;
		}
		else if( $d->isDeny() ){
			$this->hasDeny = true;
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
		if( !$this->hasPermit ){
			$this->hasPermit = true;
			if( $this->hasIndetDenyPermit
				|| ( $this->hasIndetPermit && ( $this->hasIndetDeny || $this->hasDeny ) ) ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateDP( true );
			}
			else if( $this->hasIndetPermit ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateP( true );
			}
			else if( $this->hasDeny ){
				$this->decision->isDeny( true );
				$this->decision->setAdvices( $this->advice );
				$this->decision->setObligations( $this->obligations );
			}
			else if( $this->hasIndetDeny ){
				$this->decision->isIndeterminate( true );
				$this->decision->isIndeterminateD( true );
			}
		}

		return $this->decision;
	}
}