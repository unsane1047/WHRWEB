<?php

namespace AppBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AccountExistsException extends AuthenticationException {
	public function getMessageKey(){
		return 'user.create.account.exists';
	}
}