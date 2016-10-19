<?php
namespace REJ\XacmlBundle;

use REJ\XacmlBundle\Exceptions\UnsupportedObligationException;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use	Symfony\Component\Finder\Finder; //need to decouple this from this particular finder in the future
/*
useful obligations
message person with access details
log access details to database or file
allow decision override by getting login from authorized person
allow decision override by running indicated policy against query
return message for why access is denied
return sql for reverse query-ish stuff so that we don't have to ask on each record what we are allowed to access
*/
class ObligationFactory{

	protected $registry;
	protected $namspace;
	protected $outputToRequest;

	public function __construct( $obligationNamespace = NULL ){
		if( $obligationNamespace === NULL )
			$obligationNamespace = __NAMESPACE__ . '\Obligations';
		$this->namespace = $obligationNamespace;
		$this->registry = [];
		$this->outputToRequest = NULL;
	}

	public function getInstance( $oblSpec, $isAdvice = false ){
		try{
			list( $class, $arguments ) = $this->scanSpec( $oblSpec, $isAdvice );
			$class = $this->namespace . '\\' . $class;
			$arguments[] = $this->outputToRequest;
			return new $class( $arguments, $isAdvice );
		}catch( \Exception $e ){
			if( !$isAdvice )
				throw $e;
			return NULL;
		}
	}

	public function outputTo( RequestInterface $req ){
		$this->outputToRequest = $req;
	}

	public function registerDefaults(){
		$this->namespace = __NAMESPACE__ . '\Obligations' ;
		$directory = __DIR__ . '\Obligations';
		try{
			$a = new Finder();
			$a->files()
				->ignoreUnreadableDirs()
				->in( $directory )
				->name( '*.php' )
				->getIterator();
		}catch( \Exception $e ){
			$a = [];
		}
		foreach( $a as $t ){
			$t = $t->getBasename( '.php' );
			if( $t === 'AbstractObligation' )
				continue;
			$this->registerClass( $t );
		}
	}

	public function registerClass( $class ){
		$this->registry[] = $class;
	}

	protected function scanSpec( $spec, $isAdvice ){
		$functionId = '';
		$arguments = array();

		$dom = new \DomDocument();
		$dom->loadXML( $spec );

		$root = $dom->getElementsByTagName( ( ( $isAdvice )? 'Advice': 'Obligation' ) );
		$root = $root[ 0 ];

		$functionId = $root->getAttribute( ( ( $isAdvice )? 'AdviceId': 'ObligationId' ) );

		if( !in_array( $functionId, $this->registry ) )
			throw new UnsupportedObligationException( 'unsupported obligation or advice id' );

		$attributes = $dom->getElementsByTagName( 'AttributeAssignment' );

		foreach( $attributes as $attribute ){
			$id = $attribute->getAttribute( 'AttributeId' );
			$value = $attribute->nodeValue;
			if( !isset( $arguments[ $id ] ) )
				$arguments[ $id ] = array();
			$arguments[ $id ][] = trim( $value );
		}

		return [ $functionId, $arguments ];
	}

}