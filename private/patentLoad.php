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
// business_id:         The ID of the business the patent is attached to.
// patent_id:          The ID of the patent to get the details for.
//
// Returns
// -------
//
function ciniki_patents_patentLoad($ciniki, $business_id, $args) {
    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    $strsql = "SELECT ciniki_patents.id, "
        . "ciniki_patents.name, "
        . "ciniki_patents.permalink, "
        . "ciniki_patents.status, "
        . "ciniki_patents.flags, "
        . "ciniki_patents.sequence, "
        . "ciniki_patents.primary_image_id, "
        . "ciniki_patents.primary_image_caption, "
        . "ciniki_patents.synopsis, "
        . "ciniki_patents.description "
        . "FROM ciniki_patents "
        . "WHERE ciniki_patents.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    if( isset($args['permalink']) && $args['permalink'] != '' ) {
        $strsql .= "AND ciniki_patents.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' ";
    } elseif( isset($args['patent_id']) && $args['patent_id'] > 0 ) {
        $strsql .= "AND ciniki_patents.id = '" . ciniki_core_dbQuote($ciniki, $args['patent_id']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3156', 'msg'=>'That is not a valid patent.'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.patents', 'patent');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3142', 'msg'=>'Patent not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['patent']) ) {
        return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'3143', 'msg'=>'Unable to find Patent'));
    }
    $patent = $rc['patent'];
    $patent['flags_text'] = ((($patent['flags']&0x01) == 0x01) ? 'Visible' : 'Hidden');

    //
    // Load additional images if specified
    //
    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql = "SELECT id, "
            . "name, "
            . "permalink, "
            . "flags, "
            . "image_id, "
            . "description "
            . "FROM ciniki_patents_images "
            . "WHERE patent_id = '" . ciniki_core_dbQuote($ciniki, $patent['id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.patents', array(
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'title'=>'name', 'permalink', 'flags', 'image_id', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            $patent['images'] = $rc['images'];
            foreach($patent['images'] as $img_id => $img) {
                if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                    $rc = ciniki_images_loadCacheThumbnail($ciniki, $business_id, $img['image_id'], 75);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $patent['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        } else {
            $patent['images'] = array();
        }
    }

    return array('stat'=>'ok', 'patent'=>$patent);
}
?>
