<?php

namespace REJ\XacmlBundle\Interfaces;

use REJ\XacmlBundle\Interfaces\PolicyRetrievalPointInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;

interface PolicyDecisionPointInterface{
	//set up dependencies
	public function setPRP( PolicyRetrievalPointInterface $p = NULL );
	public function getPRP();

	//do decision process
	public function decide( RequestInterface $request );
}