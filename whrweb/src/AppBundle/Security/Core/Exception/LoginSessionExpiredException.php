<?php

namespace AppBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class LoginSessionExpiredException extends InsufficientAuthenticationException{
	public function getMessageKey(){
		return 'login.expired';
	}
}