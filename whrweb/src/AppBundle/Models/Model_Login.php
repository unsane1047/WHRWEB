<?php
namespace AppBundle\Models;

use AppBundle\Models\Model,
	AppBundle\Models\Model_User,
	AppBundle\Libraries\StringLib;

class Model_Login extends Model{
	static $conn_id = 'default';

	protected function normalize_username( $value ){
		return mb_strtolower(
			StringLib::trim(
				StringLib::singleLineInputSpacesOnly(
					StringLib::normalize(
						StringLib::replace_invalid_utf8( $value )
					)
				)
			)
		);
	}

	protected function normalize_attempted( $value ){
		return $this->as_dateTime( $value );
	}

	protected function normalize_invalidated( $value ){
		return $this->as_boolean( $value, false );
	}

	public function isValid(){
		$return = false;

		$sql = 'SELECT `invalidated` FROM `login` WHERE `user_id` = ? AND `id` = ? ORDER BY `attempted` DESC';
		$args = array( $this->bean->user_id, $this->bean->id );

		if( $this->service instanceOf RedbeanService ){
			try{
				$tmp = $this->service->getCell( $sql, $args );

				if( $tmp != 1 && $tmp !== NULL )
					$return = true;
			}catch( \Exception $e ){}
		}

		return $return;
	}

	public function invalidate(){
		try{
			$this->bean->invalidated = 1;
			$this->persist();
		}catch( \Exception $e ){
			return false;
		}
		return true;
	}

	public function revalidate(){
		try{
			$this->bean->invalidated = 0;
			$this->persist();
		}catch( \Exception $e ){
			return false;
		}
		return true;
	}

	static function invalidateByUser( RedbeanService $db, Model_User $user ){
		$user = $user->unbox();
		$sql = 'UPDATE `login` SET `invalidated` = ? WHERE `user_id` = ?';
		$args = array( 1, $user->id );
		try{
			$db->exec( $sql, $args, self::$conn_id );
			return true;
		}catch( \Exception $e ){}

		return false;
	}

	static function revalidateByUser( RedbeanService $db, Model_User $user ){
		$user = $user->unbox();
		$sql = 'UPDATE `login` SET `invalidated` = ? WHERE `user_id` = ?';
		$args = array( 0, $user->id );
		try{
			$db->exec( $sql, $args, self::$conn_id );
			return true;
		}catch( \Exception $e ){}
		return false;
	}

}