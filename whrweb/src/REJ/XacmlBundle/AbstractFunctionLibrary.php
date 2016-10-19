<?php

namespace REJ\XacmlBundle;

use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;

abstract class AbstractFunctionLibrary{
	const DoubleEqualEpsilon = 0.00001;

	protected $map = [];
	protected $version = '0.0';
	protected $observer = NULL;

	public function registerObserver( $o ){
		if( is_callable( $o, 'observeCacheEffectingFunction' ) )
			$this->observer = $o;
	}
	
	/* returns true or false,
		may throw	ProcessingErrorException
					SyntaxErrorException
					IndeterminateResultException
		true if any combination of attr1 and an attribute in attr2 is true,
		otherwise if at least one
		indeterminate, then throw indeterminateresultexception,
		otherwise return false
	*/
	public function match( $op, Attribute $attr1, Attribute $attr2 ){
		$this->returns( $op, Attribute::BOOLEAN );

		try{
			$ret = $this->callFunction( $op, 2, $attr1, $attr2 );
			$val = $ret->getValue();

			if( count( $val ) < 1 )
				throw new IndeterminateResultException( 'match function results in empty bag' );
			else if( in_array( 'true', $val, true ) )
				return true;
		}catch( IndeterminateResultException $e ){
			throw $e;
		}catch( SyntaxErrorException $e ){
			throw $e;
		}catch( \Exception $e ){
			throw new ProcessingErrorException( 'unable to process match', 0, $e );
		}

		return false;
	}

	/* returns an attribute 
		may throw	ProcessingErrorException 
					SyntaxErrorException
					IndeterminateResultException
	$arguments should be Attributes or IndeterminateResultException
	*/
	public function apply( $op, array $arguments ){
		try{
			$ret = $this->callFunction( $op, count( $arguments), ...$arguments );
		}catch( IndeterminateResultException $e ){
			throw $e;
		}catch( SyntaxErrorException $e ){
			throw $e;
		}catch( \Exception $e ){
			throw new ProcessingErrorException( 'unable to process apply', 0, $e );
		}

		return $ret;
	}

	protected function callFunction( $op, $numArgs, ...$arguments ){
		$this->isMapped( $op );
		$this->takesArgs( $op, $numArgs );
		$this->isDeprecated( $op );

		$func = $this->map[ $op ];

		foreach( $arguments as $i => $arg ){
			if( !isset( $func[ 'passIndeterminate' ] ) && $arg instanceOf IndeterminateResultException )
				throw new IndeterminateResultException( sprintf( 'Passed argument to %s is indeterminate', $op ), 0, $arg );

			if( isset( $func[ 'argSpec' ] ) ){

				if(
					(
						(
							isset( $func[ 'argSpec' ][ $i ][ 'type' ] )
							&& $func[ 'argSpec' ][ $i ][ 'type' ] !== $arg->getType()
						)
					|| (
							!isset( $func[ 'argSpec' ][ $i ] )
							&& isset( $func[ 'argSpec' ][ 'general' ][ 'type' ] )
							&& $func[ 'argSpec' ][ 'general' ][ 'type' ] !== $arg->getType()
						)
					)
				)
					throw new ProcessingErrorException( sprintf( 'Argument static types do not match those required for the %s function', $op ) );

				if( $arg->isEmpty()
					&& (
							(
								isset( $func[ 'argSpec' ][ $i ] )
								&& !isset( $func[ 'argSpec' ][ $i ][ 'acceptEmpty' ] )
							)
						|| (
								!isset( $func[ 'argSpec' ][ $i ] )
								&& !isset( $func[ 'argSpec' ][ 'general' ][ 'acceptEmpty' ] )
							)
					)
				)
					throw new IndeterminateResultException( sprintf( 'Passed argument to %s is an empty bag', $op ) );

				if( count( $arg->getValue() ) > 1
					&& (
							(
								isset( $func[ 'argSpec' ][ $i ] )
								&& !isset( $func[ 'argSpec' ][ $i ][ 'acceptBag' ] )
							)
						|| (
								!isset( $func[ 'argSpec' ][ $i ] )
								&& !isset( $func[ 'argSpec' ][ 'general' ][ 'acceptBag' ] )
							)
					)
				)
					throw new IndeterminateResultException( sprintf( 'Passed argument to %s is a bag, but this function does not operate on bags', $op ) );

			}

			if( !isset( $func[ 'argSpec' ][ 'passAsAttributes' ] ) )
				$arguments[ $i ] = $arg->getValue();
		}

		if( isset( $func[ 'argSpec' ][ 'passType' ] ) )
			$arguments[] = $arg->getType();

		$return = call_user_func_array( $func[ 'func' ], $arguments );

		if( $this->observer !== NULL )
			$this->observer->observeCacheEffectingFunction( $op, $return, $arguments );

		if( $return instanceOf \Exception )
			throw $return;
		else if( !( $return instanceOf Attribute ) )
			throw new ProcessingErrorException( sprintf( 'Function %s does not return an attribute', $op ) );

		return $return;
	}

	public function getVersion(){
		return $this->version;
	}

	public function __construct( array $map = array(), array $unmap = array(), array $replace = array() ){
		$map = array_diff_key( $map, $unmap );

		foreach( $replace as $d => $r ){
			if( isset( $map[ $r ] ) ){
				$map[ $d ] = $map[ $r ];
				$map[ $d ][ 'deprecated' ] = true;
				$map[ $d ][ 'replacedBy' ] = $r;
			}
			else
				throw new ProcessingErrorException( 'unmapped deprecated function replacement' );
		}

		$this->map = $map;
	}

	protected function isMapped( $op ){
		if( !isset( $this->map[ $op ] ) )
			throw new ProcessingErrorException( sprintf( 'Requested Function does not exist: %s', $op ) );
		return true;
	}

	protected function takesArgs( $op, $min = 0, $max = NULL ){
		if( isset( $this->map[ $op ][ 'minArgs' ] ) ){
			if( ( $max === NULL && $this->map[ $op ][ 'minArgs' ] > $min )
				|| ( $max !== NULL && $this->map[ $op ][ 'minArgs' ] > $max )
			)
				throw new SyntaxErrorException( sprintf( 'mismatch in number of arguments for function %s', $op ) );
		}

		if( isset( $this->map[ $op ][ 'maxArgs' ] ) ){
			if( $this->map[ $op ][ 'maxArgs' ] < $min
				|| ( $max !== NULL && $this->map[ $op ][ 'maxArgs' ] < $max )
			)
			throw new SyntaxErrorException( sprintf( 'mismatch in number of arguments for function %s', $op ) );
		}

		return true;
	}

	protected function returns( $op, $returnType = array() ){
		if( !is_array( $returnType ) )
			$returnType = array( $returnType );

		if( !isset( $this->map[ $op ][ 'type' ] )
			|| !in_array( $this->map[ $op ][ 'type' ], $returnType, true ) )
			throw new ProcessingErrorException( sprintf( 'Return type of specified function %s does not match required return type', $op ) );

		return true;
	}

	protected function isDeprecated( $op ){
		if( isset( $this->map[ $op ][ 'deprecated' ] ) ){
			trigger_error( sprintf( 'Use of function %s in XACML version $s is deprecated. This function has been replaced with %s.', $op, $this->map[ $op ][ 'replacedBy' ], $this->version ), E_USER_WARNING );
			return true;
		}
		return false;
	}

}