<?php

namespace AppBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class RequiresCaptchaException extends AuthenticationException {
	public function getMessageKey(){
		$mess = $this->getMessage();
		if( empty( $mess ) )
			$mess = 'captcha.required';
		return $mess;
	}
}