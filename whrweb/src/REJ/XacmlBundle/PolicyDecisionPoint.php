<?php

namespace REJ\XacmlBundle;

use REJ\XacmlBundle\Interfaces\PolicyDecisionPointInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Interfaces\PolicyRetrievalPointInterface;
use REJ\XacmlBundle\Interfaces\CacheInterface;
use REJ\XacmlBundle\RetrievalPoints\Streaming as StreamingPRP;
use REJ\XacmlBundle\Decision;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\PolicyNotFoundException;

class PolicyDecisionPoint implements PolicyDecisionPointInterface{
	protected $PRP;
	protected $debug;
	protected $cache;

	public function __construct( PolicyRetrievalPointInterface $p = NULL ){
		$this->setPRP( $p );
	}

	public function setDecisionCache( CacheInterface $d = NULL ){
		$this->cache = $d;
	}

	public function getCache(){
		return $this->cache;
	}

	public function flushCache( $policyIdList = array() ){
		if( $this->cache !== NULL ){
			$this->cache->flush();
		}
	}

	public function setPRP( PolicyRetrievalPointInterface $p = NULL ){
		$this->PRP = $p;
		return $this;
	}

	public function getPRP(){
		return $this->PRP;
	}

	public function setDebug( $set = true ){
		$this->debug = $set;
		return $this;
	}

	public function decide( RequestInterface $request ){
		$cache = $this->getCache();

		try{
			$env = $request->getAttribute( 'environment', 'application_mode' );
			$env = $env->getValue();
			if( reset( $env ) === 'dev' )
				$cache = NULL;
		}catch( \Exception $e ){}

		if( $cache === NULL || !$cache->hasMatch( $request ) ){

			libxml_use_internal_errors( true );
			libxml_clear_errors();

			try{
				$root = $this->findPolicy( $request );
				$decision = $root->evaluate( $request );
			}
			catch( PolicyNotFoundException $e ){
				$decision = new Decision();
				$decision->isNotApplicable( true );
				$decision->isStatusOk( true );
				$decision->setStatusMessage( 'unable to locate an applicable policy' );
			}
			catch( SyntaxErrorException $e ){
				$decision = new Decision();
				$decision->isIndeterminate( true );
				$decision->isStatusSyntaxError( true );
				$decision->setStatusMessage( sprintf( 'Syntax error in policy root: %s.', $e->getMessage() ) );
			}
			catch( \Exception $e ){
				$decision = new Decision();
				$decision->isIndeterminate( true );
				$decision->isStatusProcessingError( true );
				$decision->setStatusMessage( sprintf( 'Processing error in policy root: %s.', $e->getMessage() ) );
			}

			libxml_clear_errors();
			libxml_use_internal_errors();

			if( $decision->isStatusProcessingError() && !$this->debug ){
				//prohibited from returning any information for security purposes according to spec
				$decision->setStatusMessage( '' );
				$decision->setStatusDetail( '' );
			}
			else if( ( $decision->isStatusOk() || $decision->isStatusSyntaxError() ) && !$this->debug ){
				//prohibited from returning detail per spec
				$decision->setStatusDetail();
			}

			if( $cache !== NULL && !$decision->isStatusError() )
				$cache->addItem( $request, $decision );
		}
		else
			$decision = $cache->fetchItem( $request );

		return $decision;
	}

	protected function findPolicy( RequestInterface $request ){
		if( $this->getPRP() === NULL )
			$this->setPRP( new StreamingPRP() );
		try{
			$policy = $this->getPRP()->find( $request );
		}catch( PolicyNotFoundException $e ){
			throw $e;
		}catch( \Exception $e ){
			throw new SyntaxErrorException( sprintf( 'Unreadable Policy or Policy Syntax Error: %s', $e->getMessage() ), 0, $e );
		}

		return $policy;
	}

}