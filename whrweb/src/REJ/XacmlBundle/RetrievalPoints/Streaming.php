<?php

namespace REJ\XacmlBundle\RetrievalPoints;

use REJ\XacmlBundle\Interfaces\PolicyRetrievalPointInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Interfaces\XacmlReaderInterface;
use REJ\XacmlBundle\Readers\StreamingReader;
use REJ\XacmlBundle\Exceptions\SyntaxErrorException;
use REJ\XacmlBundle\Exceptions\ProcessingErrorException;
use REJ\XacmlBundle\Exceptions\PolicyNotFoundException;
use Symfony\Component\Finder\Finder;
//need to implement stacks for different versions and ways to move between those versions in the same policy set and handling to namespaces
//implement version matching as defined in spec
class Streaming implements PolicyRetrievalPointInterface{
	protected $finder;
	protected $algo;

	public function __construct( $locString = '', $algo = '' ){
		if( empty( $locString ) )
			$locString = dirname( __DIR__ ) . '\Tests\Policies';

		$this->setPolicyStoreLocation( $locString )
			->setCombiningAlgo( $algo );
	}

	public function setPolicyStoreLocation( $locString ){
		try{
			$this->finder = new Finder();
			$this->finder->files()->ignoreUnreadableDirs()->in( $locString );
		}catch( \Exception $e ){
			throw new ProcessingErrorException( sprintf( 'unable to set policy store location as %s', $locString ), 0, $e );
		}

		return $this;
	}

	public function setCombiningAlgo( $algo = '' ){
		switch( $algo ){
			case 'urn:oasis:names:tc:xacml:1.1:policy-combining-algorithm:ordered-permit-overrides':
			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:permit-overrides':
			case 'urn:oasis:names:tc:xacml:1.1:policy-combining-algorithm:ordered-deny-overrides':
			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:deny-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:permit-unless-deny':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:deny-unless-permit':
			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:only-one-applicable':
			case 'urn:oasis:names:tc:xacml:1.0:policy-combining-algorithm:first-applicable':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:permit-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:ordered-permit-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:deny-overrides':
			case 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:ordered-deny-overrides':
			break;

			default:
				$algo = 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:deny-overrides';
			break;
		}
		$this->algo = $algo;
		return $this;
	}

	public function find( RequestInterface $request ){ //need to implement policy version filtering here
		$reader = new StreamingReader();
		$policy = '';

		try{
			$policy = $this->getPolicyFromRequest( $request );
			if( $policy !== false ){
				$finder = clone $this->finder;
				$finder->name( $policy );
				$finder = $finder->getIterator();
				$tmp = iterator_count( $finder );

				//will need to implement version filtering here

				if( $tmp === 1 ){
					$finder->rewind();
					$policy = $finder->current();
					$policy = $policy->getRealpath();
				}
				else if( $tmp > 1 ){
					$synthetic = $this->createPolicySet( $finder, $policy );
					$policy = '';
					$reader->XML( $synthetic );
				}
				else
					$policy = '';
			}
			else
				$policy = '';

		}catch( \Exception $e ){}

		if( !empty( $policy )  )
			$reader->open( $policy );

		return $this->getRoot( $reader );
	}

	public function findSub( $url, $version, $earliestVersion, $latestVersion ){
		$reader = new StreamingReader();
		$policy = '';

		try{
			$policy = $this->createURLfromVersion( $url, $version, $earliestVersion, $latestVersion );
			$finder = clone $this->finder;
			$finder->name( $policy );
			$finder = $finder->getIterator();
			$tmp = iterator_count( $finder );

			if( $tmp === 1 ){
				$finder->rewind();
				$policy = $finder->current();
				$policy = $policy->getRealpath();
			}
			else
				$policy = '';

		}catch( \Exception $e ){}

		if( !empty( $policy )  )
			$reader->open( $policy );

		return $this->getRoot( $reader );
	}

	public function getInline( XacmlReaderInterface $r ){ //will need to handle if there are different namespaced versions inside the policy set eventually
		$class = 'AppBundle\\Security\\Core\\Authorization\\Xacml\\v3_0\\';
		if( $r->elementName() == 'Policy' ){
			$class .= 'Policy';
			$p = new $class( $r );
		}
		else{
			$class .= 'PolicySet';
			$p = new $class( $r, $this );
		}
		
		return $p;
	}

