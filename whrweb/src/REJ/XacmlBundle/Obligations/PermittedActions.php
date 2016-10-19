<?php
namespace REJ\XacmlBundle\Obligations;

use REJ\XacmlBundle\Obligations\AbstractObligation;

class PermittedActions extends AbstractObligation{
	protected $minArgs = 1;
	protected $maxArgs = 1;

	public function fulfill(){
		if( $this->outputToRequest === NULL )
			return;
		$this->outputToRequest->setNewAdviceOutput( 'permittedActions', explode( ',', reset( $this->arguments[ 'permitted' ] ) ) );
	}
}