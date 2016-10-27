<?php
namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface{
	private $defaultLocale;
	private $supportedLocales;

	public function __construct( $defaultLocale = 'en_US', $supportedLocales = [] ){
		$this->defaultLocale = $defaultLocale;
		if( count( $supportedLocales ) < 1 || !in_array( $defaultLocale, $supportedLocales, true ) )
			$supportedLocales[] = $defaultLocale;
		$this->supportedLocales = $supportedLocales;
	}

	public function onKernelRequest( GetResponseEvent $event ){
			$request = $event->getRequest();
			if( !$request->hasPreviousSession() ){
				if( $locale = $request->attributes->get( '_locale' ) )
					$request->setLocale( $locale );
				else
					$request->setLocale( $request->getPreferredLanguage( $this->supportedLocales ) );
				return;
			}

			$url_locale = $request->attributes->get( '_locale' );
			$session_locale = $request->getSession()->get( '_locale' );
			$accept_locale = $request->getPreferredLanguage( $this->supportedLocales );

			$locale = $this->defaultLocale;
			$localePriority = $request->getSession()->get( '_localePriority' );

			if( $session_locale ){
				if( $url_locale && $url_locale !== $session_locale ){
					$session_locale = $url_locale;
					$localePriority = 'url';
				}
				else if( $localePriority === 'accept' && $accept_locale && $accept_locale !== $session_locale )
					$session_locale = $accept_locale;

				$locale = $session_locale;
			}
			else if( $url_locale ){
				$locale = $url_locale;
				$localePriority = 'url';
			}
			else if( $accept_locale ){
				$locale = $accept_locale;
				$localePriority = 'accept';
			}
			
			$request->getSession()->set( '_locale', $locale );
			$request->getSession()->set( '_localePriority', $localePriority );
			$request->setLocale( $locale );
				
	}

	public function onKernelResponse( FilterResponseEvent $event ){
		$request = $event->getRequest();
		$response = $event->getResponse();
		$response->headers->set( 'Content-Language', $request->getLocale() );
	}

	public static function getSubscribedEvents(){
		return [
			KernelEvents::REQUEST => [ [ 'onKernelRequest', 15 ] ],
			KernelEvents::RESPONSE => [ [ 'onKernelResponse', 15 ] ],
		];
	}
}