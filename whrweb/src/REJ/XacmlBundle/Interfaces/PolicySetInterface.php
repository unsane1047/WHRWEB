<?php

namespace REJ\XacmlBundle\Interfaces;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\PolicyRetrievalPointInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\AbstractFunctionLibrary;

interface PolicySetInterface{
	public function __construct( XacmlReaderInterface $reader, PolicyRetrievalPointInterface $prp );
	public function getId();
	public function getVersion();
	public function getIdent();
	public function setParents( array $arr );
	public function setCachedDecisions( array &$decisionCache = array() );
	public function evaluate( RequestInterface $req, AbstractFunctionLibrary $funcLib = NULL );
}