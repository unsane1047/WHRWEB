<?php
namespace AppBundle\Security\Core\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use AppBundle\Security\Core\Exception\LoggedOutRemotelyException;
use AppBundle\Models\User;
use AppBundle\Models\RedbeanService;

class MariaDBUserProvider implements UserProviderInterface{
	private $db;

	public function __construct( RedbeanService $db ){
		$this->db = $db;
	}

	public function loadUserByUsername( $username ){
		$user = $this->db->loadByField( 'user', 'username', $username, true );
		$user = reset( $user );
		$user = $user->box();
		if( $user instanceOf User && $user->unbox()->id != 0 )
			return $user;
		throw new UsernameNotFoundException();
	}

	public function refreshUser( UserInterface $user ){
		if( !( $user instanceof User ) ){
			throw new UnsupportedUserException(
				sprintf( 'Instances of "%s" are not supported.', get_class( $user ) )
			);
		}

		$user->setService( $this->db );

		$tmp = $this->db->load( 'user', $user->unbox()->id, true );
		$tmp = reset( $tmp );

		if( $tmp->id == 0 ){
			$e = new UsernameNotFoundException();
			$e->setUsername( $user->username );
			throw $e;
		}

		$tmp = $tmp->box();

		if( !$tmp->isEqualTo( $user ) ){
			$tmp->copyModel( $user );
			$user = $tmp;
		}

		if( !$user->isAccountNonLocked() ){
			$e = new LockedException();
			$e->setUser( $user );
			throw $e;
		}
		else if( !$user->isEnabled() ){
			$e = new DisabledException();
			$e->setUser( $user );
			throw $e;
		}

		$login = $user->getLogin();

		if( $login !== NULL ){
			$tmp = $login->unbox()->fresh()->box();
			$tmp->copyModel( $login );
			$login = $tmp;
			$user->setLogin( $login );
		}

		if( $login === NULL || $login->unbox()->invalidated == 1 )
			throw new LoggedOutRemotelyException();

		return $user;
	}

	public function supportsClass( $class ){
		return $class === 'AppBundle\Models\User';
	}

}