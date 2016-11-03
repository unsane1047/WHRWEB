<?php
namespace AppBundle\FOSRestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController as Controller;
use AppBundle\FOSRestBundle\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Symfony\Component\Templating\TemplateNameParser;

class FOSRestController extends Controller{
	protected function localizeView( $view ){
		if( is_array( $view ) ){
			$ret = [];
			foreach( $view as $i => $c )
				$ret[ $i ] = $this->localizeView( $c );
		}

		$locale = $this->get( 'request_stack' )->getCurrentRequest()->getLocale();
		$type = 'templating';
		$engine = NULL;

		if( $this->container->has( 'templating' ) ){
			$templating = $this->container->get( 'templating' );
			if( !( is_string( $view ) || $view instanceof TemplateReferenceInterface ) )
				return $view;
		}
		else if( $this->container->has( 'twig' ) ){
			$templating = $this->container->get( 'twig' );
			$engine = 'twig';
			$type = 'twig';
		}
		else
			return $view;

		
		if( $view instanceOf TemplateReferenceInterface ){
			$tmp = $view->get( 'engine' );
			if( $tmp !== NULL )
				$engine = $tmp;
			$viewname = $view->getLogicalName();
		}
		else{
			if( $view instanceOf \Twig_Template ){
				$viewname = $view->getTemplateName();
				$viewname = ( new TemplateNameParser() )->parse( $viewname );
			}
			else
				$viewname = ( new TemplateNameParser() )->parse( $view );

			if( $engine === NULL )
				$engine = $viewname->get( 'engine' );
			$viewname = $viewname->getLogicalName();
		}

		if( ( $pos = strrpos( $viewname, '.' ) ) !== false )
			$viewname = substr( $viewname, 0, $pos );

		$localizedView = sprintf( '%s.%s.%s', $viewname, $locale, $engine );

		if( $type === 'templating' ){
			if( $templating->exists( $localizedView ) ){
				if( $view instanceOf TemplateReferenceInterface )
					$view->set( 'name', sprintf( '%s.%s', $viewname, $locale ) );
				else
					$view = $localizedView;
			}
		}
		else{
			try{
				$tmp = $templating->resolveTemplate( array( $localizedView, $view ) );
				$view = $tmp;
			}
			catch( \Exception $e ){
				return $view;
			}
		}
		return $view;
	}

	protected function stream( $view, array $parameters = [], StreamedResponse $response = NULL ){
		$view = $this->localizeView( $view );
		return parent::stream( $view, $parameters, $response );
	}

	protected function render( $view, array $parameters = [], Response $response = NULL ){
		$view = $this->localizeView( $view );
		return parent::render( $view, $parameters, $response );
	}

	protected function renderView( $view, array $parameters = [] ){
		$view = $this->localizeView( $view );
		return parent::renderView( $view, $parameters );
	}

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
    protected function view( $data = null, $statusCode = null, array $headers = [] ){
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
    protected function redirectView( $url, $statusCode = Response::HTTP_FOUND, array $headers = [] ){
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
    protected function routeRedirectView( $route, array $parameters = [], $statusCode = Response::HTTP_CREATED, array $headers = [] ){
        $return = View::createRouteRedirect($route, $parameters, $statusCode, $headers);
		$return->setTemplatingEngine( $this->get( 'fos_rest.templating' ) );
		$return->setLocale( $this->get( 'request_stack' )->getCurrentRequest()->getLocale() );
		return $return;
    }
}