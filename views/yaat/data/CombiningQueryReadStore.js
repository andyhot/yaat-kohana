dojo.provide("dsms.data.CombiningQueryReadStore");

dojo.require("dojox.data.QueryReadStore");

dojo.declare("dsms.data.CombiningQueryReadStore", 
	dojox.data.QueryReadStore, 
	{

    idProp : '',
    labelProp : '',
    combine : '',
    extraQuery : '',
    
    constructor: function(/* Object */ keywordParameters){
		this.idProp = keywordParameters.idProp || 'id';
		this.labelProp = keywordParameters.labelProp || 'title';
		this.combine = keywordParameters.combine;
        this.extraQuery = keywordParameters.extraQuery;
	},

	_filterResponse: function(/* Object */ dataObject){
        var fixed = {};
        fixed.identifier = this.idProp;
        fixed.label = this.labelProp;
        fixed.items = dataObject.items;

		var combine = this.combine;
		var labelProp = this.labelProp;		

        if (this.combine) {
	        dojo.forEach(fixed.items, function(item){	        	
	            var add = item[combine];
	            item[labelProp] += ' : ' + add;
	        });
        }
        return fixed;
    },
    
	fetch:function(request) {
    	// strip *
    	var lookFor = request.query.title.replace(/\*/g,'');
    	// remove code
    	var pos = lookFor.indexOf(' : ');
    	if (pos>=0) {
    		lookFor = lookFor.substring(0, pos);
    	}
        if (this.extraQuery)
            lookFor += ' ' + this.extraQuery;
		request.serverQuery = {q:lookFor, start:request.start, count:request.count};
		return this.inherited("fetch", arguments);
	}//,
//    
//    
//    _fetchItems: function(request, fetchHandler, errorHandler){
//    	// strip *
//    	var lookFor = request.query.title.replace(/\*/g,'');
//    	// remove code
//    	var pos = lookFor.indexOf(' : ');
//    	if (pos>=0) {
//    		lookFor = lookFor.substring(0, pos);
//    	}
//    	request.serverQuery = {q : lookFor};
//    	
//    	//return this.inherited("_fetchItems", arguments);
//    	return dsms.data.CombiningQueryReadStore.superclass._fetchItems.apply(this,
//                arguments);    	
//    }
});