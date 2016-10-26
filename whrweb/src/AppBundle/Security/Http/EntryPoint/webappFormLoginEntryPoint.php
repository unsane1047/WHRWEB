<?php

namespace AppBundle\Security\Http\EntryPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;

class webappFormLoginEntryPoint extends FormAuthenticationEntryPoint{
	public function start( Request $request, AuthenticationException $authException = NULL ){
		$sess = $request->getSession();
		if( $sess !== NULL && !$sess->isStarted() )
			$sess->start();
		if( $sess !== NULL && $sess->isStarted() ){
			if( $authException !== NULL )
				$sess->getFlashBag()->add( 'warning', $authException->getMessageKey() );
		}
		return parent::start( $request, $authException );
	}
}