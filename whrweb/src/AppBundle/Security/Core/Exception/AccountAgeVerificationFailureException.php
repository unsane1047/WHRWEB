<?php

namespace AppBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AccountAgeVerificationFailureException extends AuthenticationException {
	public function getMessageKey(){
		return 'user.too_young';
	}
}