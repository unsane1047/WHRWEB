<?php
namespace AppBundle\Security\Http\Logout;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class MessageSettingLogoutHandler implements LogoutHandlerInterface{
    /**
     * Set a flash message acknowledging logout.
     *
     * @param Request        $request
     * @param Response       $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token){
        $sess = $request->getSession();
		if( $sess !== NULL ){
			if( !$sess->isStarted() )
				$sess->start();
			$sess->getFlashBag()
				->add( 'success', 'logout.success' );
		}
    }
}