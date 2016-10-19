<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\CombiningAlgorithms\AbstractCombiner;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;

class OnlyOneApplicable extends AbstractCombiner{
	protected $hasSelected;

	protected function _createDecision(){
		$d = new Decision();
		$d->isNotApplicable( true );
		return $d;
	}

	public function reset(){
		$this->hasSelected = false;
		parent::reset();
	}

	public function isRuleCombiner(){
		throw new ProcessingErrorException( 'OnlyOneApplicable combining algorithm works for Policy combining only' );
	}

	public function combine( Decision $d ){

		if( $d->targetWasIndeterminate() ){
			$this->decision->setDecision( $d->getDecision() );
			$this->decision->setStatus( $d->getStatus() );
			$this->decision->setExtendedIndeterminate( $d->getExtendedIndeterminate() );
			$this->decision->setAdvices( $d->getAdvice() );
			$this->decision->setObligations( $d->getObligations() );
			$this->selected = true;
			return false;
		}
		else if( !$d->isNotApplicable() ){
			if( $this->selected ){
				$this->decision->isIndeterminate( true );
				return false;
			}

			$this->selected = true;
			$this->decision->setDecision( $d->getDecision() );
			$this->decision->setStatus( $d->getStatus() );
			$this->decision->setExtendedIndeterminate( $d->getExtendedIndeterminate() );
			$this->decision->setAdvices( $d->getAdvice() );
			$this->decision->setObligations( $d->getObligations() );
		}

		return true;
	}

	public function getDecision(){
		return $this->decision;
	}
}