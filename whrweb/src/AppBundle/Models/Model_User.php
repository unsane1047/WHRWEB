<?php
namespace AppBundle\Models;

use Symfony\Component\Security\Core\User\AdvancedUserInterface,
	Symfony\Component\Security\Core\User\UserInterface,
	Symfony\Component\Security\Core\User\EquatableInterface,
	Symfony\Component\Security\Core\Role\Role,
	AppBundle\Libraries\Dates,
	AppBundle\Libraries\StringLib,
	AppBundle\Models\Model,
	AppBundle\Models\Model_Login,
	AppBundle\Models\RedbeanService,
	AppBundle\Models\ValidationException;

class Model_User extends Model implements AdvancedUserInterface, EquatableInterface{

	static $pswrdCharClasses = array(
		'lowercaseascii' => array( 'abcdefghijklmnopqrstuvwxyz', 2),
		'uppercaseascii' => array( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 2),
		'numeric' => array('1234567890', 4),
		'symbols' => array('!@#$%^&*()~`{}|[]\\:";\'<>?,./ ', 6)
	);

	const SALT = 'fd03457d1553be9ee50548229bde127d52c68c4d0975f56a849d078c2fcaf2d8';
	static $conn_id = 'default';

	const	RESOURCE_ID = 'whrdb:user';
	const	RESOURCE_ID_LIST = 'whrdb:user:list';

	protected $concurrent = true;
	protected $roles_cache;
	protected $login;
	protected $allowPasswordChange = false;
	protected $allowRoleChange = false;

	public function allowPasswordChange( $flag = true ){
		$this->allowPasswordChange = $flag;
	}

	public function allowRoleChange( $flag = true ){
		$this->allowRoleChange = $flag;
	}

	public function setService( RedbeanService $s ){
		parent::setService( $s );
		if( isset( $this->login ) )
			$this->login->setService( $s );
	}

	public function copyModel( Model $a ){
		if( $a instanceOf Model_User )
			$this->setLogin( $a->getLogin() );
		return parent::copyModel( $a );
	}

	public function getLogin(){
		return $this->login;
	}

	public function setLogin( Model_Login $l = NULL ){
		$this->login = $l;
		return $this;
	}

	public function getRoles(){ //doesn't yet work really, need to implement this eventually
		$tmp = explode( ',', $this->roles );
		$tmp[] = 'ROLE_USER';
		return $tmp;
	}

	public function getPassword(){
		if( $this->serialized )
			return $this->serializedBean[ 'password' ];
		return $this->bean->password;
	}

	public function getSalt(){
		return NULL;
	}

	public function getUsername(){
		if( $this->serialized )
			return $this->serializedBean[ 'username' ];
		return $this->bean->username;
	}

	#removes sensitive data from the user, don't really see the need to implement this
	#as we don't store unencrypted passwords here
	public function eraseCredentials(){}

	#advanced user interface methods
	public function isAccountNonExpired(){
		if( $this->serialized )
			return $this->serializedBean[ 'expired' ];
		return !$this->bean->expired;
	}

	public function isAccountNonLocked(){
		if( $this->serialized )
			return $this->serializedBean[ 'locked' ];
		return !$this->bean->locked;
	}

	public function isCredentialsNonExpired(){
		if( $this->serialized )
			return $tmp;

		return ( !(int)( ( $this->serialized )? $this->serializedBean[ 'must_change_password' ]:$this->bean->must_change_password ) );
	}

	public function isEnabled(){
		if( $this->serialized )
			return $this->serializedBean[ 'enabled' ];
		return (int)$this->bean->enabled;
	}

	#makes sure two users are actually equal
	public function isEqualTo( UserInterface $user ){
		if( !( $user instanceOf Model_User ) )
			return false;

		if( $user->serialized )
			$user->setService( $this->getService() );

		if( $this->serialized )
			return false;

		$userBean = $user->unbox();

		return ( ( $this->bean->password === $user->getPassword() )
				&& ( $this->getSalt() === $user->getSalt() )
				&& ( $this->bean->username === $user->getUsername() )
				&& ( $this->bean->id === $userBean->id )
				&& ( $this->bean->enabled === $userBean->enabled )
				&& ( $this->bean->password_last_changed === $userBean->password_last_changed )
				&& ( $this->bean->locked === $userBean->locked )
				&& ( $this->getRoles() === $user->getRoles() )
				);
	}


