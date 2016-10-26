<?php

namespace AppBundle\Security\Core\Authorization\Xacml\Voters;

//needs a way to indicate if secondary subject has been overridden by supervisor for uac function or if they are a developer using sudo

use REJ\XacmlBundle\Request as XacmlRequest;
use AppBundle\Models\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;

class PepVoter extends Voter{
	protected $env = 'prod';
	protected $pep = NULL;

	public function __construct( $env, $pep ){
		$this->env = $env;
		$this->pep = $pep;
	}

	protected function supports( $attribute, $subject ){
		if( !( $subject instanceOf XacmlRequest ) )
			return false;
		if( !is_array( $attribute ) )
			$attribute = array( $attribute );
		for( $i = 0; $i < count( $attribute ); $i++ ){
			if( !in_array( $attribute[ $i ], [ 'create', 'retrieve', 'update', 'delete', 'archive', 'prune' ] ) )
				return false;
		}
		return true;
	}

	protected function voteOnAttribute( $attribute, $subject, TokenInterface $token ){
		//add environment variables to the request automagically
		$subject->addCategory( $subject->createCategory( 'environment' ) )
			->addAttribute( 'environment', 'request_made', 'datetime', date( \DateTime::RFC3339 ) )
			->addAttribute( 'environment', 'application_mode', 'string', $this->env );

		//add action variables to the request automagically
		$subject->addCategory( $subject->createCategory( 'action' ) )
			->addAttribute( 'action', 'action', 'string', $attribute );

		//get access subject and secondary subject from login token
		$roles = $token->getRoles();
		$secondaryRoles = array();
		$accessSubject = $token->getUser();
		$secondarySubject = NULL;

		$tokenType = 'Anonymous';
		if( $token instanceOf UsernamePasswordToken )
			$tokenType = 'Authenticated';


		for( $i = 0; $i < count( $roles ); $i++ ){
			$r = $roles[ $i ];
			if( $r instanceOf SwitchUserRole )
				$secondarySubject = $r->getSource()->getUser();

			if( $r instanceOf RoleInterface )
				$r = $r->getRole();
			$roles[ $i ] = $r . '';
		}
		$subject->addCategory( $subject->createCategory( 'subject' ) )
			->addAttribute( 'subject', 'username', 'rfc822name', $token->getUsername() )
			->addAttribute( 'subject', 'roles', 'string', $roles )
			->addAttribute( 'subject', 'authenticated', 'boolean', $token->isAuthenticated() )
			->addAttribute( 'subject', 'tokentype', 'string', $tokenType );

		if( $accessSubject instanceOf User ){
			$subject->addAttribute( 'subject', 'id', 'string', $accessSubject->unbox()->id )
				->addAttribute( 'subject', 'enabled', 'boolean',  $accessSubject->isEnabled() )
				->addAttribute( 'subject', 'locked', 'boolean', $accessSubject->isAccountNonLocked() )
				->addAttribute( 'subject', 'credentialsExpired', 'boolean', !$accessSubject->isCredentialsNonExpired() )
				->addAttribute( 'subject', 'accountExpired', 'boolean', !$accessSubject->isAccountNonExpired() );
		}

		if( $secondarySubject instanceOf User ){
			$subject->addCategory( $subject->createCategory( 'recipient' )  )
				->addAttribute( 'recipient', 'username', 'rfc822name', $secondarySubject->getUsername() )
				->addAttribute( 'recipient', 'id', 'string', $secondarySubject->unbox()->id )
				->addAttribute( 'recipient', 'enabled', 'boolean',  $secondarySubject->isEnabled() )
				->addAttribute( 'recipient', 'locked', 'boolean', $secondarySubject->isAccountNonLocked() )
				->addAttribute( 'recipient', 'credentialsExpired', 'boolean', !$secondarySubject->isCredentialsNonExpired() )
				->addAttribute( 'recipient', 'accountExpired', 'boolean', !$secondarySubject->isAccountNonExpired() );
		}

		$decision = $this->pep
			->enforce( $subject );

		if( $decision->isPermit() )
			return true;

		return false;
	}
}