	protected function getPolicyFromRequest( RequestInterface $request ){ //implement using request to determine which document to load
		try{
			$resource = $request->getAttribute( 'resource', 'type', 'anyuri' );
			$resource = $resource->getValue();
		}catch( \Exception $e ){
			$resource = array();
		}
		try{
			$subject = $request->getAttribute( 'subject', 'id', 'string' );
			$subject = $subject->getValue();
		}catch( \Exception $e ){
			$subject = array();
		}

		$pattern = array( '/' );
		$tmp = array();
		$safeReplace = '/[\\\\\/:\*\?"<>\|\.]/ui';
		foreach( $resource as $r ){
			$r = preg_replace( $safeReplace, '', $r );
			$tmp[] = preg_quote( $r, '/' );
		}
		if( count( $tmp ) > 0 ){
			$tmp2 = implode( '|' , $tmp );
			$tmp2 = '(' . $tmp2 . ')';
			$pattern[] = $tmp2;
			$tmp = array();
		}

		foreach( $subject as $r ){
			$r = preg_replace( $safeReplace, '', $r );
			$tmp[] = '_' . preg_quote( $r, '/' );
		}
		if( count( $tmp ) > 0 ){
			$tmp2 = implode( '|' , $tmp );
			$tmp2 = '(' . $tmp2 . ')?';
			$pattern[] = $tmp2;
			$tmp = array();
		}

		$pattern[] = '(\.plc|\.plcst)\.xml$/';

		if( count( $pattern ) > 2 )
			return implode( $pattern );
		return false;
	}

	protected function createURLfromVersion( $url, $version, $earliestVersion, $latestVersion ){ //implement version matching to modify the url
/*
A version match is '.'-separated, like a version string.  A number represents a direct numeric match.  A '*' means that any single number is valid.  A '+' means that any number, and any subsequent numbers, are valid.  In this manner, the following four patterns would all match the version string '1.2.3': '1.2.3', '1.*.3', '1.2.*' and ‘1.+'.
*/
		return $url;
	}

	protected function createPolicySet( $finder, $policy ){//add version specification support here
		$dom = new \DomDocument();
		$dom->formatOutput = true;
		$pset = $dom->createElement( 'PolicySet' );
		$pset->setAttribute( 'xmlns', 'urn:oasis:names:tc:xacml:3.0:core:schema:wd-17' );
		$pset->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
		$pset->setAttribute( 'PolicySetId', $policy );
		$pset->setAttribute( 'Version', '1.0' );
		$pset->setAttribute( 'PolicyCombiningAlgId', $this->algo );
		$dom->appendChild( $pset );

		foreach( $finder as $fileInfo ){
			$name = $fileInfo->getFilename();
			if( $fileInfo->getBaseName( '.plc.xml' ) !== $name ){
				$tmp = $dom->createElement( 'PolicyIdReference' );
				$tmp->appendChild( $dom->createTextNode( $name ) );
				$pset->appendChild( $tmp );
			}
			else if( $fileInfo->getBaseName( '.plcst.xml' ) !== $name ){
				$tmp = $dom->createElement( 'PolicySetIdReference' );
				$tmp->appendChild( $dom->createTextNode( $name ) );
				$pset->appendChild( $tmp );
			}
		}

		return $dom->saveXML();
	}

	protected function getRoot( StreamingReader $r ){
		$root = NULL;

		try{
			while( $r->read() ){
				if( $r->isElement() ){
					$ver = $r->getAttribute( 'xmlns' );

					if( $ver === NULL )
						continue;
					else if( $ver === 'urn:oasis:names:tc:xacml:3.0:core:schema:wd-17' )
						$ver = 'AppBundle\\Security\\Core\\Authorization\\Xacml\\v3_0\\';
					else
						throw new SyntaxErrorException( 'Policy not presented in a recognized XACML namespace.' );

					if( $r->elementName() == 'PolicySet' ){
						$class = $ver . 'PolicySet';
						$root = new $class( $r, $this );
						break;
					}
					else if( $r->elementName() == 'Policy' ){
						$class = $ver . 'Policy';
						$root = new $class( $r );
						break;
					}

				}
			}
		}catch( \Symfony\Component\Debug\Exception\ContextErrorException $e ){
			if( $e->getMessage() === 'Warning: XMLReader::read(): Load Data before trying to read' )
				throw new PolicyNotFoundException();
			throw $e;
		}
		if( $root !== NULL )
			return $root;
		throw new SyntaxErrorException( 'Policy not presented in a recognized XACML namespace.' );
	}
}