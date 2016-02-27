<?php
//
// Description
// -----------
// This method will add a new image for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the Image to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_patents_imageAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'patent_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Patent'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'patents', 'private', 'checkAccess');
    $rc = ciniki_patents_checkAccess($ciniki, $args['business_id'], 'ciniki.patents.imageAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

	//
	// Get a UUID for use in permalink
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.patents');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3146', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
	}
	$args['uuid'] = $rc['uuid'];

	//
	// Determine the permalink
	//
	if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        if( isset($args['name']) && $args['name'] != '' ) {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        } else {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['uuid']);
        }
	}

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink "
        . "FROM ciniki_patents_images "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND patent_id = '" . ciniki_core_dbQuote($ciniki, $args['patent_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.patents', 'image');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3147', 'msg'=>'You already have an image with this name, please choose another name'));
	}

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.patents');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the image to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.patents.image', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.patents');
        return $rc;
    }
    $patent_image_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.patents');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'patents');

    return array('stat'=>'ok', 'id'=>$patent_image_id);
}
?>
