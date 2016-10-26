<?php
//set up auditing via database triggers, will simply need to set variables per connection with user information
//in order to implement
namespace AppBundle\Models;

define( 'REDBEAN_MODEL_PREFIX', 'AppBundle\\Models\\' );

use \Exception;
use \R;
use \RedBeanPHP\OODBBean as BEAN;
use AppBundle\Libraries\AppDateTime;
use AppBundle\Model\Model;
use AppBundle\Model\Collection;
use AppBundle\Model\Connection_Details;

class RedbeanService {
	const READ_UNCOMMITTED = 'READ-UNCOMMITTED';
	const READ_COMMITTED = 'READ-COMMITTED';
	const REPEATABLE_READ = 'REPEATABLE-READ';
	const SERIALIZABLE = 'SERIALIZABLE';

	protected $config;
	protected $env;

	private $activeConnections;
	private $selected;

	//class stuff
	public function __construct( $connection_config, $env, $connect_to = NULL ){
		$this->config = $connection_config;
		$this->env = $env;
		$this->activeConnections = [];
		$this->selected = NULL;
		$this->accessManager = NULL;
		if( $connect_to !== NULL )
			$this->setup( $connect_to );
	}

	public function __destruct(){
		foreach( array_keys( $this->activeConnections ) as $id )
			$this->disconnect( $id );
	}

	//connection and database utility functions
	public function setup( $connection_id = 'default' ){
		$details = $this->getConfig( $connection_id );
		if( !$this->connect( $details ) )
			$this->selectConnection( $connection_id );
	}

	public function selectConnection( $id = 'default' ){
		if( $id === $this->selected )
			return true;
		if( !isset( $this->activeConnections[ $id ] ) )
			throw new \InvalidArgumentException( sprintf( 'Connection "%s" not set up', $id ) );
		R::selectDatabase( $id );
		$this->selected = $id;
		return true;
	}

	public function testConnection(){
		return R::testConnection();
	}

	public function disconnect( $id = 'default' ){
		$tmp = $this->selected;
		$this->selectConnection( $id );
		R::close();

		if( $id === $tmp )
			$this->selected = NULL;
		else
			$this->selected = $tmp;

		unset( $this->activeConnections[ $id ] );
		return true;
	}

	public function freezeSchema( $freeze = true ){
		return R::freeze( $freeze );
	}

	//entity management functions
	public function manageEntity( $entity ){
		if( $entity instanceOf Model ){
			try{
				$class = get_class( $model );
				$this->setup( $class::$conn_id );
			}catch( Exception $e ){}
			$entity->setService( $this );
		}
		else if( $entity instanceOf BEAN ){
			$model = $entity->box();
			if( $model instanceOf Model ){
				try{
					$class = get_class( $model );
					$this->setup( $class::$conn_id );
				}catch( Exception $e ){}
				$model->setService( $this );
			}
		}
		else
			throw new \InvalidArgumentException( 'RedbeanService cannot manage entities that are not beans or descended from the provided model class' );
		return $entity;
	}

	public function create( $type, $count = 1 ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}
		catch( Exception $e ){
			$class = NULL;
		}

		$return = R::dispense( $type, $count );

		if( !is_array( $return ) )
			$return = [ $return ];

		if( $class !== NULL ){
			foreach( $return as $r )
				$r->box()->setService( $this );
		}

