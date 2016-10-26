<?php

namespace AppBundle\Models\Exceptions;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class ValidationException extends \Exception{
	protected $errors = array();
	protected $table = NULL;
	protected $id = 0;
	protected $form;

	final function setTable( $table_name = '' ){
		$this->table = $table_name;
	}

	final function getTable(){
		return $this->table;
	}

	final function setId( $id = 0 ){
		return $this->id;
	}

	final function getId(){
		return $this->id;
	}

	final function setForm( FormInterface $form ){
		$this->form = $form;
		if( $form !== NULL ){
			foreach( $this->errors as $field_name => $errors ){
				foreach( $errors as $e )
					$this->setMessageOnForm( $e[ 'message' ], $e[ 'severity' ], $field_name );
			}
		}
	}

	final function hasForm(){
		return $this->form !== NULL;
	}

	final function getMessages( $field_name = '', $severity = NULL ){
		$return = array();

		if( $field_name === NULL )
			$return = $this->errors;
		else if( isset( $this->errors[ $field_name ] ) )
			$return = $this->errors[ $field_name ];

		if( $severity != NULL ){
			$return = array_filter( $return, function( $k ) use ( $severity ){
				return ( isset( $k[ 'severity' ] ) && $k[ 'severity' ] == $severity );
			});
		}

		return $return;
	}

	final function setMessage( $message, $severity = '', $field_name = '' ){
		$this->errors[ $field_name ][] = array(
			'message' => $message,
			'severity' => $severity
		);

		if( $this->form !== NULL )
			$this->setMessageOnForm( $message, $severity, $field_name );
	}

	protected function setMessageOnForm( $message, $severity, $field_name ){
			$tmp = $this->form;
			if( !empty( $this->table ) && $this->form->has( $this->table ) )
				$tmp = $this->form->get( $this->table );
			if( $tmp->count() > 0 && $tmp->has( $this->id ) )
				$tmp = $tmp->get( $this->id );
			if( $tmp->has( $field_name ) )
				$tmp = $tmp->get( $field_name );
			if( $tmp->count() > 0 && $tmp->has( 'first' ) )
				$tmp = $tmp->get( 'first' );
			$err = new FormError( $message, NULL, [], NULL, [ 'constraint' => [ 'payload' => [ 'severity' => $severity ] ] ] );
			$tmp->addError( $err );
	}

	final function unsetMessages( $field_name = NULL, $severity = NULL ){
		$errors = $this->errors;

		if( $field_name !== NULL )
			$errors = array( ( isset( $errors[ $field_name ] ) )? $errors[ $field_name ]: array() );

		if( $severity !== NULL ){
			foreach( $errors as &$e ){
				$e = array_filter( $e, function( $k ) use ( $severity ){
					return !isset( $k[ 'severity' ] ) || $k[ 'severity' ] != $severity;
				});
			}
		}
		else
			$errors = ( $field_name !== NULL )? [ [] ]: [];

		if( $field_name !== NULL ){
			$errors = reset( $errors );
			$this->errors[ $field_name ] = $errors;
		}
		else
			$this->errors = $errors;
	}

}