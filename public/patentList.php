<?php
//
// Description
// -----------
// This method will return the list of Patents for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Patent for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'patents', 'private', 'checkAccess');
    $rc = ciniki_patents_checkAccess($ciniki, $args['tnid'], 'ciniki.patents.patentList');
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
        . "WHERE ciniki_patents.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_patents.sequence, ciniki_patents.name "
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
