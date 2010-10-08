dojo.provide("dsms.base");

dojo.require("dojo.back");
dojo.require("dsms.nls.ui");

dsms.base = {

    _title : '',
    _controller : '',
    _data : null,
    _thisUrl : null,
    _previousUrl : null,
    _pageCallback : null,
    
    setPageCallback : function(obj) {
		this._pageCallback = obj;
	},

    findParentForm : function(node) {
        // summary: Find the form that encloses the given node.
        while (node!=document) {
            if (node.tagName.toLowerCase()=='form')
                return node;
            node = node.parentNode;
        }
        return null;
    },

    _fixEditors : function() {
        var editors = dijit.registry.byClass('dijit.Editor')._hash;
        for (var id in editors) {
            var editorWidget = editors[id];
            var node = editorWidget.domNode;
            var form = dsms.base.findParentForm(node);
            var hidden = dojo.create('input', {name:id, type:'hidden'}, form);
            dojo.connect(form, 'onsubmit', (function(hidden, editorWidget) {
                return function () {
                    hidden.value = editorWidget.getValue(false);
                };
                })(hidden, editorWidget)
            );
            
            var ee = editorWidget;
            dojo.connect(ee, "onNormalizedDisplayChanged", function() {
            	var nn = dojo.query('div[class=][style^="height"]', this.domNode)[0];
            	var box = dojo.marginBox(this.iframe);
            	if (box.h!=dojo.style(nn, "height")) {
            		dojo.style(nn, "height", "auto");
            	}
            });
        }
    },

    checkRequired : function(e) {
        // summary: Checks required and number fields before allowing the
        // form to submit.
        var btn = e.target;
        var form = btn.form;        
        var errors = [];
        // check required
        var ctrls = dojo.query("[class~='required'] input", form);
        dojo.forEach(ctrls, function(item) {
            if (dojo.trim(item.value).length==0) {
                var lbl = item.parentNode.getElementsByTagName('label')[0].innerHTML;
                var msg = window.dsmsMsg.isRequired;
                errors.push(msg.replace('***', lbl));
            }
        });
        // check numbers
        ctrls = dojo.query("[class~='float'] input", form);
        dojo.forEach(ctrls, function(item) {
            var positive = dojo.hasClass(item, 'positive');
            if (!dojo.number.format(item.value)) {
                var lbl = item.parentNode.getElementsByTagName('label')[0].innerHTML;
                var msg = positive ? window.dsmsMsg.isPositiveNumber : window.dsmsMsg.isNumber;
                errors.push(msg.replace('***', lbl));
            } else if (positive && dojo.number.parse(item.value)<0) {
                var msgP = window.dsmsMsg.isPositiveNumber;
                errors.push(msgP.replace('***', lbl));
            }
        });
        // check integers
        ctrls = dojo.query("[class~='int'] input", form);
        dojo.forEach(ctrls, function(item) {
            var positive = dojo.hasClass(item, 'positive');
            if (!dojo.number.format(item.value)) {
                var lbl = item.parentNode.getElementsByTagName('label')[0].innerHTML;
                var msg = positive ? window.dsmsMsg.isPositiveNumber : window.dsmsMsg.isNumber;
                errors.push(msg.replace('***', lbl));
            } else if (positive && dojo.number.parse(item.value)<0) {
                var msgP = window.dsmsMsg.isPositiveNumber;
                errors.push(msgP.replace('***', lbl));
            }
        });

        if (errors.length>0) {
            dojo.stopEvent(e);
            var errMsg = errors.join('<br />');
            var dlg = new dijit.Dialog({title:'Error', content:errMsg})
            dlg.show();
        }
    },

    submit : function(form, loadOverride) {
        form = dsms.base.findParentForm(form);
        if (form) {
            var e = {target:form};
            dsms.base.loadInContent(e, false, {}, loadOverride);
        }
    },

    loadInContent : function(e, skipHistory, extraContent, loadOverride) {
        var isDirect = dojo.isString(e);
        var isForm = !isDirect && e.target.tagName.toLowerCase()=='form';
        var href = isDirect ? e : isForm ? e.target.action : e.target.href;
        if (href.indexOf('logout')>=0 || (!isDirect && dojo.hasClass(e.target, 'noasync'))) return;

        if (!isDirect && !extraContent)
            dojo.stopEvent(e);

        var isPost = isForm && e.target.method=='post';
        skipHistory = skipHistory || isPost;

        if (!isForm) {
            if (!isDirect && dojo.hasClass(e.target, 'back') && dsms.base._previousUrl) {
                href = dsms.base._previousUrl;
            }
            if (!dsms.base._thisUrl || !dsms.base._thisUrl.match('/edit/')) {
                // don't store when in edit'
                dsms.base._previousUrl = dsms.base._thisUrl;
            }
            dsms.base._thisUrl = href;
        }

        if (isForm) {
            dojo.query('select.select-all-on-submit', e.target).forEach( function(node) {
                dojo.query("option", node).forEach(function(n){
                    n.selected=true;
                });
            });
        }

        var xhrArgs = {
            url: href,
            load: function(response, ioArgs) {
                if (dojo.trim(response.substring(0, 30)).indexOf('<span id="newtitle"')==0) {
                    dsms.base.hideLoading();
                    
                    var matches = response.match(/.*<span.*>(.*)<\/span><span.*>(.*)<\/span>/);
                    dsms.base._updateTitle(matches[1]);
                    dsms.base.addFlashNews(matches[2]);
                    return response;
                }
                if (dijit.byId('editDialog')) {
                    dijit.byId('editDialog').destroyRecursive();
                }
                var main = dijit.byId("main_content");
                main.setContent(response);
                dsms.base.initBehavior(main.domNode);
                if (!skipHistory) {
                	var uth = dsms.base._urlToHash(ioArgs.url);
                	var base = uth[0];
                	var u = uth[1];

                    // TODO: add filter string
                    dojo.back.addToHistory({changeUrl:u,
                        handle: function() {
                            dsms.base.loadInContent(base, true);
                    }
                    });
                }
                dsms.base.hideLoading();
                return response;
            },
            error: function(){
                if (console && console.debug) console.debug(arguments);
                dsms.base.hideLoading();
                dsms.base.addFlashNews("Error", true);                
            }
        }
        var content = isForm? dojo.formToObject(e.target) : {};
        if (isForm && e.target._submit) content[e.target.__submit] = true;
        if (href.indexOf('lean=true')<0) content.lean = true;

        if (loadOverride) xhrArgs.load = loadOverride;
        if (extraContent) content = dojo.mixin(content, extraContent);
        
        xhrArgs.content = content;
        if (isPost)
            dojo.xhrPost(xhrArgs);
        else
            dojo.xhrGet(xhrArgs);

        dsms.base.showLoading();
    },
    
    _urlToHash : function(url) {
    	// summary: First element is raw url, second is hash (after # part).
    	if (url.substring(0, 1)=="/") {
        	// in chrome url can be root relative.
    		url = location.protocol + "//" + location.host + (location.port ? ':' + location.port : '') + url;
    	}
        var hasPhp = url.indexOf('.php')>0;
        var basePattern = hasPhp ? /(.*php).*/ : /(https?:\/\/.*?)\/.*/;
        var ctrlPattern = hasPhp ? /.*php([^\\?]*).*/ : /https?:\/\/.*?(\/[^\\?]*).*/;
        // extract base url
        var base = url.match(basePattern)[1];
        // now extract controller, action, params
        var u = url.match(ctrlPattern)[1];
        var filterMatch = url.match(/\?.*q=(.*)/);
        var filter = filterMatch ? filterMatch[1].split('&')[0] : null;
        base += u;
        u = u.split('/').join('-');
        if (u.indexOf('-')==0) u=u.substring(1);
        if (filter) {
            base += '?q=' + filter;
            u += '.' + filter;
        }
        return [base, u];
    },
    
    _hashToUrl : function(hash) {
        var hasPhp = location.href.indexOf('.php')>0;
        var base2Pattern = hasPhp ? /(.*php)[^\\?]*.*/ : /(https?:\/\/.*?)\/[^\\?]*.*/;
        var base = location.href.match(base2Pattern)[1];
    	return base + "/" + hash.split("-").join('/');
    },

    fadeInFive : function(node) {
        setTimeout(function() {
            var minimize = dojo.animateProperty({node:node, properties: { width: 1 }});
            var fade = dojo.fadeOut({ node: node });

            var anim = dojo.fx.combine([minimize, fade]);
            dojo.connect(anim, "onEnd", function() {dojo.destroy(node);} );

            anim.play();
        }, 5000);
    },

    addFlashNews : function(content, error) {
        var info = dojo.create('p', {'class':error?'error':'info'}, dojo.byId('header'));
        info.innerHTML = content;
        dsms.base.fadeInFive(info);
    },

    showLoading : function() {
        dojo.query("#overlayLoading").style({display:'block'});
    },

    hideLoading : function() {
        dojo.query("#overlayLoading").style({display:'none'});
    },

    _storeController : function() {
        var node = dojo.query('.selector')[0];
        var contr;
        if (node) {
            var id = node.name;
            var pos = id.indexOf('_');
            if (pos>=0) {
                contr = id.substring(0, pos);
            }
        } else {
            node = dojo.query('form')[0];
            if (node) {
                var action = node.action;
                var matcher = action.match(/admin\/(\w*)/);
                
                if (matcher) {
                	contr = matcher[1];
                } else {
                	matcher = action.match((/(\w*)\//));
                	if (matcher) contr = matcher[1];
                }
            }
            if (!contr) {
                node = dojo.byId('newtitle');
                if (node) {
                    contr = dojo.attr(node, 'class');
                }
            }
        }
        this._controller = contr;
    },

    _updateTitle : function(newTitle) {
        this._title = newTitle;
        dojo.query('#header h1')[0].innerHTML = newTitle;
        var docTitle = document.title;
        var pos = docTitle.indexOf(' | ');
        if (pos>=0) {
            docTitle = docTitle.substring(0, pos) + ' | ' + newTitle;
        } else {
            docTitle += ' | ' + newTitle;
        }
        document.title = docTitle;
    },

    initBehavior : function(node) {
        this._storeController();
        if (dojo.byId('newtitle')) {
            this._updateTitle(dojo.byId('newtitle').innerHTML);
        }
        if (dojo.byId('newflash')) {
            this.addFlashNews(dojo.byId('newflash').innerHTML);
        }
        dojo.query("a", node).onclick(dsms.base.loadInContent);
        dojo.query("input[name='_submit']", node).onclick(dsms.base.checkRequired);
        dojo.query("input[type='submit']", node).onclick(function(e){
            var b=e.target;
            if (b.name) b.form.__submit = b.name;
        });
        this._fixEditors();
        dojo.query("form[method='get']").onsubmit(dsms.base.loadInContent);
        dojo.query("form[method='post']").onsubmit( dsms.base.loadInContent );

        dojo.query('.datatable tr', node)
            .onmouseover(function(){ dojo.addClass(this,'selected'); })
            .onmouseout( function(){ dojo.removeClass(this,'selected'); } );

        dojo.query('.selector', node).onclick(function(e){ 
            var n = e.target;
            dsms.base.setCheckState(n, n.checked);
            dsms.base.removeSelectEverything();
        });

        dojo.query('.select-all', node).onclick(function(e){
            dojo.stopEvent(e);
            dojo.query('.selector').forEach( function(n) {
                n.checked = true;
                dsms.base.setCheckState(n, true);
            });
            dsms.base.addSelectEverything();
        });

        dojo.query('.select-none', node).onclick(function(e){
            dojo.stopEvent(e);
            dojo.query('.selector').forEach( function(n) {
                n.checked = false;
                dsms.base.setCheckState(n, false);
            });
            
            dsms.base.clearSelection();
            dsms.base.removeSelectEverything();
        });

        dojo.query('.select-everything', node).onclick(function(e){
            dojo.stopEvent(e);
            dsms.base.setSelectionFilter(this.rel);
            dojo.query('.select-everything').style({display:'none'});
            dojo.query('.select-everything-true').style({display:'inline'});
        });

        this.populateSelected();

        dojo.query('.multi button.switch', node).onclick(function(e){
            dojo.stopEvent(e);
            
            var ctrl = e.target;
            var parts = ctrl.id.split('_');
            var type = parts[0];
            var id = parts[1];

            var left = dijit.byId('avail_'+id);
            var right = dijit.byId('sel_'+id);

            if (type=='switchleft') {
                left.addSelected(right);
            } else {
                right.addSelected(left);
            }            
        });
        
        dojo.query('.multi button.mover', node).onclick(function(e){
            dojo.stopEvent(e);
            
            var ctrl = e.target;
            var parts = ctrl.id.split('_');
            var type = parts[0];
            var id = parts[1];
            
            var right = dijit.byId('sel_'+id).domNode;
            
            var moveUp = function(element) {
            	  for(i = 0; i < element.options.length; i++) {
            	    if(element.options[i].selected == true) {
            	      if(i != 0) {
            	        var temp = new Option(element.options[i-1].text,element.options[i-1].value);
            	        var temp2 = new Option(element.options[i].text,element.options[i].value);
            	        element.options[i-1] = temp2;
            	        element.options[i-1].selected = true;
            	        element.options[i] = temp;
            	      }
            	    }
            	  }
        	};
            var moveDown = 	function(element) {
            	  for(i = (element.options.length - 1); i >= 0; i--) {
            	    if(element.options[i].selected == true) {
            	      if(i != (element.options.length - 1)) {
            	        var temp = new Option(element.options[i+1].text,element.options[i+1].value);
            	        var temp2 = new Option(element.options[i].text,element.options[i].value);
            	        element.options[i+1] = temp2;
            	        element.options[i+1].selected = true;
            	        element.options[i] = temp;
            	      }
            	    }
            	  }
        	};
        	
        	if (type=='moveup') {
        		moveUp(right);
        	} else {
        		moveDown(right);
        	}
            
        });

        dojo.query("input[name='bulk_categorize']", node).onclick(function(e){
            dojo.stopEvent(e);
            var triggerControl = e.target;

            dsms.base.showCategoriesDialog(function(item) {
                dsms.base.loadInContent({target:triggerControl.form}, true,
                    {category:item.id[0]} );
            });
        });

        dojo.query("input.back", node).onclick(function(e){
            if (dsms.base._previousUrl) {
                dojo.stopEvent(e);
                dsms.base.loadInContent(dsms.base._previousUrl);
            }
        });
        
        dojo.query("a.img-preview", node).onclick(function(e){
        	dojo.stopEvent(e);
        	var title = dojo.query('label', this.parentNode)[0].innerHTML;
            var dlg = new dijit.Dialog({title:title, content:'<img src="' + this.href + '"/>'});
            dlg.show();
        });

        if (this._controller && this._pageCallback && this._pageCallback['init_'+this._controller]) {
        	this._pageCallback['init_'+this._controller]();
        }
    },
    
    initCsvControl : function() {
        var fn = function(q) {
            var list=dojo.query(q + " input[type='checkbox']");
            var first = list[0];
            if (!first) return;
            var checked = first.checked;
            
            list.forEach(function(n) {
                if (checked) n.checked = true;
            });

            list.onclick(function(e) {
                var node = e.target;
                if (node==first) {
                    var newVal = node.checked;
                    dojo.query("input[type='checkbox']", node.parentNode).forEach(function(m) {
                        if (m!=node) m.checked = newVal;
                    });
                } else {
                    first.checked = false;
                }
            });
        };
        fn(".odd .csvvalues");
        fn(".even .csvvalues");    	
    },

    setCheckState : function(node, value) {
        var row = node.parentNode.parentNode;
        if (value) {
            dojo.addClass(row, 'highlight');
            dsms.base.storeSelection(node, true);
        }
        else {
            dojo.removeClass(row, 'highlight');
            dsms.base.storeSelection(node, false);
        }
    },

    addSelectEverything : function() {
        if (dojo.query('.select-everything-true').style('display')[0]!='inline') {
            dojo.query('.select-everything').style({display:'inline'});
        }
    },

    removeSelectEverything : function() {
        // TODO:  must remove filter if found
        dojo.query('.select-everything').style({display:'none'});
        dojo.query('.select-everything-true').style({display:'none'});
        dsms.base.setSelectionFilter(false);
    },

    clearSelection : function(/*DOMNode*/ node) {
        // summary : Clears state info for current controller.
        if (!node) {
            node = dojo.query('.selector')[0];
            if (!node) return;
        }
        var id = node.name;
        var pos = id.indexOf('_');
        if (pos<0) return;
        var contr = id.substring(0, pos);
        var config = dojo.fromJson(dojo.cookie('sel'));
        if (config) {
            delete config[contr];
            dojo.cookie('sel', dojo.toJson(config), {path:'/'});
        }
    },

    setSelectionFilter : function(/*String*/filter,/*DOMNode*/ node) {
        if (!node) {
            node = dojo.query('.selector')[0];
            if (!node) return;
        }
        var id = node.name;
        var pos = id.indexOf('_');
        if (pos<0) return;
        var contr = id.substring(0, pos);
        //var filter = dojo.byId('search_' + contr);

        var config = dojo.fromJson(dojo.cookie("sel"));
        if (!config) config = {};
        if (!config[contr]) config[contr] = '';
        
        // remove old
        config[contr] = config[contr].replace(/all:[^\|]*\|/g,'');
        
        if (filter!==false)
            config[contr] += '|all:' + filter + '|';

        config[contr] = config[contr].replace(/(\|\|)/g,'|');
        if (config[contr]=='' || config[contr]=='|') {
            delete config[contr];
        }

        dojo.query('.selectpane input').attr('disabled', !config[contr]);

        dojo.cookie('sel', dojo.toJson(config), {path:'/'});
    },

    storeSelection : function(/*DOMNode*/node, /*boolean*/add) {
        // summary: Adds or removes state for given node.
        var id = node.name;
        var pos = id.indexOf('_');
        if (pos<0) return;
        var contr = id.substring(0, pos);
        var num = id.substring(pos+1);
        var lookfor = '|'+num+'|';

        var config = dojo.fromJson(dojo.cookie('sel'));
        if (!config) config = {};
        if (!config[contr]) config[contr] = '';
        if (add) {
            if (config[contr].indexOf(lookfor)<0) {
                config[contr] += lookfor;
                config[contr] = config[contr].replace(/(\|\|)/g,'|');
            }
        } else {
            pos = config[contr].indexOf(lookfor);
            if (pos>0) {
                config[contr] = config[contr].substring(0, pos + 1) +
                    config[contr].substring(pos + lookfor.length);
            } else if (pos==0) {
                config[contr] = config[contr].substring(lookfor.length - 1);
            }
            if (config[contr]=='' || config[contr]=='|') {
                delete config[contr];
            }
        }

        dojo.query('.selectpane input').attr('disabled', !config[contr]);

        dojo.cookie('sel', dojo.toJson(config), {path:'/'});
    },

    populateSelected : function() {
        var config = dojo.fromJson(dojo.cookie('sel'));
        if (!config) {
            dojo.query('.selectpane input').attr('disabled', true);
            return;
        }
        var found = false;
        dojo.query('.selector').forEach( function(n) {
            var id = n.name;
            var pos = id.indexOf('_');
            if (pos<0) return;
            var contr = id.substring(0, pos);
            var num = id.substring(pos+1);
            if (config[contr]) found = true;
            if (config[contr] && config[contr].indexOf('|'+num+'|')>=0) {
                n.checked = true;
                dojo.addClass(n.parentNode.parentNode, 'highlight');
            }
        });
        dojo.query('.selectpane input').attr('disabled', !found);
    },

    showCategoriesDialog : function(/*Function*/fn) {
        // summary: Shows a category selection dialog - will call the callback function
        //      only if there was a selection.
    	var msgs = dsms.nls.ui.get();
        var url = location.href.substring(0, location.href.indexOf('admin')+5) + "/categories/index?q=&json";
        var html = [];
        if (!window['categoriesStore'])
            html.push('<div dojoType="dojo.data.ItemFileReadStore" jsId="categoriesStore" url="' + url + '"></div>');
        html.push('<select dojoType="dijit.form.FilteringSelect" store="categoriesStore" '); 
        html.push('searchAttr="title" autocomplete="true" pageSize="15" class="bigSelect">');
        html.push('</select>');
        html.push('<br />');
        html.push('<button dojoType="dijit.form.Button">');
        html.push(msgs.chooseCategory);
        html.push('</button>');
        var dlg = new dijit.Dialog({title:msgs.selectCategory, content:html.join('')});
        dlg.show();

        var newWidgets = dijit.findWidgets(dlg.domNode);
        var selectWidget = newWidgets[0];
        var btnWidget = newWidgets[1]; 
        
        dojo.connect( btnWidget, 'onClick', function() {
            var categoryItem = selectWidget.item;
            
            dlg.hide();
            dlg.destroyRecursive();

            if (!categoryItem) return;

            fn(categoryItem);
        } );
    },

    onLoad : function() {        
        dojo.parser.parse();

        dojo.query("#menu a").onclick(dsms.base.loadInContent);
        dsms.base.initBehavior(dijit.byId("main_content").domNode);
        dojo.query('#header p.info').forEach(dsms.base.fadeInFive);
        dojo.query(".switch-bodyclass").onclick(function(e){
            dojo.stopEvent(e);
            var theme = dojo.trim(e.target.innerHTML);
            document.body.className = theme;
        });

        dojo.query("#overlayLoading").style({display:'none'});

        dojo.fadeOut({
              node:"overlay",
              onEnd: function(){
                     dojo.style("overlay","display","none");
              },
              duration: 100
        }).play();

        var parts = location.href.split('#');
        var u = parts[0];        

        dojo.back.setInitialState({changeUrl:u, handle:function(){
            dsms.base.loadInContent(u, true);
        }});
        if (parts.length>1) {
        	var url = dsms.base._hashToUrl(parts[1]);
        	dsms.base.loadInContent(url);
        }
    }
};