	function validate(){
		if( $this->serialized )
			throw new \Exception( 'must set service before validation' );

		if( empty( $this->bean->password_last_changed ) )
			$this->bean->password_last_changed = $this->normalize_password_last_changed( $this->bean->password_last_changed );

		if( $this->bean->id === 0 || $this->bean->created === NULL ){ //set created if this is a new bean
			$this->bean->created = new \DateTime();
			$this->bean->password_last_changed = new \DateTime();
		}

		if( empty( $this->bean->username ) || !StringLib::isValidEmail( $this->bean->username ) )
			$this->getException( true )->setMessage( 'user.username.error.invalid', 'error', 'username' );

		if( $this->bean->password === NULL )
			$this->getException( true )->setMessage( 'user.error.password', 'error', 'password' );

		if( $this->bean->hasChanged( 'password' ) ){
			$this->bean->password_last_changed = new \DateTime();
			$this->bean->must_change_password = false;
		}

		if( $this->bean->locked == 0 )
			$this->bean->note = NULL;
		else if( $this->bean->hasChanged( 'locked' ) && $this->bean->note == '' )
			$this->getException( true )->setMessage( 'user.error.note', 'error', 'note' );
		else if( !$this->bean->hasChanged( 'locked' )
				&& $this->bean->locked == 1
				&& ( $this->hasChanged( 'username' )
					|| $this->hasChanged( 'password' ) )
				)
			$this->getException( true )->setMessage( 'user.error.update.locked', 'error', 'locked' );

		return ( $this->getException() === NULL );
	}


	public function serialize(){
		$serialized = parent::serialize();
		return serialize( array(
			$serialized,
			$this->login,
			$this->roles_cache
		) );
	}

	public function unserialize( $serialized ){
		list(
			$serialized,
			$this->login,
			$this->roles_cache
		) = unserialize( $serialized );
		parent::unserialize( $serialized );
	}


	public function normalize_username( $val ){
		return mb_strtolower( StringLib::trim( StringLib::singleLineInputSpacesOnly( StringLib::normalize( StringLib::replace_invalid_utf8( $val ) ) ) ) );
	}

	protected function normalize_password( $val ){
		if( $this->allowPasswordChange )
			return $val;
		return $this->immutable( 'password', $val );
	}

	protected function normalize_password_last_changed( $val ){
		return $this->immutable( 'password_last_changed', $val );
	}

	protected function normalize_note( $val ){
		return StringLib::trim( StringLib::singleLineInputSpacesOnly( StringLib::normalize( StringLib::replace_invalid_utf8( $val ) ) ) );
	}

	protected function normalize_must_change_password( $val ){
		return $this->as_boolean( $val, false );
	}

	protected function normalize_enabled( $val ){
		return $this->as_boolean( $val, true );
	}

	protected function normalize_expired( $val ){
		return $this->as_boolean( $val, false );
	}

	protected function normalize_locked( $val ){
		return $this->as_boolean( $val, false );
	}

	protected function normalize_roles( $val ){
		if( !$this->allowRoleChange )
			return $this->immutable( 'roles', $val );

		$val = explode( ',', $val );
		$final = [];
		foreach( $val as $v ){
			if( $v === 'ROLE_ADMIN'
				|| $v === 'ROLE_DEV'
			)
				$final[] = $v;
		}
		return implode( ',', $final );
	}

