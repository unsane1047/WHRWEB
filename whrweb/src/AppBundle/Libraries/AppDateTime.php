<?php
// src/AppBundle/Libraries/AppDateTime.php
namespace AppBundle\Libraries;

######
# AppDateTime object that deals with some of the problems of the \DateTime class and
#   provides some simple convinience methods
#####

use \DateTime,
	\DateTimeZone,
	\DateInterval,
	\Exception,
	\DateTimeInterface;

class AppDateTime extends \DateTime{
	const MARIADB_DATETIME = 'Y-m-d H:i:s';
	const MARIADB_DATE = 'Y-m-d';
	const MARIADB_TIME = 'H:i:s';
	const NORTHAMERICA24 = 'm/d/Y H:i:s';
	const NORTHAMERICA12 = 'm/d/Y h:i a';
	const NORTHAMERICADATE = 'm/d/Y';
	const YEAR = 'Y';
	const TIME24 = 'H:i:s';
	const TIME12 = 'h:i:s a';

	protected $format = self::MARIADB_DATETIME;

	static function createFromParts( $month, $day, $year, $hour, $minute, $second, $ampm, $timezoneoffset){
		if( $ampm !== 'pm' )
			$ampm = NULL;

		if( !checkdate( $month, $day, $year ) )
			return NULL;

		$formatted = sprintf('%04d-%02d-%02d %02d:%02d:%02d %2s %6s', $year, $month, $day, $hour, $minute, $second, $ampm, $timezoneoffset);
		return DateTime::createFromFormat('Y-m-d ' . ( ($ampm === 'pm')? 'g':'G' ) . ':i:s' . ( ($ampm === 'pm')? ' a':' ' ) . ' P', $ret);
	}

	static function timezoneNameToOffset( $timezone = '' ){ #parse a timezone name from php into its offset
		$tmp = date_default_timezone_get();
		date_default_timezone_set( $timezone );
		$offset = '+00:00';
		if( $timezone != '' && is_string( $timezone ) ){
			$now = new DateTime();
			$mins = $now->getOffset() / 60;
			$sgn = ( $mins < 0 ? -1 : 1 );
			$mins = abs( $mins );
			$hrs = floor( $mins / 60 );
			$mins -= $hrs * 60;
			$offset = sprintf( '%+03d:%02d', $hrs*$sgn, $mins );
		}
		date_default_timezone_set( $tmp );
		return $offset;
	}

	static function listTimezones(){
		static $timezones = [];

		if( count( $timezones ) < 1 ){
			$offsets = [];
			$now = new DateTime();

			$tzlist = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
			foreach( $tzlist as $zone ){
				$now->setTimezone( new DateTimeZone( $zone ) );
				$offsets[] = $offset = $now->getOffset();
				$timezones[ $zone ] = [ 'description' => '(' .
					self::formatOffset( $offset ) .
					') ' .
					self::formatTZname( $zone ),
					'offset' => $offset
				];
			}
			array_multisort( $offsets, $timezones );
		}

		return $timezones;
	}

	static function formatOffset( $offset ){
		$hours = intval( $offset / 3600 );
		$mins = abs( intval( $offset % 3600 / 60 ) );
		return ( 'UTC' . ( $offset ? sprintf( '%+03d:%02d', $hours, $mins ) : '' ) );
	}

	static function formatTZname( $name ){
		return str_replace( [ '_', 'St ' ], [ ' ', 'St.' ], $name ); 
	}

	function __toString(){
		return $this->format( $this->format );
	}

	function setFormat( $format ){
		$this->format = $format;
	}

	function diff( DateTimeInterface $datetime2, $absolute = false ){
		$diff = parent::diff( $datetime2, $absolute );
		range( 0, 0 ); //fixes a really weird bug where floating point numbers and range results are crapped on for one call after Datetime::diff() is called on some platforms

		if( $diff->format( '%a' ) != 6015 ) #php 5.3.x on windows screws things up so must go manual in this case
			return $diff;

		$d1 = $this->format( self::MARIADB_DATETIME );
		$d2 = $datetime2->format( self::MARIADB_DATETIME );
 
		$diff = ( strtotime( $d1 ) - strtotime( $d2 ) );
		$diff = new DateInterval( 'PT' . $diff . 'S' );
		
		return $diff;
	}

	function age( DateTimeInterface $now = NULL ){
		if( $now === NULL )
			$now = new DateTime();

		$diff = $now->diff( $this, true );
		range( 0, 0 ); //fixes a really weird bug in php < 5.3.6 where floating point numbers and range results are crapped on for one call after Datetime::diff() is called on some platforms

		return ( $diff->invert )? $diff->y: ( -1 * $diff->y );
	}

	static function duration( $ss, $format = '%2$02d:%3$02d:%4$02d', $highestUnit = 'h' ){
		switch( $highestUnit ){
			default:
			case 'd':
			case 'D':
				$highestUnit = 'd';
			break;

			case 'H':
			case 'h':
				$highestUnit = 'h';
			break;

			case 'm':
			case 'M':
				$highestUnit = 'm';
			break;

			case 's':
			case 'S':
				$highestUnit = 's';
			break;
		}

		$s = $ss%60;
		$m = floor(($ss%3600)/60);
		$h = floor(($ss%86400)/3600);
		$d = floor(($ss%2592000)/86400);

		if( $highestUnit !== 'd' ){
			$h += $d * 24;
			$d = 0;
			if( $highestUnit !== 'h' ){
				$m += $h * 60;
				$h = 0;
				if( $highestUnit !== 'm' )
					$s = $ss;
					$m = 0;
			}
		}

		return sprintf( $format, $d, $h, $m, $s );
	}

}