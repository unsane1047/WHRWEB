<?php

namespace AppBundle\Security\Core\User;

use Symfony\Component\Security\Core\Exception\CredentialsExpiredException,
	Symfony\Component\Security\Core\Exception\LockedException,
	Symfony\Component\Security\Core\Exception\DisabledException,
	Symfony\Component\Security\Core\Exception\AccountExpiredException,
	Symfony\Component\Security\Core\User\UserCheckerInterface,
	Symfony\Component\Security\Core\User\UserInterface,
	Symfony\Component\Security\Core\User\AdvancedUserInterface;

class AppUserChecker implements UserCheckerInterface{
	protected $expiaryPeriod = false;
	protected $passwordExpiaryPeriod = false;

	public function __construct( $config = array() ){
		if( isset( $config[ 'expiaryperiod' ] ) )
			$this->expiaryPeriod = $config[ 'expiaryperiod' ];
	}

	public function checkPreAuth( UserInterface $user ){
		return;
	}

	public function checkPostAuth( UserInterface $user ){
		if( !$user instanceof AdvancedUserInterface )
            return;
		if( !$user->isAccountNonLocked() ){
			$e = new LockedException();
			$e->setUser($user);
			throw $e;
        }
		if( !$user->isEnabled() ){
			$e = new DisabledException();
			$e->setUser($user);
			throw $e;
		}
		if( !$user->isAccountNonExpired( $this->expiaryPeriod ) ){
			$e = new AccountExpiredException();
			$e->setUser($user);
			throw $e;
		}
		if( !$user->isCredentialsNonExpired() ){
			$e = new CredentialsExpiredException();
			$e->setUser($user);
			throw $e;
		}
	}

}