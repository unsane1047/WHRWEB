<?php

namespace AppBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class IpLockoutException extends AuthenticationException {
	public function getMessageKey(){
		return 'login.iplockout';
	}
}