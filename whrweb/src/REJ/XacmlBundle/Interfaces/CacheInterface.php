<?php

namespace REJ\XacmlBundle\Interfaces;

use REJ\XacmlBundle\Interfaces\RequestInterface;

interface CacheInterface{
	public function hasMatch( RequestInterface $r );
	public function addItem( RequestInterface $r, $d );
	public function fetchItem( RequestInterface $r );
}