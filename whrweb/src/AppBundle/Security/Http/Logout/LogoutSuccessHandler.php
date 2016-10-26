<?php
namespace AppBundle\Security\Http\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler{
	protected $tok;

	public function __construct( HttpUtils $httpUtils, $targetUrl = '/', TokenStorageInterface $tok = NULL ){
		$this->tok = $tok;
		parent::__construct( $httpUtils, $targetUrl );
	}

	public function onLogoutSuccess( Request $request ){
		if( $this->tok !== NULL ){
			$user = $this->tok->getToken()->getUser();
			try{
				$login = $user->getLogin();
				if( $login !== NULL )
					$login->invalidate();
			}catch( \Exception $e ){}
		}

		return parent::onLogoutSuccess( $request );
	}
}