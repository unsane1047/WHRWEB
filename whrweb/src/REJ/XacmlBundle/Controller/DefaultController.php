<?php

namespace REJ\XacmlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('REJXacmlBundle:Default:index.html.twig');
    }
}
