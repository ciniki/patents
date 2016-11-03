<?php
//
// Description
// -----------
// This function will process a web request for the blog module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_patents_web_processRequest(&$ciniki, $settings, $business_id, $args) {

    if( !isset($ciniki['business']['modules']['ciniki.patents']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.patents.17', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );

    //
    // Setup titles
    //
    if( count($page['breadcrumbs']) == 0 ) {
        $page['breadcrumbs'][] = array('name'=>'Patents', 'url'=>$args['base_url']);
    }

    $display = '';
    $ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

    //
    // Setup the base url as the base url for this page. This may be altered below
    // as the uri_split is processed, but we do not want to alter the original passed in.
    //
    $base_url = $args['base_url']; // . "/" . $args['blogtype'];

    //
    // Check if we are to display an image, from the gallery, or latest images
    //
    $display = '';

//    $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($categories, true) . "</pre>");
//  return array('stat'=>'ok', 'page'=>$page);

    $uri_split = $args['uri_split'];
   
    //
    // Check for an patent
    //
    if( isset($uri_split[0]) && $uri_split[0] != '' ) {
        $patent_permalink = $uri_split[0];
        $display = 'patent';
        //
        // Check for gallery pic request
        //
        if( isset($uri_split[1]) && $uri_split[1] == 'gallery'
            && isset($uri_split[2]) && $uri_split[2] != '' 
            ) {
            $image_permalink = $uri_split[2];
            $display = 'patentpic';
        }
        $ciniki['response']['head']['og']['url'] .= '/' . $patent_permalink;
        $base_url .= '/' . $patent_permalink;
    }

    //
    // No patent specified, display list
    //
    else {
        $display = 'list';
    }

    if( $display == 'list' ) {
        //
        // Display list as thumbnails
        //
        $strsql = "SELECT id, name, permalink, primary_image_id AS image_id, synopsis, 'yes' AS is_details "
            . "FROM ciniki_patents "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND status = 10 "
            . "AND (flags&0x01) = 0x01 "
            . "";
        $strsql .= "ORDER BY sequence, name ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.patents', 'patent');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"There are currently no patents available.");
        } else {
            $page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$base_url, 'list'=>$rc['rows']);
        }
    }

    elseif( $display == 'patent' || $display == 'patentpic' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'patents', 'private', 'patentLoad');
        $rc = ciniki_patents_patentLoad($ciniki, $business_id, array('permalink'=>$patent_permalink, 'images'=>'yes'));
        if( $rc['stat'] == 'noexist' ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.patents.18', 'msg'=>"We're sorry, the patent you requested does not exist."));
        }
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['patent']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.patents.19', 'msg'=>"We're sorry, the patent you requested does not exist."));
        } elseif( !isset($rc['patent']['status']) || $rc['patent']['status'] != 10 ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.patents.20', 'msg'=>"We're sorry, the page you requested is not available."));
        } elseif( !isset($rc['patent']['flags']) || ($rc['patent']['flags']&0x01) != 0x01 ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.patents.21', 'msg'=>"We're sorry, the page you requested is not available."));
        } else {
            $patent = $rc['patent'];
            $page['title'] = $patent['name'];
            $page['breadcrumbs'][] = array('name'=>$patent['name'], 'url'=>$base_url);
            //
            // Check if to display picture
            //
            if( $display == 'patentpic' ) {
                
                if( !isset($patent['images']) || count($patent['images']) < 1 ) {
                    $page['blocks'][] = array('type'=>'message', 'section'=>'patent-image', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
                } else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
                    $rc = ciniki_web_galleryFindNextPrev($ciniki, $patent['images'], $image_permalink);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    if( $rc['img'] == NULL ) {
                        $page['blocks'][] = array('type'=>'message', 'section'=>'patent-image', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
                    } else {
                        $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$base_url . '/gallery/' . $image_permalink);
                        if( $rc['img']['title'] != '' ) {
                            $page['title'] .= ' - ' . $rc['img']['title'];
                        }
                        $block = array('type'=>'galleryimage', 'section'=>'patent-image', 'primary'=>'yes', 'image'=>$rc['img']);
                        if( $rc['prev'] != null ) {
                            $block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                        }
                        if( $rc['next'] != null ) {
                            $block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
                        }
                        $page['blocks'][] = $block;
                    }
                }
            } else {
                if( isset($patent['primary_image_id']) && $patent['primary_image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$patent['primary_image_id'], 
                        'title'=>$patent['name'], 'caption'=>$patent['primary_image_caption']);
                }
                if( isset($patent['description']) && $patent['description'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$patent['description']);
                } elseif( isset($patent['synopsis']) && $patent['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$patent['synopsis']);
                }
                // Add share buttons  
                if( !isset($settings['page-patents-share-buttons']) || $settings['page-patents-share-buttons'] == 'yes' ) {
                    $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$patent['name'], 'tags'=>array());
                }

                //
                // Check if additional images
                //
                if( isset($patent['images']) && count($patent['images']) > 0 ) {
                    $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'base_url'=>$base_url . '/gallery', 'images'=>$patent['images']);
                }
            }
        }
    }

    //
    // Return error if nothing found to display
    //
    else {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.patents.22', 'msg'=>"We're sorry, the page you requested is not available."));
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
