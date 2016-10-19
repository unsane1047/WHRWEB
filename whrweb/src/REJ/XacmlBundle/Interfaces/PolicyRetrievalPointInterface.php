<?php

namespace REJ\XacmlBundle\Interfaces;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;

interface PolicyRetrievalPointInterface{
	public function setPolicyStoreLocation( $locString );
	public function setCombiningAlgo( $algo = '' );
	public function find( RequestInterface $request );
	public function findSub( $url, $version, $earliestVersion, $latestVersion );
	public function getInline( XacmlReaderInterface $r );
}