dojo.provide("dsms.data.CombiningReadStore");

dojo.require("dojo.data.ItemFileReadStore");

dojo.declare("dsms.data.CombiningReadStore", 
	dojo.data.ItemFileReadStore, 
	{
	//	summary:
	//		Store that gives the ability to combine 2 attributes 
	//		into the label.
	
    idProp : '',
    labelProp : '',
    combine : '',
    joiner : '',
    
    constructor: function(/* Object */ keywordParameters){
		this.idProp = keywordParameters.idProp || 'id';
		this.labelProp = keywordParameters.labelProp || 'title';
		this.combine = keywordParameters.combine;
		this.joiner = keywordParameters.joiner || ' : ';
	},

    _getItemsFromLoadedData: function(/* Object */ dataObject){
        var fixed = {};
        fixed.identifier = this.idProp;
        fixed.label = this.labelProp;
        fixed.items = dataObject.items;

        if (this.combine) {
    		var combine = this.combine;
    		var labelProp = this.labelProp;
    		var joiner = this.joiner;
    		
	        dojo.forEach(fixed.items, function(item){	        	
	            var add = item[combine];
	            item[labelProp] += joiner + add;
	        });
        }

        arguments[0] = fixed;
        return dsms.data.CombiningReadStore.superclass._getItemsFromLoadedData.apply(this,
            arguments);
    }
});