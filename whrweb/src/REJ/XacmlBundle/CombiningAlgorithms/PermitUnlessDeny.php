<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\CombiningAlgorithms\AbstractCombiner;

class PermitUnlessDeny extends AbstractCombiner{
	protected function _createDecision(){
		$d = new Decision();
		$d->isPermit( true );
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
			$this->advice = array_merge( $this->advice, $d->getAdvice() );
			$this->obligations = array_merge( $this->obligations, $d->getObligations() );
		}

		return true;
	}

	public function getDecision(){
		if( !$this->hasDeny ){
			$this->hasDeny = true;
			$this->decision->setAdvices( $this->advice );
			$this->decision->setObligations( $this->obligations );
		}

		return $this->decision;
	}
}