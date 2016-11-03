<?php
namespace AppBundle\Security\Http\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use AppBundle\Libraries\URL;
use AppBundle\Libraries\AppDateTime;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler{
	public function onAuthenticationSuccess( Request $request, TokenInterface $token ){
		$user = $token->getUser();
		$login = $user->getLogin();
		try{
			if( $login === NULL ){
				$login = $user->getService()->create( 'login' );
				$login = reset( $login );
				$login->user_id = $user->unbox()->id;
				$login->username = $user->getUsername();
				$login->ip_address = inet_pton( $request->server->get( "REMOTE_ADDR", '127.0.0.1' ) );
				$login->attempted = new AppDateTime();
				$login->invalidated = false;
				$login->getService()->begin();
					$login->persist();
				$login->getService()->commit();
				$user->setLogin( $login->box() );
			}
		}catch( \Exception $e ){
			$user->getService()->rollback();
			throw $e;
		}

		$sess = $request->getSession();
		if( $sess->isStarted() )
			$sess->getFlashBag()->add( 'success', 'login.successful' );

		return parent::onAuthenticationSuccess( $request, $token );
	}

	public function determineTargetUrl( Request $request ){
		$return = parent::determineTargetUrl( $request );
		$tmp = new URL( $return );
		$login = new URL( $this->httpUtils->generateUri( $request, $this->options[ 'login_path' ] ) );
		if( $login->host === $tmp->host
			&& $login->port === $tmp->port
			&& $login->path === $tmp->path
			&& $login->query === $tmp->query )
			$return = $this->options[ 'default_target_path' ];

		return $return;
	}
}