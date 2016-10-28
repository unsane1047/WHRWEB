<?php
namespace AppBundle\Security\JWT;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class JWT_JWSOneTimeTokenProvider {
	private $secret;
	private $requestStack;
	private $usedAfterLength;
	private $expiresLength;

	public function __construct( $secret, RequestStack $requestStack = NULL, $usedAfterLength = 60, $expiresLength = 3600 ){
		$this->secret = $secret;
		$this->requestStack = $requestStack;
		$this->usedAfterLength = $usedAfterLength;
		$this->expiresLength = $expiresLength;
	}

	public function getBuilder( Request $request = NULL ){
		$issuedBy = 'http://www.example.com';
		if( $request !== NULL )
			$issuedBy = $request->getHost();
		else if( $this->requestStack !== NULL && $this->requestStack->getCurrentRequest() )
			$issuedBy = $this->requestStack->getCurrentRequest()->getHost();
		$now = time();
		$builder = ( new Builder() )->setIssuer( $issuedBy )
									->setAudience( $issuedBy )
									->setId( uniqid( rand() ), true )
									->setIssuedAt( $now )
									->setNotBefore( $now + $this->usedAfterLength )
									->setExpiration( time() + $this->expiresLength );
		return $builder;
	}

	public function getToken( Builder $builder ){
		$signer = new Sha256();
		return $builder->sign( $signer, $this->secret )
						->getToken();
	}

	public function parseToken( $token ){
		return ( new Parser() )->parse( (string) $token );
	}

	public function getDataValidator( Request $request = NULL ){
		$issuedBy = 'http://www.example.com';
		if( $request !== NULL )
			$issuedBy = $request->getHost();
		else if( $this->requestStack !== NULL && $this->requestStack->getCurrentRequest() )
			$issuedBy = $this->requestStack->getCurrentRequest()->getHost();
		$data = ( new ValidationData() );
		$data->setIssuer( $issuedBy );
		$data->setAudience( $issuedBy );
		return $data;
	}

	public function verifyToken( $token ){
		$signer = new Sha256();
		$token = $this->parseToken( $token );
		return $token->verify( $signer, $this->secret );
	}

}