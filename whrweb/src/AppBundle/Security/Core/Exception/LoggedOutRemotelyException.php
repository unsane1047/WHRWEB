<?php
namespace AppBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class LoggedOutRemotelyException extends InsufficientAuthenticationException{
	public function getMessageKey(){
		return 'account.loggoutRemotely';
	}
}