<?php
//
// Description
// ===========
// This method will return all the information about an patent.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the patent is attached to.
// patent_id:          The ID of the patent to get the details for.
//
// Returns
// -------
//
function ciniki_patents_patentGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'patent_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Patent'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'patents', 'private', 'checkAccess');
    $rc = ciniki_patents_checkAccess($ciniki, $args['tnid'], 'ciniki.patents.patentGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Patent
    //
    if( $args['patent_id'] == 0 ) {
        $patent = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'status'=>'10',
            'flags'=>'1',
            'order'=>'1',
            'primary_image_id'=>'0',
            'primary_image_caption'=>'',
            'synopsis'=>'',
            'description'=>'',
        );
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.patents', 0x01) ) {
            $strsql = "SELECT MAX(sequence) AS seq FROM ciniki_patents WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.patents', 'max');
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
            if( isset($rc['max']['seq']) && $rc['max']['seq'] > 0 ) {
                $patent['order'] = $rc['max']['seq'] + 1;
            }
        }
    }

    //
    // Get the details for an existing Patent
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'patents', 'private', 'patentLoad');
        $rc = ciniki_patents_patentLoad($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $patent = $rc['patent'];
    }

    return array('stat'=>'ok', 'patent'=>$patent);
}
?>
