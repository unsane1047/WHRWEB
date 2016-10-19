<?php
namespace REJ\XacmlBundle\v3_0\Expressions;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;
use REJ\XacmlBundle\v3_0\Expressions\Apply;

abstract class abstractExpression {

	abstract public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables = NULL );

	protected function attributeValue( XacmlReaderInterface $r, RequestInterface $req, $id = '' ){
		$return = new Attribute();
		return new Attribute( $id, $r->getAttribute( 'DataType' ), trim( $r->readString() ) );
	}

	protected function attributeSelector( XacmlReaderInterface $r, RequestInterface $req ){
		$cat = $r->getAttribute( 'Category' );
		$id = $r->getAttribute( 'ContextSelectorId' );
		$path = $r->getAttribute( 'Path' );
		$dataType = $r->getAttribute( 'DataType' );
		$contextPath = NULL;
		$mustBePresent = ( $r->getAttribute( 'MustBePresent' ) === 'true' );

		if( $id !== NULL ){
			try{
				$tmp = $req->getAttribute( $cat, $id, Attribute::XPATHEXPRESSION );
				$this->trackAttr( 'default', $cat, $id, $tmp->getType(), $tmp->getValue() );
				if( !$tmp->isEmpty() && $tmp->getType() === Attribute::XPATHEXPRESSION ){
					$contextPath = $tmp->getValue();
					$contextPath = reset( $contextPath );
				}
			}catch( MissingAttributeErrorException $e ){
				throw new IndeterminateResultException( sprintf( 'AttributeSelector failed to locate ContextSelectorId Attribute %s in content of category %s', $id, $cat ), 0, $e );
			}
		}

		try{
			$return = $req->getAttribute( $cat, 'content', $dataType, $path, $contextPath );
		}catch( MissingAttributeErrorException $e ){
			if( $mustBePresent )
				throw new IndeterminateResultException( sprintf( 'AttributeSelector failed to locate required attribute in content of category %s', $cat ), 0, $e );
			$return = new Attribute();
		}

		return $return;
	}

	protected function attributeDesignator( XacmlReaderInterface $r, RequestInterface $req ){
		$cat = $r->getAttribute( 'Category' );
		$id = $r->getAttribute( 'AttributeId' );
		$dataType = $r->getAttribute( 'DataType' );
		$mustBePresent = ( $r->getAttribute( 'MustBePresent' ) === 'true' );

		try{
			$return = $req->getAttribute( $cat, $id, $dataType );
		}catch( MissingAttributeErrorException $e ){
			if( $mustBePresent )
				throw new IndeterminateResultException( sprintf( 'AttributeDesignator failed to locate required attribute %s in category %s', $id, $cat ), 0, $e );
			$return = new Attribute( $id, $dataType );
		}

		return $return;
	}

	protected function variableReference( XacmlReaderInterface $r, AttributeSet $variables = NULL ){
		$id = $r->getAttribute( 'VariableId' );

		if( $variables === NULL )
			throw new SyntaxErrorException( 'VariableReference refers to a variable that does not exist in this context.' );

		try{
			$return = $variables->$id;
		}catch( \Exception $e ){
			throw new SyntaxErrorException( 'VariableReference refers to a variable that does not exist in this context.', 0, $e );
		}

		return $return;
	}

	protected function functionElement( XacmlReaderInterface $r, $id = '' ){
		return new Attribute( $id, Attribute::ANYURI, $r->getAttribute( 'FunctionId' ) );
	}

	protected function apply( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables = NULL ){
		$a = new Apply();
		return $a->evaluate( $r, $req, $funcLib, $variables );
	}

}