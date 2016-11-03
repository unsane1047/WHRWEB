<?php

namespace AppBundle\Security\Core\Authentication\Provider;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\RequestStack;
use AppBundle\Security\Core\Exception\RequiresCaptchaException;
use AppBundle\Security\Core\Exception\IpLockoutException;
use AppBundle\Security\Captcha\CaptchaProvider;
use AppBundle\Models\RedbeanService;
use AppBundle\Models\User;

class WebappDaoAuthenticationProvider extends DaoAuthenticationProvider{
	protected $captchaProvider;
	protected $requestStack;
	protected $db;
	protected $userConfig;
	protected $sess;

	public function __construct( UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, EncoderFactoryInterface $encoderFactory, $hideUserNotFoundExceptions = true, RequestStack $req = NULL, RedbeanService $db = NULL, CaptchaProvider $cp = NULL, $user_config = array() ){
		$this->captchaProvider = $cp;
		$this->requestStack = $req;
		$this->db = $db;
		$this->userConfig = $user_config;

		parent::__construct( $userProvider, $userChecker, $providerKey, $encoderFactory, $hideUserNotFoundExceptions );
	}

	public function authenticate( TokenInterface $token ){
		$delay = 0;

		if( $this->db !== NULL ){
			if( $this->requestStack !== NULL )
				$currentIp = inet_pton( $this->requestStack->getCurrentRequest()->server->get( 'REMOTE_ADDR', '127.0.0.1' ) );
			else
				$currentIp = NULL;

			try{
				$e = NULL;

				self::loginWait(
					$this->db,
					$currentIp,
					$this->userConfig[ 'failedloginperiod' ],
					$this->userConfig[ 'logincaptchathreshold' ],
					$this->userConfig[ 'loginiplockoutthreshold' ],
					$this->userConfig[ 'loginbackoffstepdefinition' ]
				);
			}catch( AuthenticationException $e ){
				$e->setToken( $token );
				if( !( $e instanceOf RequiresCaptchaException ) )
					throw $e;
				else if( $this->captchaProvider !== NULL
						&& $this->captchaProvider->hasCompletedCaptcha()
				)
					$e = NULL;
			}

			if( $this->captchaProvider !== NULL && $this->requestStack !== NULL ){
				$response = $this->requestStack->getCurrentRequest()->get( 'authentication', array() );
				$response = ( ( isset( $response[ '_captcha' ] ) )? $response[ '_captcha' ]: NULL );

				if( $response !== NULL || $e !== NULL ){
					$captchaValid = false;

					if( $response != '' )
						$captchaValid = $this->captchaProvider->checkCaptcha( $response, $currentIp );

					if( $captchaValid === NULL || !$captchaValid ){
						$e = new RequiresCaptchaException( ( ( $captchaValid === NULL)? 'captcha.serverdown': '' ) );
						$e->setToken( $token );
						throw $e;
					}

				}
			}
		}

		return parent::authenticate( $token );
	}

	public static function loginWait( RedbeanService $db, $ip = NULL, $period = '15 minute', $captchaThreshold = 0, $ipLockoutThreshold = 0, array $backoffStepDefinition = [] ){
		$delay = 0;
		$failedAttemptsByIp = 0;
		$now = time();

		try{
			$failedAttempts = $db->getCell(
				'SELECT count(*) FROM `failedlogin` WHERE `attempted` > DATE_SUB(NOW(), INTERVAL '
				. $period . ')',
				[],
				User::$conn_id
			);
			if( $ip !== NULL && $ipLockoutThreshold > 0 )
				$failedAttemptsByIp = $db->getCell(
					'SELECT count(*) FROM `failedlogin` WHERE `ip_address` = ? AND `attempted` > DATE_SUB(NOW(), INTERVAL '
					. $period . ')',
					array( $ip )
					);
		}catch( \Exception $e ){
			$failedAttempts = 30; #default to slightly elevated if we failed for some reason so that there will be some default protection from cracking attempts
		}

		foreach( $backoffStepDefinition as $tmp ){
			$low = ( isset( $tmp[ 'low' ] ) )? $tmp[ 'low' ]: 0;
			$high = ( isset( $tmp[ 'high' ] ) )? $tmp[ 'high' ]: NULL;
			$d = ( isset( $tmp[ 'delay' ] ) )? $tmp[ 'delay' ]: 0;

			if( $failedAttempts > $low && ( $high === NULL || $failedAttempts <= $high ) ){
				$delay = $d;
				break;
			}
		}

		$remaining_delay = max( 0, $delay - ( $now - time() ) );

		if( $delay !== 0 )
			sleep( $delay );

		if( $ipLockoutThreshold > 0 && $failedAttemptsByIp > $ipLockoutThreshold )
			throw new IpLockoutException();

		if( $captchaThreshold > 0 && $delay >= $captchaThreshold )
			throw new RequiresCaptchaException();

		return;
	}

}