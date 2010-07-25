dojo.provide("dsms.tree.RefreshingForestModel");

dojo.require("dijit.tree.ForestStoreModel");

dojo.declare("dsms.tree.RefreshingForestModel", dijit.tree.ForestStoreModel, {
    	pasteItem: function(/*Item*/ childItem, /*Item*/ oldParentItem, /*Item*/ newParentItem,
            /*Boolean*/ bCopy, /*int?*/ insertIndex){

                dsms.tree.RefreshingForestModel.superclass.pasteItem.apply(this,
                    arguments);
                // requery top nodes so that tree is redrawn
                this._requeryTop();
        }
});