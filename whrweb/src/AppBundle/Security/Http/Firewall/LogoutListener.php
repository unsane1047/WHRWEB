<?php
namespace AppBundle\Security\Http\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Firewall\LogoutListener as LL;

class LogoutListener extends LL{

	protected function requiresLogout( Request $request ){
		$return = parent::requiresLogout( $request );
		if( $return && !$request->isMethod( 'POST' ) )
			throw new LogoutException( 'logout.post_only' );
		return $return;
	}

}