<?php
namespace AppBundle\FOSRestBundle;

use FOS\RestBundle\View\View as FOSView;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Symfony\Component\Templating\TemplateNameParser;

class View extends FOSView{
	protected $templating;
	protected $locale;

	public function setTemplatingEngine( EngineInterface $templating = NULL ){
		$this->templating = $templating;
	}

	public function setLocale( $locale ){
		$this->locale = $locale;
	}
	
	public function setTemplate( $template ){
        if( !( is_string( $template ) || $template instanceof TemplateReferenceInterface ) )
            throw new \InvalidArgumentException('The template should be a string or implement TemplateReferenceInterface');

		$engine = $this->getEngine();
		$locale = $this->locale;

		if( $template instanceOf TemplateReferenceInterface ){
			$tmp = $template->get( 'engine' );
			if( $tmp !== NULL )
				$engine = $tmp;
			$templatename = $template->getLogicalName();
		}
		else{
			$templatename = ( new TemplateNameParser() )->parse( $template );
			if( $engine === NULL )
				$engine = $templatename->get( 'engine' );
			$templatename = $templatename->getLogicalName();
		}

		if( ( $pos = strrpos( $templatename, '.' ) ) !== false )
			$templatename = substr( $templatename, 0, $pos );

		$localizedTemplate = sprintf( '%s.%s.%s', $templatename, $locale, $engine );

		if( $this->templating->exists( $localizedTemplate ) ){
			if( $template instanceOf TemplateReferenceInterface ){
				$template->set( 'name', sprintf( '%s.%s', $templatename, $locale ) );
			}
			else
				$template = $localizedTemplate;
		}

        return parent::setTemplate( $template );
	}
}