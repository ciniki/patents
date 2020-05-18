//
// The app to add/edit patents patent images
//
function ciniki_patents_images() {
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_patents_images', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.patents.images.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.patent_id = 0;
        this.edit.sections = {
            '_image':{'label':'Image', 'type':'imageform',
                'gstep':1,
                'gtitle':function(p) { return (p.data.image_id != null && p.data.image_id > 0)?'Would you like to change this photo?':'Do you have an additional photo to add?';},
                'gmore':function(p) { return (p.data.image_id != null && p.data.image_id > 0)?
                    'Use the <b>Change Photo</b> button below to select a new photo from your computer or tablet.'
                    + ' If you would like to save the original photo to your computer, press the <span class="icon">G</span> button.'
                    :'Use the <b>Add Photo</b> button to select a photo from your computer or tablet.';},
                'fields':{
                    'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
                }},
            'info':{'label':'Information', 'type':'simpleform', 
                'gstep':2,
                'gtitle':'Additional Information',
                'fields':{
                    'name':{'label':'Title', 'type':'text',
                        'gtitle':'Do you have a title for this photo?',
                        'htext':'The title is optional, you can leave this blank.'
                        },
                    'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags,
                        'htext':'If you do not want this image visible on your website, press the hidden button.'},
                }},
            '_description':{'label':'Description', 'type':'simpleform', 
                'gstep':3,
                'gtitle':'How would you describe this photo?',
                'gmore':'This is optional, but can be used to describe the details of the photo.',
                'fields':{
                    'description':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
                }},
            '_save':{'label':'', 'buttons':{
            }},
            '_buttons':{'label':'', 
                'gstep':4,
                'gtext':function(p) { return (p.patent_image_id>0)?'Press the save button to update the additional image.':'Press the save button to add the additional image.';},
                'gmore':function(p) { return (p.patent_image_id>0)?'If you want to remove this additional photo, press the <em>Delete</em> button.':null;},
                'buttons':{
                    'save':{'label':'Save', 'fn':'M.ciniki_patents_images.saveImage();'},
                    'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_patents_images.deleteImage();'},
                }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.patents.imageHistory', 'args':{'tnid':M.curTenantID, 
                'patent_image_id':this.patent_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_patents_images.edit.setFieldValue('image_id', iid, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_patents_images.saveImage();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_patents_images', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.patent_id);
        } else if( args.patent_image_id != null && args.patent_image_id > 0 ) {
            this.showEdit(cb, args.patent_image_id);
        }
        return false;
    }

    this.showEdit = function(cb, iid, eid) {
        if( iid != null ) {
            this.edit.patent_image_id = iid;
        }
        if( eid != null ) {
            this.edit.patent_id = eid;
        }
        if( this.edit.patent_image_id > 0 ) {
            this.edit.reset();
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.patents.imageGet', 
                {'tnid':M.curTenantID, 'patent_image_id':this.edit.patent_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_patents_images.edit.data = rsp.image;
                    M.ciniki_patents_images.edit.refresh();
                    M.ciniki_patents_images.edit.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.data = {};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.patent_image_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.patents.imageUpdate', 
                    {'tnid':M.curTenantID, 
                    'patent_image_id':this.edit.patent_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_patents_images.edit.close();
                            }
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeFormData('yes');
            var rsp = M.api.postJSONFormData('ciniki.patents.imageAdd', 
                {'tnid':M.curTenantID, 'patent_id':this.edit.patent_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_patents_images.edit.close();
                        }
                    });
        }
    };

    this.deleteImage = function() {
        M.confirm('Are you sure you want to delete this image?',null,function() {
            var rsp = M.api.getJSONCb('ciniki.patents.imageDelete', {'tnid':M.curTenantID, 
                'patent_image_id':M.ciniki_patents_images.edit.patent_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_patents_images.edit.close();
                });
        });
    };
}
