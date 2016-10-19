<?php

namespace REJ\XacmlBundle\CombiningAlgorithms;

use REJ\XacmlBundle\Decision;

abstract class AbstractCombiner{
	protected $decision;
	protected $advice = array();
	protected $obligations = array();
	protected $isRuleCombiner = false;

	protected $hasDeny;
	protected $hasPermit;

	protected $hasIndetDeny;
	protected $hasIndetPermit;
	protected $hasIndetDenyPermit;
	protected $hasPotentialPermit;
	protected $hasPotentialDeny;

	public function __construct(){
		$this->reset();
	}

	public function reset(){
		$this->decision = $this->_createDecision();
		$this->hasDeny = false;
		$this->hasPermit = false;
		$this->hasIndetDeny = false;
		$this->hasIndetPermit = false;
		$this->hasIndetDenyPermit = false;
		$this->hasPotentialPermit = false;
		$this->hasPotentialDeny = false;
		$this->advice = array();
		$this->obligations = array();
	}

	public function isRuleCombiner(){
		$this->isRuleCombiner = true;
	}

	public function isPolicyCombiner(){
		$this->isRuleCombiner = false;
	}

	abstract protected function _createDecision();
	abstract public function combine( Decision $d );
	abstract public function getDecision();
}