<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_patents_objects($ciniki) {
    
    $objects = array();
    $objects['patent'] = array(
        'name'=>'Patent',
        'o_name'=>'patent',
        'o_container'=>'patents',
        'sync'=>'yes',
        'table'=>'ciniki_patents',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('name'=>'Options'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'primary_image_id'=>array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'primary_image_caption'=>array('name'=>'Primary Image Caption', 'default'=>''),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_patents_history',
        );
    $objects['image'] = array(
        'name'=>'Image',
        'o_name'=>'image',
        'o_container'=>'images',
        'sync'=>'yes',
        'table'=>'ciniki_patents_images',
        'fields'=>array(
            'patent_id'=>array('name'=>'Patent', 'ref'=>'ciniki.patents.patent'),
            'name'=>array('name'=>'Name', 'default'=>''),
            'permalink'=>array('name'=>'Permalink'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'image_id'=>array('name'=>'Image', 'default'=>'0', 'ref'=>'ciniki.images.image'),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_patents_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
