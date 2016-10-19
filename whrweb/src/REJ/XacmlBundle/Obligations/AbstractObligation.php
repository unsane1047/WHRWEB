<?php
namespace REJ\XacmlBundle\Obligations;

use \InvalidArgumentException;

abstract class AbstractObligation{
	protected $isAdvice;
	protected $arguments;
	protected $minArgs;
	protected $maxArgs;
	protected $outputToRequest = NULL;

	public function __construct( $arguments, $isManditory = false ){
		$this->outputToRequest = array_pop( $arguments );
		$c = count( $arguments );

		if( !empty( $this->minArgs ) && $c < $this->minArgs
			|| !empty( $this->maxArgs ) && $c > $this->maxArgs
		)
			throw new InvalidArgumentException( 'wrong number of arguments' );

		$this->isAdvice = ( !$isManditory );
		$this->arguments = $arguments;
	}

	abstract public function fulfill();

}