		return $return;
	}

	public function duplicate( $bean, array $filters = array() ){
		
		$model = $bean->box();
		$class = get_class( $model );

		try{
			if( is_subclass_of( $class, Model::class ) )
				$this->setup( $class::$conn_id );
			else
				$class = NULL;
		}
		catch( Exception $e ){
			$class = NULL;
		}

		$return = R::duplicate( $bean, $filters );

		if( $class !== NULL )
			$return->box()->copyModel( $model );

		return $return;
	}

	//because these allow arbitrary sql and table/field names to be used directly in sql queries by their nature
	//make sure that $type, $field, and $sql variables are never taken in from the user and used with these functions directly
	//these functions become progressively more dangerous from this standpoint as you read down the code from here
	public function load( $type, $id, $asBean = true, $lockString = '' ){
		try{
			$table = $this->safeTableName( $type );
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}
		catch( Exception $e ){
			$class = NULL;
		}

		if( $asBean && empty( $lockString ) ){
			if( is_array( $id ) ){
				$return = R::loadAll( $type, $id );
				if( $class !== NULL ){
					foreach( $return as $r )
						$r->box()->setService( $this );
				}
			}
			else{
				$return = R::load( $type, $id );
				if( $class !== NULL )
					$return->box()->setService( $this );
				$return = [ $return ];
			}

			return $return;
		}

		if( is_array( $id ) ){
			$args = array_values( $id );
			$args[] = count( $id );
			$return = R::getAll( 'SELECT * FROM `' . $table . '` WHERE `id` IN (' . R::genSlots( $id ) . ') LIMIT ?' . $lockString, $args );
			if( $asBean )
				$return = R::convertToBeans( $type, $return );
		}
		else{
			$return = R::getRow( 'SELECT * FROM `' . $table . '` WHERE `id` = ? LIMIT 1' . $lockString, array( $id ) );
			if( $asBean )
				$return = R::convertToBeans( $table, array( $return ) );
		}

		if( $asBean && $class !== NULL ){
			foreach( $return as $r )
				$r->box()->setService( $this );
		}

		return $return;
	}

	public function loadByField( $type, $field, $value, $asBean = true, $lockString = '' ){
		try{
			$field = $this->safeFieldName( $field );
			$table = $this->safeTableName( $type );
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		if( $asBean && $lockString == '' ){
			$return = R::findOne( $table, ' `' . $field . '` = ?', array( $value ) );
			if( $return === NULL )
				$return = R::dispense( $table );

			if( $class !== NULL )
				$return->box()->setService( $this );

			return [ $return ];
		}

		$return = R::getRow( 'SELECT * FROM `' . $table . '` WHERE `' . $field . '`=? LIMIT 1' . $lockString, array( $value ) );

		if( $asBean && $return !== NULL )
			$return = R::convertToBeans( $table, array( $return ) );
		else if( $asBean )
			$return = [ R::dispense( $table ) ];

		if( $class !== NULL && $asBean ){
			foreach( $return as $r )
				$r->box()->setService( $this );
		}

		return $return;
	}

	public function find( $type, $sql, $args, $asBean = true, $createOnFail = false ){
		try{
			$table = $this->safeTableName( $type );
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		$return = [];

		if( $asBean ){
			if( $createOnFail )
				$return = R::findOrDispense( $type, $sql, $args );
			else
				$return = R::find( $table, $sql, $args );

			if( $return === NULL )
				$return = [];
			else if( !is_array( $return ) )
				$return = [ $return ];

			if( $class !== NULL && $return !== [] ){
				foreach( $return as $r )
					$r->box()->setService( $this );
			}
			return $return;
		}

		return R::findAndExport( $table, $sql, $args );
	}

	public function findAll( $type, $sql, $asBean = true, $args ){
		try{
			$table = $this->safeTableName( $type );
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		if( $asBean ){
			$return = R::findAll( $type, $sql, $args );

			if( $class !== NULL ){
				foreach( $return as $r )
					$r->box()->setService( $this );
			}
		}
		else
			$return = R::getAll( 'SELECT * FROM `' . $table . '`' . $sql, $args );

		return $return;
	}

	public function findOne( $type, $sql, $asBean = true, $args ){
		try{
			$table = $this->safeTableName( $type );
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		if( $asBean ){
			$return = R::findOne( $type, $sql, $args );
			if( $return === NULL )
				$return = [];
			else if( !is_array( $return ) )
				$return = [ $return ];

			if( $class !== NULL ){
				foreach( $return as $r )
					$r->box()->setService( $this );
			}
		}
		else
			$return = R::getRow( 'SELECT * FROM `' . $table . '`' . $sql, $args );

		return $return;
	}

	public function findLast( $type, $sql, $args ){ //cannot provide asBean because this would require parsing the sql to add where condition
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		$return = R::findLast( $type, $sql, $args );
		if( $return === NULL )
			$return = [];
		else if( !is_array( $return ) )
			$return = [ $return ];

		if( $class !== NULL ){
			foreach( $return as $r )
				$r->box()->setService( $this );
		}

		return $return;
	}

	public function findCollection( $type, $sql, $args ){ //there is no plain sql equivalent for this method at this time
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		$return = R::findCollection( $type, $sql, $args );
		$return = new Collection( $return );

		if( $class !== NULL )
			$return->setService( $this );

		return $return;
	}

	public function findLike( $type, array $like = array(), $sql = '' ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		$return = R::findLike( $type, $like, $sql );

		if( $return === NULL )
			$return = [];
		else if( !is_array( $return ) )
			$return = [ $return ];


		if( $class !== NULL ){
			foreach( $return as $r )
				$r->box()->setService( $this );
		}

		return $return;
	}

	//alter stored data

	// optional merge specification will allow certain fields from replace_id record
	// to be kept or appended to the fields from keep_id record
	final public function mergeRecords( $type, $keep_id, $replace_id, $mergespec = array() ){
		$sql = '';
		$args = array();
		$table = $this->safeTableName( $type );
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		$fields = $this->getRelatedFields( $table );
		$fieldsToUpdate = array_unique( array_column( $fields, 'TABLE_NAME' ) );

		$records = R::getAll( "SELECT * FROM `{$table}` WHERE `id` IN ( ?, ? ) FOR UPDATE", array( $keep_id, $replace_id ) ); #lock needed records if a transaction is active

		if( count( $records ) != 2 )
			throw new Exception( 'unable to merge records. one or more records not found' );

		$records = array_combine( array_column( $records, 'id' ), $records );

		if( count( $fieldsToUpdate ) > 1 ){
			foreach( $fields as $row ){
				$sql .= "UPDATE IGNORE `{$row[ 'TABLE_NAME' ]}` SET `{$row[ 'COLUMN_NAME' ]}` = ? WHERE `{$row[ 'COLUMN_NAME' ]}` = ?;\n";
				$args[] = $keep_id;
				$args[] = $replace_id;
			}
		}

		$newValues = array();
		$tmpsql = "UPDATE `{$table}` SET `id`=`id`";

		foreach( $mergespec as $field => $spec ){
			if( !isset( $records[ $replace_id ][ $field ] ) || $records[ $replace_id ][ $field ] === $records[ $keep_id ][ $field ] )
				continue;

			switch( $spec ){
				case $replace_id:
					$newValues[] = $records[ $replace_id ][ $field ];
					$tmpsql .= ", `{$field}` = ?";
				break;

				case 'combine':
					$tmp = '';

					if( isset( $records[ $keep_id ][ $field ] ) )
						$tmp .= $records[ $keep_id ][ $field ];

					$tmp .= $records[ $replace_id ][ $field ];
					$newValues[] = $tmp;

					$tmpsql .= ", `{$field}` = ?";
				break;
			}

		}

		if( count( $newValues ) > 0 ){
			$sql .= $tmpsql . " WHERE `id` = ?;\n";
			$newValues[] = $keep_id;
			$args = array_merge( $args, $newValues );
		}

		$sql .= "DELETE FROM `{$table}` WHERE id = ? LIMIT 1;";
		$args[] = $replace_id;

		return R::exec( $sql, $args );
	}

	public function merge( $keep, $replace ){
		$id = $keep->id;
		foreach( $keep->getProperties() as $property => $value ){
			if( strpos( $property, 'own' ) === 0 ){
				$property = 'x' . $property . 'List';
				$mergeMe = $replace->$property;
				if( is_array( $mergeMe ) && count( $mergeMe ) > 0 )
					$keep->$property = array_merge( $keep->$property, $mergeMe );
			}
			else if( strpos( $property, 'shared' ) === 0 ){
				$peroperty .= 'List';
				$mergeMe = $contact->$property;
				if( is_array( $mergeMe ) && count( $mergeMe ) > 0 )
					$keep->$property = array_merge( $keep->$property, $mergeMe );
			}
			else if( !isset( $value ) && isset( $replace->$property ) )
				$keep->$property = $replace->$property;
		}

		$keep->id = $id; #make sure that id didn't get futzed with

		$this->persist( $keep );
		$this->remove( $replace );
		return true;
	}

	public function persist( $bean ){
		$arr = is_array( $bean );

		if( $arr )
			$type = reset( $bean );
		else
			$type = $bean;

		if( !( $type instanceOf BEAN ) ){
			if( !$arr )
				$type = $bean = $bean->unbox();
			else{
				foreach( $bean as $i => $b )
					$bean[ $i ] = $b->unbox();
				$type = reset( $bean );
			}
		}

		$type = $type->getMeta( 'type' );

		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		if( $arr )
			return R::storeAll( $bean );

		return R::store( $bean );
	}

	public function remove( $bean ){
		$arr = is_array( $bean );

		if( $arr )
			$type = reset( $bean );
		else
			$type = $bean;

		if( !( $type instanceOf BEAN ) ){
			if( !$arr )
				$type = $bean = $bean->unbox();
			else{
				foreach( $bean as $i => $b )
					$bean[ $i ] = $b->unbox();
				$type = reset( $bean );
			}
		}

		$type = $type->getMeta( 'type' );

		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		if( $arr )
			return R::trashAll( $bean );

		return R::trash( $bean );
	}

	public function wipe( $type ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		return R::wipe( $type );
	}

	public function destroyDatabase( $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::nuke();
	}

	//metaData functions

	public function lastInsertID(){
		if( (float)R::getVersion() > 4.2 )
			return R::getInsertID();
		else
			return R::getDatabaseAdapter()->getInsertID();
	}

	public function count( $type ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){}

		return R::count( $type );
	}

	public function getColumns( $type ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){}

		return R::getColumns( $type );
	}

	public function inspect( $type = NULL ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){}

		return R::inspect( $type );
	}

	public function getRelatedFields( $type ){
		try{
			$table = $this->safeTableName( $type );
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){}

		$config = $this->getConfig( $class::$conn_id );
		
		$database = $config->getDatabase();

		$sql = <<<'NOWDOC'
SELECT `TABLE_NAME`, `COLUMN_NAME`
FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
WHERE `TABLE_SCHEMA` = ?
AND `REFERENCED_TABLE_SCHEMA` = ?
AND `REFERENCED_TABLE_NAME` = ?
AND `REFERENCED_COLUMN_NAME` = ?
NOWDOC;
		$args = array( $database, $database, $table, 'id' );
		return R::getAll( $sql, $args );
	}

	####################
	# transactions
	####################

	public function begin( $lev = NULL ){
		switch( $lev ){
			case self::READ_UNCOMMITTED:
			case self::READ_COMMITTED:
			case self::REPEATABLE_READ:
			case self::SERIALIZEABLE:
				R::exec( 'SET TRANSACTION ISOLATION LEVEL ' . $lev );
			break;
		}

		return R::begin();
	}

	public function commit(){
		return R::commit();
	}

	public function rollback(){
		return R::rollback();
	}

	public function transact( Callable $callback, $lev = NULL ){
		switch( $lev ){
			case self::READ_UNCOMMITTED:
			case self::READ_COMMITTED:
			case self::REPEATABLE_READ:
			case self::SERIALIZEABLE:
				R::exec( 'SET TRANSACTION ISOLATION LEVEL ' . $lev );
			break;
		}

		return R::transaction( $callback );
	}

	public function debug( $toggle = true, $pretty = true, $logOnly = false ){
		$mode = 0;
		if( $pretty && $logOnly )
			$mode = 3;
		else if( $logOnly )
			$mode = 1;
		else if( $pretty )
			$mode = 2;
		return R::debug( $toggle, $mode );
	}

	######################
	# models/beans to pure SQL output
	######################

	public function exportAll( $beans, $parents = false, array $filters = array() ){
		$arr = is_array( $bean );

		if( $arr )
			$type = reset( $bean );
		else
			$type = $bean;

		if( !( $type instanceOf BEAN ) ){
			if( !$arr )
				$type = $bean = $bean->unbox();
			else{
				foreach( $bean as $i => $b )
					$bean[ $i ] = $b->unbox();
				$type = reset( $bean );
			}
		}

		$type = $type->getMeta( 'type' );

		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		return R::exportAll( $bean, $parents, $filters );
	}

	public function beansToArray( array $bean ){

		$type = reset( $bean );

		if( !( $type instanceOf BEAN ) ){
			foreach( $bean as $i => $b )
				$bean[ $i ] = $b->unbox();
			$type = reset( $bean );
		}

		$type = $type->getMeta( 'type' );

		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		return R::beansToArray( $bean );
	}

	public function dump( $bean ){
		$arr = is_array( $bean );

		if( $arr )
			$type = reset( $bean );
		else
			$type = $bean;

		if( !( $type instanceOf BEAN ) ){
			if( !$arr )
				$type = $bean = $bean->unbox();
			else{
				foreach( $bean as $i => $b )
					$bean[ $i ] = $b->unbox();
				$type = reset( $bean );
			}
		}

		$type = $type->getMeta( 'type' );

		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		return R::dump( $bean );
	}

	######################
	# pure SQL output to models/beans
	######################

	public function convertToBeans( $type, array $rows, $metamask = NULL ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		$return = R::convertToBeans( $type, $rows, $metamask );

		if( $class !== NULL ){
			foreach( $return as $r )
				$r->box()->setService( $this );
		}

		return $return;
	}

	public function convertToBean( $type, array $row, $metamask = NULL ){
		try{
			$class = $this->getModelName( $type );
			$this->setup( $class::$conn_id );
		}catch( Exception $e ){
			$class = NULL;
		}

		$return = R::convertToBean( $type, $rows, $metamask );

		if( $class !== NULL )
			$return->box()->setService( $this );

		return $return;
	}

	######################
	# direct SQL functions
	######################

	public function exec( $sql, array $args = array(), $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::exec( $sql, $args );
	}

	public function getAll( $sql, array $args = array(), $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::getAll( $sql, $args );
	}

	public function getCell( $sql, array $args = array(), $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::getCell( $sql, $args );
	}

	public function getRow( $sql, array $args = array(), $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::getRow( $sql, $args );
	}

	public function getCol( $sql, array $args = array(), $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::getCol( $sql, $args );
	}

	public function getAssoc( $sql, array $args = array(), $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::getAssoc( $sql, $args );
	}

	public function getAssocRow( $sql, array $args = array(), $conn_id = NULL ){
		if( $conn_id !== NULL )
			$this->setup( $conn_id );
		return R::getAssocRow( $sql, $args );
	}


	//internal utility functions

	protected function getConfig( $connection_id ){
		return new Connection_Details( $connection_id, $this->env, $this->config );
	}

	protected function connect( Connection_Details $details ){
		$id = $details->getId();

		if( isset( $this->activeConnections[ $id ] ) )
			return false;
		
		try{
			$this->activeConnections[ $id ] = true;
			R::addDatabase( $id, $details->getConnectionString(), $details->getUser(), $details->getPassword(), $details->freezeDatabase() );
			R::selectDatabase( $id );
			$this->selected = $id;
			R::exec( 'SET time_zone=?', array( AppDateTime::timezoneNameToOffset( date_default_timezone_get() ) ) );
		}catch( Exception $e ){
			throw new Exception( 'Database exception occurred during connect', 0, $e );
		}

		return true;
	}

	protected function safeTableName( $input ){
		preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches );
		$ret = $matches[ 0 ];
		foreach( $ret as &$match )
			$match = strtolower( $match );
		return preg_replace( '/[^a-zA-Z0-9\_]/', '', implode( '_', $ret ) );
	}

	protected function safeFieldName( $input ){
		preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches );
		$ret = $matches[ 0 ];
		foreach( $ret as &$match )
			$match = strtolower( $match );
		return preg_replace( '/[^a-zA-Z0-9\_]/', '', implode( '_', $ret ) );
	}

	protected function getModelName( $table ){
		$class = REDBEAN_MODEL_PREFIX . ucfirst( $table );
		if( class_exists( $class ) && is_subclass_of( $class, Model::class ) )
			return $class;
		throw new Exception( 'cannot create Entity for table that does not have an associated Model.' );
	}
}