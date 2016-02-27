<?php
//
// Description
// -----------
// This method will return the list of Patents for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Patent for.
//
// Returns
// -------
//
function ciniki_patents_patentList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'patents', 'private', 'checkAccess');
    $rc = ciniki_patents_checkAccess($ciniki, $args['business_id'], 'ciniki.patents.patentList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of patents
    //
    $strsql = "SELECT ciniki_patents.id, "
        . "ciniki_patents.name, "
        . "ciniki_patents.permalink, "
        . "ciniki_patents.status, "
        . "ciniki_patents.flags, "
        . "ciniki_patents.primary_image_id, "
        . "ciniki_patents.primary_image_caption, "
        . "ciniki_patents.synopsis, "
        . "ciniki_patents.description "
        . "FROM ciniki_patents "
        . "WHERE ciniki_patents.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.patents', array(
        array('container'=>'patents', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'status', 'flags', 'primary_image_id', 'primary_image_caption', 'synopsis', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['patents']) ) {
        $patents = $rc['patents'];
    } else {
        $patents = array();
    }

    return array('stat'=>'ok', 'patents'=>$patents);
}
?>
