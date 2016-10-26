<?php
namespace AppBundle\Models;

use \RedBean_SimpleModel;
use Hashids\Hashids;
use AppBundle\Models\RedbeanService;
use AppBundle\Models\Exceptions\ConcurrencyException;
use AppBundle\Models\Exceptions\ValidationException;
use AppBundle\Libraries\AppDateTime;

class Model extends RedBean_SimpleModel implements \Serializable{

	#########################################
	# id hash
	# should be defined in each model
	# salt should not conflict between models
	#########################################

	const SALT		= '';
	const IDLENGTH	= '16';
	const ALPHABET	= 'abcdefghijklmnopqrstuvwxyz0123456789';

	const		EMPTYDATETIME	= '0000-00-00 00:00:00';
	const		EMPTYDATE		= '0000-00-00';
	const		EMPTYTIME		= '00:00:00';

	static $conn_id = 'default';

	################
	# audit and concurrency settings
	################

	protected	$concurrent		= false;
	protected	$version		= 0;
	protected	$serialized		= false;
	protected	$serializedBean;
	protected	$serializedBeanType;
	protected	$service;
	protected	$exception;
	protected	$form;

	//table initialization should be implemented in each model
	//this function allows us to have a model that, when used can set up it's table in the database
	//according to a specified algo, instead of relying on RedBean to do it for us
	function setup(){
		throw new Exception( 'setup not implemented for table ' . get_called_class() );
	}

	static function getTableName(){
		$class = static::class;
		$class = explode( 'Model_', $class, 2 );
		return strtolower( array_pop( $class ) );
	}

	//things to make this work with the RedbeanService
	public function copyModel( Model $a ){
		$this->version = $a->getVersion();
		$this->setService( $a->getService() );
	}

	function setService( RedbeanService $s ){
		$this->service = $s;
		if( $this->serialized ){
			$bean = $s->create( $this->serializedBeanType );
			$this->bean = reset( $bean );
			$this->bean->import( $this->serializedBean );
			$this->bean->id = $this->serializedBean[ 'id' ];
			$this->bean->setMeta( 'model', $this );
			$this->serialized = false;
			unset( $this->serializedBean, $this->serializedBeanType );
		}
	}

	function getService(){
		return $this->service;
	}

	function persist(){
		if( $this->service === NULL )
			throw new \Exception( 'must set Redbean service.' );

		return $this->getService()->persist( $this->bean );
	}

	function duplicate( array $filters = array() ){
		if( $this->service === NULL )
			throw new \Exception( 'must set Redbean service.' );

		return $this->getService()->duplicate( $this->bean, $filters );
	}

	function mergeWith( Model $duplicate ){
		if( $this->service === NULL )
			throw new \Exception( 'must set Redbean service.' );

		return $this->getService()->merge( $this->bean, $duplicate );
	}

	function remove(){
		if( $this->service === NULL )
			throw new \Exception( 'must set Redbean service.' );

		return $this->getService()->remove( $this->bean );
	}

	function dump(){
		if( $this->service === NULL )
			throw new \Exception( 'must set Redbean service.' );

		return $this->getService()->dump( $this->bean );
	}

	//concurrency version controls
	function setVersion( $version ){
		$this->version = $version;
	}

	function getVersion(){
		return $this->version;
	}

	//id hiding for url functions
	final function urlID(){
		if( static::SALT != '' )
			return self::hashID( $this->bean->id );
		return $this->bean->id;
	}

	final static function unhashID( $id ){
		if( static::SALT == '' )
			return $id;

		$hashids = new Hashids( static::SALT, static::IDLENGTH,  static::ALPHABET );
		$return = $hashids->decode( mb_strtolower( $id ) );
		if( is_array( $return ) )
			$return = reset( $return );
		return $return;
	}

	final static function hashID( $id ){
		if( static::SALT == '' )
			return $id;

		$hashids = new Hashids( static::SALT, static::IDLENGTH, static::ALPHABET );
		$return = $hashids->encode( $id );
		if( is_array( $return ) )
			$return = reset( $return );
		return $return;
	}

	###############
	# redbean hooks
	###############

	function update(){
		if( $this->bean->isTainted() ){
			$properties = $this->bean->getProperties();
			$changed = $this->normalize();

			if( !$this->validate() ){
				if( $this->getException() !== NULL )
					throw $this->exception;
				else{
					$e = $this->getException( true );
					$e->setMessage( 'provided data is invalid', 'error' );
					throw $e;
				}
			}

			if( $changed > 0 && $this->concurrent ){
				if( isset( $this->version ) && $this->bean->version !== $this->version )
					throw new ConcurrencyException();

				$this->bean->version = (int)$this->bean->version + 1;

			}
		}

		return true;
	}

	function delete(){
		if( $this->concurrent ){
			if( isset( $this->version ) && $this->bean->version !== $this->version )
				throw new ConcurrencyException();
		}
	}

	function after_update(){
		$this->bean->clearHistory();
	}

	#################
	# data validation
	#################

	function validate(){
		return true;
	}

	final public function getException( $create = false ){
		if( $create && $this->exception === NULL ){
			$this->exception = new ValidationException();
			$this->exception->setTable( self::getTableName() );
			$this->exception->setId( $this->bean->id );
			$this->exception->setForm( $this->form );
		}
		return $this->exception;
	}

	final public function setForm( $form ){
		$this->form = $form;
	}

	###################
	# model serialization
	###################
	function serialize(){
		return serialize( array(
			$this->version,
			( ( !$this->serialized )? $this->bean->getMeta( 'type' ): $this->serializedBeanType ),
			( ( !$this->serialized )? $this->bean->export(): $this->serializedBean )
		) );
	}

	function unserialize( $serialized ){
		list(
			$this->version,
			$this->serializedBeanType,
			$this->serializedBean
		) = unserialize( $serialized );
		$this->serialized = true;
	}

	function getSerialized(){
		return $this->serialized;
	}

	##################
	# data normalization
	##################
	function normalize(){
		$properties = $this->bean->getProperties();
		$changed = 0;

		foreach( $properties as $property => $value ){

			if( !$this->bean->hasChanged( $property ) )
				continue;

			$changed++;

			if( $value === '' && $property !== 'id' && $property !== 'version' && $property !== 'created' )
				$this->bean->$property = NULL;

			$normalization_function = 'normalize_' . $property;

			if( is_callable( array( $this, $normalization_function ) ) )
				$this->bean->$property = $this->$normalization_function( $value );
		}

		return $changed;
	}

	final protected function immutable( $key, $value ){
		$old = $this->bean->old( $key );

		if( $this->bean->id == 0 || $old === NULL )
			return $value;
		return $old;
	}

	final protected function normalize_created( $value ){
		return $this->immutable( 'created', $value );
	}

	final protected function normalize_version( $value ){
		return $this->immutable( 'version', $value );
	}

	final protected function as_dateTime( $value ){
		if( !($value instanceof \DateTime) )
			$value = new AppDateTime( $value );
		return $value->format( 'Y-m-d H:i:s' );
	}

	final protected function as_date( $value ){
		if( !($value instanceOf \DateTime ) )
			$value = new AppDateTime( $value, true );
		return $value->format( 'Y-m-d' );
	}

	final protected function as_boolean( $value, $default = false ){
		if( $default === false )
			return ( $value === 'true' || $value === 'on' || $value === 'ON' || $value === 'On' || $value === true || $value == 1 );
		else
			return ( $value !== 'false' && $value !== 'off' && $value !== 'OFF' && $value !== 'Off' && $value !== false && $value != 0 );
	}

}