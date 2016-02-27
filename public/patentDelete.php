<?php
//
// Description
// -----------
// This method will delete an patent.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the patent is attached to.
// patent_id:            The ID of the patent to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_patents_patentDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'patent_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Patent'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'patents', 'private', 'checkAccess');
    $rc = ciniki_patents_checkAccess($ciniki, $args['business_id'], 'ciniki.patents.patentDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the patent
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_patents "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['patent_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.patents', 'patent');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['patent']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3141', 'msg'=>'Airlock does not exist.'));
    }
    $patent = $rc['patent'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.patents');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the patent
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.patents.patent',
        $args['patent_id'], $patent['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.patents');
        return $rc;
    }

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

    return array('stat'=>'ok');
}
?>
