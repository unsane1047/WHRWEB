<?php

namespace REJ\XacmlBundle\Cache;

use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Interfaces\DecisionInterface;
use REJ\XacmlBundle\Interfaces\CacheInterface;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class DecisionCache implements CacheInterface{
	private $lastMatch;
	private $lastId;

	function __construct( $namespace = 'REJ/XacmlBundle', $defaultTTL = 1, $cacheDir = NULL ){
		$this->cache = new FilesystemAdapter( $namespace, $defaultTTL, $cacheDir );
		$this->lastId = NULL;
		$this->lastMatch = NULL;
	}

	protected function getId( RequestInterface $r ){
		$id = $r->getCacheId();
		$id = hash( 'sha256', $id );

        if( '' === $id
			|| ( ( strlen( $id ) * 2 + $this->extStrLen ) > 255 )
			|| ( $this->isWin && ( $this->dirStrLen + 4 + strlen( $id ) * 2 + $this->extStrLen) > 258 ) )
			$id = '_' . $hash;
		else
			$id = bin2hex( $id );

		return $id;
	}

	public function hasMatch( RequestInterface $r ){
		$id = $this->getId( $r );

		$item = $this->cache->getItem( $id );

		if( $item->isHit() ){
			$this->lastMatch = $item->get();
			$this->lastId = $item->getKey();
			return true;
		}

		return false;
	}

	public function addItem( RequestInterface $r, $d ){
		$lifetime = $r->getCacheLifetime();
		if( $lifetime < 0 ) //no need to write this file because it will never be used
			return true;
		$id = $this->getId( $r );
		$item = $this->cache->getItem( $id );
		$item->set( $d );
		$item->expiresAfter( $lifetime );
		return $this->cache->save( $item );
	}

	public function fetchItem( RequestInterface $r ){
		$id = $this->getId( $r );

		if( $id !== $this->lastId ){
			$item = $this->cache->getItem( $id );
			if( !$item->isHit() )
				return false;
			$this->lastMatch = $item->get();
			$this->lastId = $item->getKey();
		}

		return $this->lastMatch;
	}

}