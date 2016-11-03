<?php
namespace AppBundle\Models;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\Role\Role;
use AppBundle\Libraries\AppDateTime;
use AppBundle\Libraries\UnicodeString;
use AppBundle\Libraries\EmailAddress;
use AppBundle\Models\Model;
use AppBundle\Models\Login;
use AppBundle\Models\RedbeanService;
use AppBundle\Models\Exceptions\ValidationException;

class User extends Model implements AdvancedUserInterface, EquatableInterface{
	const SALT = 'fd03457d1553be9ee50548229bde127d52c68c4d0975f56a849d078c2fcaf2d8';
	static $conn_id = 'default';
	private static $allowed_roles = [
		'ROLE_ADMIN',
		'ROLE_DEV',
		'ROLE_ALLOWED_TO_SWITCH',
	];

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
		if( $a instanceOf User )
			$this->setLogin( $a->getLogin() );
		return parent::copyModel( $a );
	}

	public function getLogin(){
		return $this->login;
	}

	public function setLogin( Login $l = NULL ){
		$this->login = $l;
		return $this;
	}

	public function getRoles(){ //doesn't yet work really, need to implement this eventually
		if( $this->serialized )
			$roles = $this->serializedBean[ 'roles' ];
		else
			$roles = $this->bean->roles;

		$tmp = explode( ',', $this->bean->roles );
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
	#as we don't store unhashed passwords here
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
		if( !( $user instanceOf User ) )
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

	public function validate(){
		if( $this->serialized )
			throw new \Exception( 'must set service before validation' );

		if( empty( $this->bean->password_last_changed ) )
			$this->bean->password_last_changed = new AppDateTime();

		if( $this->bean->id === 0 || $this->bean->created === NULL ){ //set created if this is a new bean
			$this->bean->created = new AppDateTime();
			$this->bean->password_last_changed = new AppDateTime();
		}

		if( empty( $this->bean->username ) || !( ( new EmailAddress( $this->bean->username ) )->isValid() ) )
			$this->getException( true )->setMessage( 'user.username.error.invalid', 'error', 'username' );

		if( $this->bean->password === NULL )
			$this->getException( true )->setMessage( 'user.error.password', 'error', 'password' );

		if( $this->bean->hasChanged( 'password' ) ){
			$this->bean->password_last_changed = new AppDateTime();
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
		return serialize( [
			$serialized,
			$this->login,
			$this->roles_cache
		] );
	}

	public function unserialize( $serialized ){
		list(
			$serialized,
			$this->login,
			$this->roles_cache
		) = unserialize( $serialized );
		parent::unserialize( $serialized );
	}

	protected function normalize_username( $val ){
		return mb_strtolower( ( new UnicodeString( $val ) )
			->asSingleLineInputSpacesOnly()
			->trim()
			->encode() );
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
		return ( new UnicodeString( $val ) )
			->asSingleLineInputSpacesOnly()
			->trim()
			->encode();
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
			if( in_array( $v, self::$allowed_roles, true ) )
				$final[] = $v;
		}
		return implode( ',', $final );
	}

	public static function listUsers( RedbeanService $db, $whereRestriction = '', $start_id = 0, $searchParam = '', $direction = NULL, $limit = NULL, $asBeans = false, $exclude_ids = [], $associated_contacts = [] ){
		if( $limit === NULL )
			$direction = NULL;
		if( $whereRestriction == '' )
			$whereRestriction = ' 1=1';

		$db->doSetup( self::$conn_id );
		$sql = 'SELECT u.* FROM `user` AS u';
		$where = ' WHERE' . $whereRestriction;
		$args = [];

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