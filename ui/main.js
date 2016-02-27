//
// This app will handle the listing, additions and deletions of patents.  These are associated business.
//
function ciniki_patents_main() {
	//
	// Panels
	//
	this.init = function() {
		//
		// patents panel
		//
		this.menu = new M.panel('Patents',
			'ciniki_patents_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.patents.main.menu');
        this.menu.sections = {
			'patents':{'label':'Patents', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No patents',
				'addTxt':'Add Patent',
				'addFn':'M.ciniki_patents_main.showEdit(\'M.ciniki_patents_main.menuShow();\',0);',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
            switch (j) {
                case 0: return d.name;
                case 1: return ((d.flags&0x01)==0x01?'Visible':'Hidden');
            }
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_patents_main.patentShow(\'M.ciniki_patents_main.menuShow();\',\'' + d.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_patents_main.showEdit(\'M.ciniki_patents_main.menuShow();\',0);');
		this.menu.addClose('Back');

		//
		// The patent panel 
		//
		this.patent = new M.panel('Patent',
			'ciniki_patents_main', 'patent',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.patents.main.patent');
		this.patent.data = {};
		this.patent.patent_id = 0;
		this.patent.sections = {
			'_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'_caption':{'label':'', 'aside':'yes', 
                'visible':function() {return M.ciniki_patents_main.patent.data.primary_image_caption!=''?'yes':'no'; },
                'list':{
                    'primary_image_caption':{'label':'Caption'},
                }},
			'info':{'label':'', 'aside':'yes', 'list':{
				'name':{'label':'Name'},
				'flags_text':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
				}},
			'synopsis':{'label':'Synopsis', 'type':'htmlcontent'},
			'description':{'label':'Description', 'type':'htmlcontent'},
			'images':{'label':'Gallery', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Additional Image',
				'addFn':'M.startApp(\'ciniki.patents.images\',null,\'M.ciniki_patents_main.patentShow();\',\'mc\',{\'patent_id\':M.ciniki_patents_main.patent.patent_id,\'add\':\'yes\'});',
				},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_patents_main.showEdit(\'M.ciniki_patents_main.patentShow();\',M.ciniki_patents_main.patent.patent_id);'},
				}},
		};
		this.patent.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.patents.imageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'patent_id':M.ciniki_patents_main.patent.patent_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.patent.addDropImageRefresh = function() {
			if( M.ciniki_patents_main.patent.patent_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.patents.patentGet', {'business_id':M.curBusinessID, 
					'patent_id':M.ciniki_patents_main.patent.patent_id, 'images':'yes'}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_patents_main.patent.data.images = rsp.patent.images;
						M.ciniki_patents_main.patent.refreshSection('images');
					});
			}
		};
		this.patent.sectionData = function(s) {
			if( s == 'synopsis' || s == 'description' ) { return this.data[s].replace(/\n/g, '<br/>'); }
			if( s == 'info' || s == '_caption' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.patent.fieldValue = function(s, i, d) { return this.data[i]; }
		this.patent.listLabel = function(s, i, d) { return d.label; };
		this.patent.listValue = function(s, i, d) {
			return this.data[i];
		};
		this.patent.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.patents.images\',null,\'M.ciniki_patents_main.patentShow();\',\'mc\',{\'patent_image_id\':\'' + d.id + '\'});';
		};
		this.patent.addButton('edit', 'Edit', 'M.ciniki_patents_main.showEdit(\'M.ciniki_patents_main.patentShow();\',M.ciniki_patents_main.patent.patent_id);');
		this.patent.addClose('Back');

		//
		// The panel for a site's menu
		//
		this.edit = new M.panel('Patent',
			'ciniki_patents_main', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.patents.main.edit');
		this.edit.data = null;
		this.edit.patent_id = 0;
        this.edit.sections = { 
			'_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'_caption':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_caption':{'label':'Caption', 'type':'text'},
				}},
            'general':{'label':'General', 'aside':'yes', 'fields':{
                'name':{'label':'Name', 'hint':'Patents name', 'type':'text', },
                'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
                }}, 
			'_synopsis':{'label':'Synopsis', 'fields':{
                'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
                }},
			'_description':{'label':'Description', 'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
                }},
			'_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_patents_main.patentSave();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_patents_main.patentRemove();'},
                }},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.patents.patentHistory', 'args':{'business_id':M.curBusinessID, 
				'patent_id':this.patent_id, 'field':i}};
		}
		this.edit.addDropImage = function(iid) {
			M.ciniki_patents_main.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_patents_main.patentSave();');
		this.edit.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_patents_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.menuShow(cb);
	}

	this.menuShow = function(cb, cat) {
        M.api.getJSONCb('ciniki.patents.patentList', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_patents_main.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
	};

	this.patentShow = function(cb, pid) {
		this.patent.reset();
		if( pid != null ) { this.patent.patent_id = pid; }
		M.api.getJSONCb('ciniki.patents.patentGet', {'business_id':M.curBusinessID, 'patent_id':this.patent.patent_id, 'images':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_patents_main.patent;
            p.data = rsp.patent;
            p.refresh();
            p.show(cb);
        });
	};

	this.showEdit = function(cb, pid) {
		this.edit.reset();
		if( pid != null ) { this.edit.patent_id = pid; }

		this.edit.sections._buttons.buttons.delete.visible = (this.edit.patent_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.patents.patentGet', {'business_id':M.curBusinessID, 'patent_id':this.edit.patent_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_patents_main.edit;
            p.data = rsp.patent;
            p.refresh();
            p.show(cb);
        });
	};

	this.patentSave = function() {
		if( this.edit.patent_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.patents.patentUpdate', 
					{'business_id':M.curBusinessID, 'patent_id':M.ciniki_patents_main.edit.patent_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_patents_main.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.patents.patentAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    if( rsp.id > 0 ) {
                        var cb = M.ciniki_patents_main.edit.cb;
                        M.ciniki_patents_main.edit.close();
                        M.ciniki_patents_main.patentShow(cb,rsp.id);
                    } else {
                        M.ciniki_patents_main.edit.close();
                    }
                });
			} else {
				this.edit.close();
			}
		}
	};

	this.patentRemove = function() {
		if( confirm("Are you sure you want to remove this patent?") ) {
			M.api.getJSONCb('ciniki.patents.patentDelete', 
				{'business_id':M.curBusinessID, 'patent_id':M.ciniki_patents_main.edit.patent_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_patents_main.edit.close();
				});
		}
	}
};
