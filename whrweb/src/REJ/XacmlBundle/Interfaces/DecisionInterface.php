<?php

namespace REJ\XacmlBundle\Interfaces;

interface DecisionInterface{
	//decisions
	const DENY = 0;
	const PERMIT = 1;
	const INDETERMINATE = 3;
	const NOTAPPLICABLE = 4;
	const INDETERMINATEDENYPERMIT = 3;
	const INDETERMINATEDENY = 5;
	const INDETERMINATEPERMIT = 6;

	//status codes
	const MISSING_ATTRIBUTE = 'urn:oasis:names:tc:xacml:1.0:status:missing-attribute';
	const OK = 'urn:oasis:names:tc:xacml:1.0:status:ok';
	const PROCESSING_ERROR = 'urn:oasis:names:tc:xacml:1.0:status:processing-error';
	const SYNTAX_ERROR = 'urn:oasis:names:tc:xacml:1.0:status:syntax-error';

	public function setDecision( $d );
	public function getDecision();
	public function getDecisionString( $showExtendedIndeterminate = false );
	public function isPermit( $set = false );
	public function isDeny( $set = false );
	public function isNotApplicable( $set = false );
	public function isIndeterminate( $set = false );
	public function isTargetIndeterminate( $set = NULL );
	public function isIndeterminateDP( $set = false );
	public function isIndeterminateD( $set = false );
	public function isIndeterminateP( $set = false );
	public function getExtendedIndeterminate();
	public function setExtendedIndeterminate( $value );

	public function setStatus( $s );
	public function getStatus();
	public function isStatusProcessingError( $set = false );
	public function isStatusSyntaxError( $set = false );
	public function isStatusMissingAttribute( $set = false );
	public function isStatusError( $set = false );
	public function isStatusOk( $set = false );
	public function setStatusMessage( $s );
	public function getStatusMessage();
	public function setStatusDetail( $s );
	public function getStatusDetail();
	public function addMissingAttrs( array $s );
	public function getMissingAttributes();

	public function hasObligations();
	public function hasAdvice();
	public function clearObligations();
	public function clearAdvice();
	public function getNextObligation();
	public function getNextAdvice();
	public function getAdvice();
	public function getObligations();
	public function setAdvices( array $arr );
	public function setObligations( array $arr );

	public function __toString();
}