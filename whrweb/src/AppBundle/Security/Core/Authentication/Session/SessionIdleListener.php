<?php

namespace AppBundle\Security\Core\Authentication\Session;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use AppBundle\Security\Core\Exception\LoginSessionExpiredException;


class SessionIdleListener implements EventSubscriberInterface{
	use TargetPathTrait;

	protected $timeout = 3600;
	protected $tokStor;
	protected $secret;
	protected $providerKey;
	protected $authenticationEntryPoint;

	public function __construct( TokenStorageInterface $tokStor, AuthenticationEntryPointInterface $authenticationEntryPoint = NULL, $providerKey = 'main', $secret = 'none', $timeoutDivisor = 2 ){
		$this->tokStor = $tokStor;
		$this->authenticationEntryPoint = $authenticationEntryPoint;
		$this->providerKey = $providerKey;
		$this->secret = $secret;
		$this->timeout = ini_get( 'session.gc_maxlifetime' ) / $timeoutDivisor;
	}

	public function onKernelRequest( GetResponseEvent $event ){
		$request = $event->getRequest();

		if( !$request->hasPreviousSession() )
			return;

		$sess = $request->getSession();

		if( !$sess->isStarted() )
			return;

		$now = time();
		$sessMeta = $sess->getMetadataBag();
		$tok = $this->tokStor->getToken();

		if(
			$now > ( $sessMeta->getLastUsed() + $this->timeout )
			&& $tok !== NULL
			&& is_callable( [$tok, 'getProviderKey'] )
			&& $tok->getProviderKey() === $this->providerKey
		){
			//login timeout reached
			try{
				//clear the token to avoid problems with any other listeners or parts of symfony
				$tok = new AnonymousToken( $this->secret, 'anon.', array() );
				$this->tokStor->setToken( $tok );
				$sess->migrate( true );

				$e = new LoginSessionExpiredException();
				$e->setToken( $tok );

				if( $this->authenticationEntryPoint === NULL )
					throw new AccessDeniedException();

				if( $request->hasSession() && $request->isMethodSafe() && !$request->isXmlHttpRequest() )
					$this->saveTargetPath( $sess, $this->providerKey, $request->getUri() );

				$response = $this->authenticationEntryPoint->start( $request, $e );

				if( !( $response instanceof Response ) ){
					$given = is_object( $response )? get_class( $response ) : gettype( $response );
					throw new \LogicException( sprintf( 'The %s::start() method must return a Response object (%s returned)', get_class( $this->authenticationEntryPoint ), $given ) );
				}

				$event->setResponse( $response );
			}
			catch( \Exception $e ){
				$event->setException($e);
			}
		}

		return;
	}

	public static function getSubscribedEvents(){
		return [
			KernelEvents::REQUEST => [['onKernelRequest', 65]] //priority may need to change
		];
	}
}