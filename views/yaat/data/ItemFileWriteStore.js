dojo.provide("dsms.data.ItemFileWriteStore");

dojo.require("dojo.data.ItemFileWriteStore");
dojo.require("dsms.base");

dojo.declare("dsms.data.ItemFileWriteStore", dojo.data.ItemFileWriteStore, {

    constructor: function(keywordParameters){
        this.rootLabel = keywordParameters.rootLabel;
        this.saveUrl = keywordParameters.saveUrl;
    },
    
    idProp : 'id',
    labelProp : 'title',
    parentProp : 'category_id',
    rootLabel : 'ROOT',
    saveUrl : '',
    _changes : {},

    _getItemsFromLoadedData: function(/* Object */ dataObject){
        this._changes = {};
        var parent_identifier = this.parentProp;
        var fixed = {};
        fixed.identifier = this.idProp;
        fixed.label = this.labelProp;
        fixed.items = dataObject.items;
        var root;
        if (this.rootLabel) {
            root = {children:[]};
            root[this.idProp] = null;
            root[this.labelProp] = this.rootLabel;
        }

        dojo.forEach(fixed.items, function(item){
            var id = item[parent_identifier];
            if (id) {
                var found = dojo.filter(fixed.items, function(o) {return o.id==id;});
                if (found.length==1) {
                    var kids = found[0].children;
                    if (!kids) kids = [];
                    kids.push( {_reference:item.id} );
                    found[0].children = kids;
                } else {
                    if (root) root.children.push( {_reference:item.id} );
                }
            } else {
                if (root) root.children.push( {_reference:item.id} );
            }
        });

        if (root) fixed.items.push(root);
        arguments[0] = fixed;
        return dsms.data.ItemFileWriteStore.superclass._getItemsFromLoadedData.apply(this,
            arguments);
    },

    _saveCustom: function(saveCompleteCallback, saveFailedCallback) {
        // save the new relations
        var content = {};
        var count = 0;
        for (var i in this._changes) {
            content['id'+count] = i;
            content['pId'+count] = this._changes[i] || '';
            count++;
        }
        content['_tree'] = count;
        var self = this;

        dojo.xhrPost({
            url:this.saveUrl,
            content: content,
            load: function() {
                if (saveCompleteCallback) saveCompleteCallback();
                self._changes = {};
                dsms.base.hideLoading();
                dsms.base.addFlashNews('Changed ' + count + ' entities');
            },
            error: function(){
                if (saveFailedCallback) saveFailedCallback();
                dsms.base.hideLoading();
                dsms.base.addFlashNews('Could not save', true);
            }
        });
        dsms.base.showLoading();
    },

    onSet: function(/* item */ item, /*attribute-name-string*/ attribute,
                    /*object | array*/ oldValue, /*object | array*/ newValue){
        if (oldValue==newValue) return;
        if (attribute!=this.parentProp) return;

        this._changes[this.getIdentity(item)] = newValue;
    },

    setValues: function(newParentItem, parentAttr, childItems) {
        if (parentAttr=='children') {
            var id = this.getValue(newParentItem, this.idProp);
            dojo.forEach(childItems, function(item){
                this.setValue(item, this.parentProp, id);
            }, this);
        }
        return dsms.data.ItemFileWriteStore.superclass.setValues.apply(this,
            arguments);
    },

    isParent: function(child, parent) {
        if (child==parent) return false;
        var parentId = this.getValue(parent, this.idProp);
        while (child) {
            var id = this.getValue(child, this.parentProp);
            if (parentId==id) return true;
            child = this._getItemByIdentity(id);
        }
        return false;
    },

    itemAccept: function(target, source) {
        // do not accept if target is source's child
        var draggedItems = source.selection;
        var targetItem = dijit.getEnclosingWidget(target).item;
        for (var thisItem in draggedItems) {
            var item = dijit.getEnclosingWidget(draggedItems[thisItem]).item;
            if (this.isParent(targetItem, item))
                return false;
        }
        
        return true;
    }
});