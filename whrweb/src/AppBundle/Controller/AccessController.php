<?php

namespace AppBundle\Controller;

//dependencies
use AppBundle\FOSRestBundle\Controller\FOSRestController;
use AppBundle\Forms\AuthenticationType;
use AppBundle\Security\Core\Authentication\Provider\WebappDaoAuthenticationProvider as AuthProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;

//exceptions
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use AppBundle\Security\Core\Exception\RequiresCaptchaException;

class AccessController extends FOSRestController{
	//token query parameters
	const ACCOUNT_TOKEN_QUERY_PARAM = '_accounttoken';

	//token reasons
	const ACCOUNT_REQUIRED_PASSWORD_RESET = 'requiredPasswordReset';
	const ACCOUNT_REENABLE = 'accountReenable';
	const ACCOUNT_CREATE = 'createAccount';
	const ACCOUNT_RECOVER = 'recoverAccount';
	const USERNAME_CHANGE = 'changeUsername';

	//token ttls
	const ACCOUNT_RECOVERY_TOKEN_TTL = 86400;
	const ACCOUNT_CREATION_TOKEN_TTL = 172800;
	const ACCOUNT_REQUIRED_PASSWORD_RESET_TOKEN_TTL = 8600;
	const ACCOUNT_DISABLED_TOKEN_TTL = 172800;

