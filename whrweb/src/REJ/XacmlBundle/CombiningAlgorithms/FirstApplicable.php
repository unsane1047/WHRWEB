<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\CombiningAlgorithms\AbstractCombiner;

class FirstApplicable extends AbstractCombiner{

	protected function _createDecision(){
		$d = new Decision();
		$d->isNotApplicable( true );
		return $d;
	}

	public function combine( Decision $d ){

		if( !$d->isNotApplicable() ){
			$this->hasPermit = true;
			$this->decision->setDecision( $d->getDecision() );
			$this->decision->setStatus( $d->getStatus() );
			$this->decision->setExtendedIndeterminate( $d->getExtendedIndeterminate() );
			$this->decision->setAdvices( $d->getAdvice() );
			$this->decision->setObligations( $d->getObligations() );
			return false;
		}

		return true;
	}

	public function getDecision(){
		return $this->decision;
	}
}