	public static function passwordStrength( RedbeanService $db, $password, $options = array() ){
		$valid = false;
		$strength = 0;
		$len = mb_strlen( $password );
		$errors = new ValidationException();
		$errors->setTable( 'user' );

		if( $password == '' || $password === NULL ){
			$errors->setMessage( 'password.strength.blank', 'error', 'password' );
			throw $errors;
		}

		if( isset( $options[ 'minpasswordlength' ] ) && $len < $options[ 'minpasswordlength' ] ){
			$errors->setMessage( 'user.password.minlength', 'error', 'password' );
			throw $errors;
		}

		#make sure password does not appear in installed password dictionary of weak passwords
		if( $db->getCell( 'SELECT 1 FROM `passworddictionary` WHERE `password` = ? LIMIT 1',
				array( $password ), self::$conn_id ) == 1 ){
			$errors->setMessage( 'password.strength.dictionary', 'error', 'password' );
			throw $errors;
		}

		$strength += ( $len * 4 ); #gets a bump in strength of 4 times the length

		$chars = StringLib::mb_count_chars( $password, 1 );
		$charskey = array_keys( $chars );
		$bruteForceMult = 0;
		$charsetsUsed = array();

		#add points for using characters from designated sets and compute brute force metric
		foreach( self::$pswrdCharClasses as $id => $charspec ){
			$set = str_split( $charspec[ 0 ] );
			$strenAddMult = $charspec[ 1 ];
			if( count( array_intersect( $charskey, $set ) ) > 0 ){
				$bruteForceMult += count( $set );
				$tmp = array_diff( $charskey, $set );
				$charsUsedFromSet = count( $charskey ) - count( $tmp );
				$strength += ( $len - ( $charsUsedFromSet ) ) * $strenAddMult;
				$charskey = $tmp;
				$charsetsUsed[] = $id;
			}
		}

		#any character not in the sets gives an added bonus of 10
		if( count( $chars ) > 0 ){
			$strength += 10;
			$charsetsUsed[] = 'notset';
		}

		#fail password because it is too vulnerable to brute force attacks
		if( ( $len * $bruteForceMult ) < ( ( isset( $options[ 'minpasswordbrutestrength' ] )? $options[ 'minpasswordbrutestrength' ]: 1 ) ) ){
			$errors->setMessage( 'password.strength.bruteforce', 'error', 'password' );
			throw $errors;
		}

		#deduct points for using just one character classes
		# or add points for 2 or more character classes
		if( count( $charsetsUsed ) <= 1 ){
			$strength -= $len;
			$errors->setMessage( 'password.strength.bruteforce2', 'warning', 'password' );
		}
		else if( count( $charsetsUsed ) > 2 )
			$strength += count( $charsetsUsed ) * 2;

		$entropy = StringLib::entropy( $password );

		#fail password if minimum entropy is not met or simply
		#penalize if not at the set threshold
		#add points if above the threshold
		if( isset( $options[ 'minpasswordentropy' ] ) && $entropy < $options[ 'minpasswordentropy' ] ){
			$errors->setMessage( 'password.strength.entropy', 'error', 'password' );
			throw $errors;
		}
		else if( isset( $options[ 'passwordentropythreshold' ] ) && $entropy < $options[ 'passwordentropythreshold' ] ){
			$errors->setMessage( 'password.strength.entropy', 'warning', 'password' );
			$strength -= $len;
		}
		else
			$strength += ceil( $entropy * $len );

		#fail password if minimum strength requirement is not met
		if( isset( $options[ 'minpasswordoverallstrength' ] ) && $strength < $options[ 'minpasswordoverallstrength' ] ){
			$errors->setMessage( 'password.strength.requirement', 'error', 'password' );
			throw $errors;
		}

		return array( $strength, $errors );
	}

	public static function listUsers( RedbeanService $db, $whereRestriction = '', $start_id = 0, $searchParam = '', $direction = NULL, $limit = NULL, $asBeans = false, $exclude_ids = [], $associated_contacts = [] ){
		if( $limit === NULL )
			$direction = NULL;
		if( $whereRestriction == '' )
			$whereRestriction = ' 1=1';

		$db->doSetup( self::$conn_id );
		$sql = 'SELECT u.* FROM `user` AS u';
		$where = ' WHERE' . $whereRestriction;
		$args = array();

		if( count( $associated_contacts ) > 0 ){
			$where .= ' AND u.`profile_id` IN ( ' . $db->genSlots( $associated_contacts ) . ' )';
			$args = array_merge( $args, $associated_contacts );
		}

		if( $direction !== NULL ){
			$where .= ' AND u.`id`' . ( ( $direction === 'prv' )? '<': '>' ) . '?';
			$args[] = $start_id;
		}

		if( $searchParam !== '' ){
			$where .= ' AND u.`username` LIKE ?';
			$args[] = $searchParam;
		}

		if( count( $exclude_ids ) > 0 ){
			$where .= ' AND u.`id` NOT IN (' . $db->genSlots( $exclude_ids ) . ')';
			$args = array_merge( $args, $exclude_ids );
		}

		$sql .= $where;
		$sql .= ' ORDER BY u.`id` ' . (( $direction === 'prv' )? 'DESC': 'ASC');

		if( $limit !== NULL ){
			$sql .= ' LIMIT ?';
			$args[] = $limit;
		}

		$return = $db->getAll( $sql, $args );
		if( $direction === 'prv' )
			$return = array_reverse( $return );

		if( $asBeans )
			$return = $db->convertToBeans( 'user', $return );

		return $return;
	}
}