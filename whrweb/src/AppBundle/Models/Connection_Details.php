<?php
namespace AppBundle\Models;

class Connection_Details{
	protected $id;
	protected $type;
	protected $host;
	protected $port;
	protected $name;
	protected $user;
	protected $password;
	protected $env;
	protected $frozen;
	protected $path;

	function __construct( $connection_id, $env, $config ){
		$this->id = $connection_id;

		if( !isset( $config[ $connection_id ] ) )
			throw new \InvalidArgumentException( sprintf( 'No connection id "%s" found, please provide details in configuration', $connection_id ) );

		$config = $config[ $connection_id ];

		if( !isset( $config[ 'type' ] ) )
			throw new \InvalidArgumentException( sprintf( 'database configuration for "%s" must supply a database type', $connection_id ) );

		if( !isset( $config[ 'env' ] ) )
			throw new \InvalidArgumentException( sprintf( 'database configuration for "%s" must supply an environment', $connection_id ) );

		if( $config[ 'env' ] !== 'any'
			&& $config[ 'env' ] !== $env )
			throw new \InvalidArgumentException( sprintf( 'database configuration for "%s" does not allow connection from the "%s" environment', $connection_id, $env ) );

		$this->type = $config[ 'type' ];
		$this->env = $config[ 'env' ];

		switch( $this->type ){
			case 'sqlite':
				if( !isset( $config[ 'path' ] ) )
					throw new \InvalidArgumentException( sprintf( 'database configuration for sqlite database must include a path. connection id "%s"', $connection_id ) );
				$this->path = $config[ 'path' ];
			break;

			case 'mysql':
			case 'pgsql':
			case 'cubrid':
				if( !isset( $config[ 'host' ] )
					|| !isset( $config[ 'port' ] )
					|| !isset( $config[ 'name' ] )
					|| !isset( $config[ 'user' ] )
					|| !isset( $config[ 'password' ] ) )
					throw new \InvalidArgumentException( sprintf( 'database configuration for mysql, pgsql and cubrid database must include host, port, name, user and password. connection id "%s"', $connection_id ) );
				$this->host = (string)$config[ 'host' ];
				$this->port = ( !empty( $config[ 'port' ] ) )? $config[ 'port' ]: NULL;
				$this->name = (string)$config[ 'name' ];
				$this->user = ( !empty( $config[ 'user' ] ) )? $config[ 'user' ]: NULL;
				$this->password = ( !empty( $config[ 'password' ] ) )? $config[ 'password' ]: NULL;
			break;

			default:
				throw new \InvalidArgumentException( sprintf( 'Connection id "%s" does not specify a valid database type', $connection_id ) );
			break;
		}

		$this->frozen = ( ( isset( $config[ 'frozen' ] )? $config[ 'frozen' ]: true );
	}

	function freezeDatabase( $t = NULL ){
		$oldt = $t;
		if( $t !== NULL )
			$this->frozen = $t;
		return $oldt;
	}

	function getConnectionString(){
		if( !is_array( $this->details ) )
			return NULL;

		$string = $this->type . ':';

		if( $this->type === 'sqlite' ){
			$string .= $this->path;
		}
		else{
			$string .= 'host=' . $this->host . ';';
			if( $this->type === 'cubrid' && $this->port !== NULL )
				$string .= 'port=' . $this->port . ';';
			$string .= 'dbname=' . $this->name;
			if( $this->type !== 'cubrid' && $this->port !== NULL )
				$string .= ';port=' . $this->port;
		}

		return $string;
	}

	function getUser(){
		return $this->user;
	}

	function getPassword(){
		return $this->password;
	}

	function getDatabase(){
		return $this->name;
	}

	function getId(){
		return $this->id
	}
}