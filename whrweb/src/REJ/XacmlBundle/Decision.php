<?php

namespace REJ\XacmlBundle\Decision;

use REJ\XacmlBundle\Interfaces\DecisionInterface;
use REJ\XacmlBundle\Interfaces\RequestInterface;
use REJ\XacmlBundle\Exceptions\MissingAttributeErrorException;

class Decision implements DecisionInterface{

	protected $obligations;
	protected $advice;
	protected $decision;
	protected $extendedIndeterminateValue;
	protected $status;
	protected $statusMessage;
	protected $statusDetail;
	protected $missingAttrs = array();
	protected $targetIndeterminate;

	public function __construct(){
		$this->obligations = array();
		$this->advice = array();
		$this->decision = DecisionInterface::DENY;
		$this->targetIndeterminate = false;
		$this->extendedIndeterminateValue = NULL;
		$this->status = DecisionInterface::OK;
		$this->statusMessage = '';
		$this->statusDetail = '';
	}

	public function setDecision( $d ){
		$this->decision = $d;
		return $this;
	}

	public function getDecision(){
		return $this->decision;
	}

	public function getDecisionString( $showExtendedIndeterminate = false ){
		$return = 'unknown';
		switch( $this->decision ){
			case DecisionInterface::PERMIT;
				$return = 'Permit';
			break;
			case DecisionInterface::DENY;
				$return = 'Deny';
			break;
			case DecisionInterface::NOTAPPLICABLE;
				$return = 'NotApplicable';
			break;
			case DecisionInterface::INDETERMINATE;
				$return = 'Indeterminate';
				if( $showExtendedIndeterminate ){
					if( $this->isIndeterminateD() )
						$return .= 'D';
					else if( $this->isIndeterminateP() )
						$return .= 'P';
					else if( $this->extendedIndeterminateValue === DecisionInterface::INDETERMINATEDENYPERMIT )
						$return .= 'DP';

					if( $this->isTargetIndeterminate() )
						$return .= ': TARGET';
				}
			break;
		}

		return $return;
	}

	public function isPermit( $set = false ){
		if( !$set )
			return ( $this->decision === DecisionInterface::PERMIT );
		$this->decision = DecisionInterface::PERMIT;
		return $this;
	}

	public function isDeny( $set = false ){
		if( !$set )
			return ( $this->decision === DecisionInterface::DENY );
		$this->decision = DecisionInterface::DENY;
		return $this;
	}

	public function isNotApplicable( $set = false ){
		if( !$set )
			return ( $this->decision === DecisionInterface::NOTAPPLICABLE );
		$this->decision = DecisionInterface::NOTAPPLICABLE;
		return $this;
	}

	public function isIndeterminate( $set = false ){
		if( !$set )
			return ( $this->decision === DecisionInterface::INDETERMINATE );
		$this->decision = DecisionInterface::INDETERMINATE;
		return $this;
	}

	public function isTargetIndeterminate( $set = NULL ){
		if( $set === NULL )
			return $this->targetIndeterminate;
		$this->targetIndeterminate = ( ( $set === true)? true: false );
		return $this;
	}

	public function isIndeterminateDP( $set = false ){
		if( !$set )
			return ( $this->isIndeterminate() && ( !isset( $this->extendedIndeterminateValue ) || $this->extendedIndeterminateValue === DecisionInterface::INDETERMINATEDENYPERMIT ) );
		$this->decision = DecisionInterface::INDETERMINATE;
		$this->extendedIndeterminateValue = DecisionInterface::INDETERMINATEDENYPERMIT;
		return $this;
	}

	public function isIndeterminateD( $set = false ){
		if( !$set )
			return ( $this->isIndeterminate() && ( $this->extendedIndeterminateValue === DecisionInterface::INDETERMINATEDENY ) );
		$this->decision = DecisionInterface::INDETERMINATE;
		$this->extendedIndeterminateValue = DecisionInterface::INDETERMINATEDENY;
		return $this;
	}

	public function isIndeterminateP( $set = false ){
		if( !$set )
			return ( $this->isIndeterminate() && ( $this->extendedIndeterminateValue === DecisionInterface::INDETERMINATEPERMIT ) );
		$this->decision = DecisionInterface::INDETERMINATE;
		$this->extendedIndeterminateValue = DecisionInterface::INDETERMINATEPERMIT;
		return $this;
	}

	public function getExtendedIndeterminate(){
		if( $this->isIndeterminate() )
			return $this->extendedIndeterminateValue;
		return NULL;
	}

	public function setExtendedIndeterminate( $value ){
		$this->extendedIndeterminateValue = $value;
	}

	public function setStatus( $s ){
		$this->status = $s;
		return $this;
	}

	public function getStatus(){
		return $this->status;
	}

	public function isStatusProcessingError( $set = false ){
		if( !$set )
			return ( $this->status === DecisionInterface::PROCESSING_ERROR );
		$this->status = DecisionInterface::PROCESSING_ERROR;
		return $this;
	}

