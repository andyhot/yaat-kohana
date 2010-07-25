dojo.provide("dsms.data.ItemFileReadStore");

dojo.require("dojo.data.ItemFileReadStore");

dojo.declare("dsms.data.ItemFileReadStore", dojo.data.ItemFileReadStore, {

    idProp : 'id',
    labelProp : 'title',
    parentProp : 'category_id',

    _getItemsFromLoadedData: function(/* Object */ dataObject){
        var parent_identifier = this.parentProp;
        var fixed = {};
        fixed.identifier = this.idProp;
        fixed.label = this.labelProp;
        fixed.items = dataObject.items;

        dojo.forEach(fixed.items, function(item){
            var id = item[parent_identifier];
            if (id) {
                var found = dojo.filter(fixed.items, function(o) {return o.id==id;});
                if (found.length==1) {
                    var kids = found[0].children;
                    if (!kids) kids = [];
                    kids.push( {_reference:item.id} );
                    found[0].children = kids;
                }
            }
        });

        arguments[0] = fixed;
        return dsms.data.ItemFileReadStore.superclass._getItemsFromLoadedData.apply(this,
            arguments);
    }
});