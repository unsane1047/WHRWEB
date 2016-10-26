<?php
namespace AppBundle\Models;

use \RedBeanPHP\BeanCollection;
use AppBundle\Models\Model;
use	AppBundle\Models\RedbeanService;

class Collection{
	$this->beanCollection;
	$this->service;

	function __construct( BeanCollection $collection, RedbeanService $service = NULL ){
		$this->beanCollection = $collection;
		$this->service = $service;
	}

	function setService( RedbeanService $service = NULL ){
		$this->service = $service;
	}

	function next(){
		$return = $this->beanCollection->next();
		if( $return !== NULL && $return->box() instanceOf Model && $this->service !== NULL )
			$return->box()->setService( $this->service );
		return $return;
	}

	function close(){
		$this->beanCollection->close();
		$this->beanCollection = NULL;
	}

}