<?php

namespace REJ\XacmlBundle\v3_0\Expressions;

use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Attributes\Attribute;
use REJ\XacmlBundle\Attributes\AttributeSet;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\IndeterminateResultException;
use REJ\XacmlBundle\v3_0\Expressions\abstractExpression;
use REJ\XacmlBundle\v3_0\Functions\FunctionLibrary;

class AttributeAssignmentExpression extends abstractExpression{

	public function evaluate( XacmlReaderInterface $r, RequestInterface $req, FunctionLibrary $funcLib, AttributeSet $variables = NULL ){
		if( !$r->isElement( 'AttributeAssignmentExpression' ) )
			throw new ProcessingErrorException( 'AttributeAssignmentExpression only interprets AttributeAssignemtExpression Nodes' );

		$id = $r->getAttribute( 'AttributeId' );
		$category = $r->getAttribute( 'Category' );
		$issuer = $r->getAttribute( 'Issuer' );
		$dataType = NULL;
		$return = new Attribute( $id );
		$values = array();

		if( empty( $id ) ){
			$r->jumpToEndElement( 'AttributeAssignmentExpression' );
			throw new SyntaxErrorException( 'no AttributeId specified for AttributeAssignmentExpression' );
		}

		while( $r->read() ){
			if( $r->isElement() ){
				$name = $r->elementName();
				if( $name == 'AttributeValue' ){
					if( $dataType === NULL )
						$dataType = $r->getAttribute( 'DataType' );
					if( $dataType == $r->getAttribute( 'DataType' ) )
						$values[] = trim( $r->readString() );
				}
				else if( $name == 'AttributeDesignator' ){
					try{
						$tmp = $this->attributeDesignator( $r, $req );
						$dt = $tmp->getType();
						if( $dataType === NULL )
							$dataType = $dt;
						if( $dt === $dataType )
							$values = array_merge( $values, $tmp->getValue() );
					}catch( \Exception $e ){}
				}
				else if( $name == 'AttributeSelector' ){
					try{
						$tmp = $this->attributeDesignator( $r, $req );
						$dt = $tmp->getType();
						if( $dataType === NULL )
							$dataType = $dt;
						if( $dt === $dataType )
							$values = array_merge( $values, $tmp->getValue() );
					}catch( \Exception $e ){}
				}
				else if( $name == 'Apply' ){
					try{
						$tmp = $this->apply( $r, $req, $funcLib );
						$dt= $tmp->getType();
						if( $dataType === NULL )
							$dataType = $dt;
						if( $dataType === $dt )
							$values = array_merge( $values, $tmp->getValues() );
					}catch( \Exception $e ){}
				}
			}
			else if( $r->isEndElement( 'AttributeAssignmentExpression' ) )
				break;
		}

		$return->setType( $dataType );
		$return->setValue( $values );

		return $return->asAttribAssign( $category, $issuer );
	}

}