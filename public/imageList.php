<?php
//
// Description
// -----------
// This method will return the list of Images for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Image for.
//
// Returns
// -------
//
function ciniki_patents_imageList($ciniki) {
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
    $rc = ciniki_patents_checkAccess($ciniki, $args['tnid'], 'ciniki.patents.imageList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of images
    //
    $strsql = "SELECT ciniki_patents_images.id, "
        . "ciniki_patents_images.patent_id, "
        . "ciniki_patents_images.name, "
        . "ciniki_patents_images.permalink, "
        . "ciniki_patents_images.flags, "
        . "ciniki_patents_images.image_id, "
        . "ciniki_patents_images.description "
        . "FROM ciniki_patents_images "
        . "WHERE ciniki_patents_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.patents', array(
        array('container'=>'images', 'fname'=>'id', 
            'fields'=>array('id', 'patent_id', 'name', 'permalink', 'flags', 'image_id', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['images']) ) {
        $images = $rc['images'];
    } else {
        $images = array();
    }

    return array('stat'=>'ok', 'images'=>$images);
}
?>
