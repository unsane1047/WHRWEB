<?php 
namespace AppBundle\Security\Http\Authentication; 

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use AppBundle\Models\RedbeanService;
use AppBundle\Models\User;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler{

	protected $db;

	public function __construct( HttpKernelInterface $httpKernel, HttpUtils $httpUtils, array $options = array(), LoggerInterface $logger = NULL , RedbeanService $db = NULL ){
			$this->db = $db;
			parent::__construct( $httpKernel, $httpUtils, $options, $logger );
	}

	public function onAuthenticationFailure( Request $request, AuthenticationException $exception ){
		try{
			if( $this->db !== NULL ){
				$this->db->exec(
					'INSERT INTO `failedlogin` SET `username` = ?, `ip_address` = ?, `attempted` = ?',
					array(
						$exception->getToken()->getUsername(),
						inet_pton( $request->server->get( "REMOTE_ADDR", '127.0.0.1' ) ),
						date( 'Y-m-d H:i:s' )
					),
					User::$conn_id
				);
			}
		}catch( \Exception $e ){
			if( $this->logger !== NULL ){
				$this->logger
					->debug( 'failed login table not present in user database, unable to log failed login attempts or enforce failed login policies.',
							array( 'connection_id' => User::$conn_id )
					);
			}
		}
		return parent::onAuthenticationFailure( $request, $exception );
	}

}