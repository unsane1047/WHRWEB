<?php

namespace AppBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidResetTokenException extends AuthenticationException {
	public function getMessageKey(){
		return 'token.invalid.createtoken';
	}
}