	public function isStatusSyntaxError( $set = false ){
		if( !$set )
			return ( $this->status === DecisionInterface::SYNTAX_ERROR );
		$this->status = DecisionInterface::SYNTAX_ERROR;
		return $this;
	}

	public function isStatusMissingAttribute( $set = false ){
		if( !$set )
			return ( $this->status === DecisionInterface::MISSING_ATTRIBUTE );
		$this->status = DecisionInterface::MISSING_ATTRIBUTE;
		return $this;
	}

	public function isStatusError( $set = false ){
		if( !$set )
			return ( $this->isStatusProcessingError() || $this->isStatusSyntaxError() );
		return $this->isStatusProcessingError( $set );
	}

	public function isStatusOk( $set = false ){
		if( !$set )
			return ( $this->status === DecisionInterface::OK );
		$this->status = DecisionInterface::OK;
		return $this;
	}

	public function setStatusMessage( $s ){
		$this->statusMessage = $s;
		return $this;
	}

	public function getStatusMessage(){
		return $this->statusMessage;
	}

	public function setStatusDetail( $s = '' ){
		$this->statusDetail = $s;
		return $this;
	}

	public function getStatusDetail(){
		if( count( $this->missingAttrs ) > 0 && $this->isStatusMissingAttribute() ){
			$e = new MissingAttributeErrorException();
			$e->addMissingAttributes( $this->missingAttrs );
			$this->statusDetail = $e->getMissingAttrDetail();
		}
		return $this->statusDetail;
	}

	public function addMissingAttrs( array $s ){
		$processed = array();
		foreach( $s as $i ){
			$index = $i[ 'name' ]
				. $i[ 'category' ]
				. $i[ 'dataType' ];
			if( isset( $i[ 'issuer' ] ) )
				$index .= $i[ 'issuer' ];
			if( isset( $i[ 'value' ] ) )
				$index .= $i[ 'value' ];
			$processed[ md5( $index ) ] = $i;
		}

		$this->missingAttrs = array_merge( $this->missingAttrs, $processed );
		return $this;
	}

	public function getMissingAttributes(){
		return $this->missingAttrs;
	}

	public function hasObligations(){
		return ( count( $this->obligations ) > 0 );
	}

	public function hasAdvice(){
		return ( count( $this->advice ) > 0 );
	}

	public function clearObligations(){
		$this->obligations = array();
		return $this;
	}

	public function clearAdvice(){
		$this->advice = array();
		return $this;
	}

	public function getNextObligation(){
		return array_shift( $this->obligations );
	}

	public function getNextAdvice(){
		return array_shift( $this->advice );
	}

	public function getAdvice(){
		return $this->advice;
	}

	public function getObligations(){
		return $this->obligations;
	}

	public function setAdvices( array $arr ){
		$this->advice = $arr;
		return $this;
	}

	public function setObligations( array $arr ){
		$this->obligations = $arr;
		return $this;
	}

	public function __toString(){
		try{
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;

			$result = $dom->createElement( 'Result' );
			$dom->appendChild( $result );

			$decision = $dom->createElement( 'Decision' );
			$decision->appendChild( $dom->createTextNode( $this->getDecisionString() ) );
			$result->appendChild( $decision );

			$status = $dom->createElement( 'Status' );
			$result->appendChild( $status );

			$statusCode = $dom->createElement( 'StatusCode' );
			$statusCode->setAttribute( 'Value', $this->getStatus() );
			$status->appendChild( $statusCode );

			$message = $this->getStatusMessage();

			if( !empty( $message ) ){
				$statusMessage = $dom->createElement( 'StatusMessage' );
				$statusMessage->appendChild( $dom->createTextNode( $message ) );
				$status->appendChild( $statusMessage );
			}

			$detail = $this->getStatusDetail();

			if( !empty( $detail ) ){
				$frag = $dom->createDocumentFragment();
				$frag->appendXML( $detail );
				
				$status->appendChild( $frag );
			}

			if( $this->hasObligations() ){
				$obligations = $dom->createElement( 'Obligations' );
				while( ( $obl = $this->getNextObligation() ) !== NULL ){
					$frag = $dom->createDocumentFragment();
					$frag->appendXML( $obl );
					$obligations->appendChild( $frag );
				}
				$result->appendChild( $obligations );
			}

			if( $this->hasAdvice() ){
				$advice = $dom->createElement( 'AssociatedAdvice' );
				while( ( $adv = $this->getNextAdvice() ) !== NULL ){
					$frag = $dom->createDocumentFragment();
					$frag->appendXML( $adv );
					$advice->appendChild( $frag );
				}
				$result->appendChild( $advice );
			}

			//implement attributes if we start supporting multi request
			return $dom->saveXML( $result );
		}catch( \Exception $e ){
							echo '<pre>';
							echo sprintf( "Exception: %s on line %s in %s\n", $e->getMessage(), $e->getLine(), $e->getFile() );
							while( $e = $e->getPrevious() )
								echo sprintf( "Exception: %s on line %s in %s\n", $e->getMessage(), $e->getLine(), $e->getFile() );
							echo '</pre>';
			return '';
		}
	}
}