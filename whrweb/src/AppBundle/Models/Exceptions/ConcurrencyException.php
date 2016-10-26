<?php
namespace AppBundle\Models\Exceptions;

class ConcurrencyException extends \Exception{
	function __construct(){
		return parent::__construct( 'model.concurrency.error' );
	}
}