	/**
	 * @Route("/users/login", name="login")
	*/
	public function formLoginAction( Request $request ){

		$db = $this->get( 'app.redbean' );
		$authUtils = $this->get( 'security.authentication_utils' );
		$error = $authUtils->getLastAuthenticationError();
		$lastUsername = $authUtils->getLastUsername();
		$tokUtils = $this->get( 'app.security.tokenProvider' );
		$tok = NULL;

		if( $error instanceOf UsernameNotFoundException || $error instanceOf UnsupportedUserException )
			$error = new BadCredentialsException(); #hides these errors from the user for security reasons
		else if( $error instanceOf DisabledException ){
			$tok = (string)$tokUtils->getToken( $tokUtils->getBuilder( $request )
														->set( 'unm', $lastUsername )
														->set( 'rsn', self::ACCOUNT_REENABLE )
														->setExpiration( time() + self::ACCOUNT_DISABLED_TOKEN_TTL ) );
			$body = $this->renderView(
					'email/accountdisabled.txt.twig',
					[
						'email' => $lastUsername,
						'url' => $url = $this->generateUrl( 'accountrecover', [ self::ACCOUNT_TOKEN_QUERY_PARAM => $tok ] ),
						'validLength' => ( self::ACCOUNT_DISABLED_TOKEN_TTL - 600 )#set minus 10 minutes so that they will have time to process before token times out
					]
			);

			$message = \Swift_Message::newInstance()
				->setSubject( 'William Holland Account Services' )
				->setFrom( $this->getParameter( 'mailer_account_message_from_address' ) )
				->setTo( $lastUsername )
				->setBody( $this->renderView( 'email/markdown.html.twig', [ 'body' => $body ] ) , 'text/html' )
				->addPart( $body, 'text/plain' );

			try{
				$flag = $this->get( 'swiftmailer.mailer.instant' )->send( $message );
				if( $flag != 1 )
					throw new \Exception( 'message not sent' );
			}
			catch( \Exception $e ){
				$this->get( 'session' )->getFlashBag()
						->add( 'login_warning', 'email.unabletosend' );
			}
		}

		$siteKey = $this->getParameter( 'recaptcha' );
		$siteKey = ( isset( $siteKey[ 'sitekey' ] )? $siteKey[ 'sitekey' ]: NULL );
		$formParameters = [
			'action' => $this->generateUrl( 'login' ),
			'captcha' => ( $error instanceOf RequiresCaptchaException )
		];

		if( $siteKey !== NULL )
			$formParameters[ 'sitekey' ] = $siteKey;

		if( $error === NULL ){
			try{
				$currentIp = inet_pton( $request->server->get( 'REMOTE_ADDR', '127.0.0.1' ) );
				$config = $this->getParameter( 'userpolicies' );
				AuthProvider::loginWait(
					$db,
					$currentIp,
					$config[ 'failedloginperiod' ],
					$config[ 'logincaptchathreshold' ],
					$config[ 'loginiplockoutthreshold' ],
					$config[ 'loginbackoffstepdefinition' ]
				);
			}
			catch( RequiresCaptchaException $error ){
				$formParameters[ 'captcha' ] = true;
				$error = NULL;
			}
		}

		if( $this->get( 'app.security.captcha_provider' )->hasCompletedCaptcha()
			&& isset( $formParameters[ 'captcha' ] ) ){
			unset( $formParameters[ 'captcha' ] );
			$error = NULL;
		}

		if( $error instanceOf CredentialsExpiredException || $error instanceOf AccountExpiredException ){
			$tok = (string)$tokUtils->getToken( $tokUtils->getBuilder( $request )
														->set( 'unm', $lastUsername )
														->set( 'rsn', self::ACCOUNT_REQUIRED_PASSWORD_RESET )
														->setExpiration( time() + self::ACCOUNT_REQUIRED_PASSWORD_RESET_TOKEN_TTL ) );

			$view = $this->routeRedirectView(
				'accountrecover',
				[ self::ACCOUNT_TOKEN_QUERY_PARAM => $tok ],
				303
			);
		}
		else{
			$form = $this->createForm( AuthenticationType::class, NULL, $formParameters );
			$flash = $this->get( 'session' )->getFlashBag();

			if( $error instanceOf AuthenticationException ){
				$err = new FormError( $error->getMessageKey(), null, [], null, [ 'constraint' => [ 'payload' => [ 'severity' => 'error' ] ] ] );
				$form->addError( $err );
			}

			foreach( $flash->get( 'login_error' ) as $e ){
				$err = new FormError( $e, null, [], null, [ 'constraint' => [ 'payload' => [ 'severity' => 'error' ] ] ]);
				$form->addError( $err );
			}
			foreach( $flash->get( 'login_warning' ) as $e ){
				$err = new FormError( $e, null, [], null, [ 'constraint' => [ 'payload' => [ 'severity' => 'warning' ] ] ]);
				$form->addError( $err );
			}
			foreach( $flash->get( 'login_information' ) as $e ){
				$err = new FormError( $e, null, [], null, [ 'constraint' => [ 'payload' => [ 'severity' => 'information' ] ] ]);
				$form->addError( $err );
			}
			foreach( $flash->get( 'login_help' ) as $e ){
				$err = new FormError( $e, null, [], null, [ 'constraint' => [ 'payload' => [ 'severity' => 'help' ] ] ]);
				$form->addError( $err );
			}

			return $this->view( $form, ( ( $error instanceOf AuthenticationException )? 403: 200 ) )
				->setTemplate( 'authentication/login.html.twig' )
				->setTemplateVar( 'form' )
				->setTemplateData( [
						'last_username' => $lastUsername
				] );
		}

		if( $tok !== NULL ){
			$view = $this->handleView( $view );
			$view
				->headers
				->setCookie( new Cookie( self::ACCOUNT_TOKEN_QUERY_PARAM, $tok, new \DateTime( '+10 years' ), '/', NULL, $request->isSecure(), true ) );
		}

		return $view;

	}

	/**
	 * @Route("/users/create", name="createUser")
	*/
	public function createAccount( Request $request ){
		//if is a logged in user with rights to create an account then
			//create an account and assign a temporary password, send this temporary password in email to person account was created for
			//on their first login they will need to change password and set up security questions
			//or user may choose to simply send email as an invite as if the self service option had been used

		//if it is self service collect info and send email
			//when email gets back create account by adding password and security questions

		//in either option, need a way to detect if contacts is available and collect contact information if it is
	}

	/**
	 * @Route("/users/accountrecovery", name="accountrecover")
	*/
	public function accountRecoveryAction( Request $request ){
		//get token
		//extract token
		//get action from token
		//distribute to sub-handlers for each kind of token
	}
}