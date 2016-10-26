<?php

namespace AppBundle\Security\Captcha;

use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class CaptchaProvider{

	private $siteKey;
	private $secretKey;
	private $sessionVar;
	private $apiUrl;
	private $lastErrorCodes = array();
	private $sess;

	public function __construct( array $params = array(), Session $sess = NULL ){
		$this->siteKey = $params[ 'sitekey' ];
		$this->secretKey = $params[ 'secretkey' ];
		$this->apiUrl = $params[ 'api' ];
		$this->sessionVar = $params[ 'session_var' ];
		$this->sess = $sess;
		if( empty( $params[ 'session_var' ] ) )
			$this->sess = NULL;
	}

	public function getSessionVar(){
		return $this->sessionVar;
	}

	public function getApiUrl(){
		return $this->apiUrl;
	}

	public function getLastErrorCodes(){
		$codes = $this->lastErrorCodes;
		$this->lastErrorCodes = [];
		return $codes;
	}

	public function getSession(){
		return $this->sess;
	}

	public function checkCaptcha( $response, $remoteIp ){
		$checker = new ReCaptcha( $this->secretKey );
		$resp = $checker->verify( $response, $remoteIp );

		if( $resp->isSuccess() ){
			$this->hasCompletedCaptcha( true );
			return true;
		}

		$this->removeCompletedCaptcha();
		$this->lastErrorCodes = $resp->getErrorCodes();
		return false;

	}

	public function hasCompletedCaptcha( $set = false ){
		$sess = $this->getSession();
		if( $sess === NULL )
			return false;
		else if( $set !== false )
			$sess->set( $this->sessionVar, true );
		return $sess->has( $this->sessionVar );
	}

	public function removeCompletedCaptcha(){
		$sess = $this->getSession();
		if( $sess === NULL )
			return;
		return $sess->remove( $this->sessionVar );
	}

}