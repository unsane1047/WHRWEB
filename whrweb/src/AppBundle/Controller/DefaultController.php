<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\FOSRestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends FOSRestController{
    /**
     * @Route("/", name="frontlanding")
     */
    public function indexAction(Request $request){
		$data = realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR;
		return $this->view( $data, 200 )
			->setTemplate( 'default/index.html.twig' )
			->setTemplateVar( 'base_dir' );
    }

}
