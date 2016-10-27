<?php
namespace AppBundle\FOSRestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController as Controller;
use AppBundle\FOSRestBundle\View;

class FOSRestController extends Controller{
    /**
     * Creates a view.
     *
     * Convenience method to allow for a fluent interface.
     *
     * @param mixed $data
     * @param int   $statusCode
     * @param array $headers
     *
     * @return View
     */
    protected function view($data = null, $statusCode = null, array $headers = [])
    {
        $return = View::create($data, $statusCode, $headers);
		$return->setTemplatingEngine( $this->get( 'fos_rest.templating' ) );
		$return->setLocale( $this->get( 'request_stack' )->getCurrentRequest()->getLocale() );
		return $return;
    }

    /**
     * Creates a Redirect view.
     *
     * Convenience method to allow for a fluent interface.
     *
     * @param string $url
     * @param int    $statusCode
     * @param array  $headers
     *
     * @return View
     */
    protected function redirectView($url, $statusCode = Response::HTTP_FOUND, array $headers = [])
    {
        $return = View::createRedirect($url, $statusCode, $headers);
		$return->setTemplatingEngine( $this->get( 'fos_rest.templating' ) );
		$return->setLocale( $this->get( 'request_stack' )->getCurrentRequest()->getLocale() );
		return $return;
    }

    /**
     * Creates a Route Redirect View.
     *
     * Convenience method to allow for a fluent interface.
     *
     * @param string $route
     * @param mixed  $parameters
     * @param int    $statusCode
     * @param array  $headers
     *
     * @return View
     */
    protected function routeRedirectView($route, array $parameters = [], $statusCode = Response::HTTP_CREATED, array $headers = [])
    {
        $return = View::createRouteRedirect($route, $parameters, $statusCode, $headers);
		$return->setTemplatingEngine( $this->get( 'fos_rest.templating' ) );
		$return->setLocale( $this->get( 'request_stack' )->getCurrentRequest()->getLocale() );
		return $return;
    }
}