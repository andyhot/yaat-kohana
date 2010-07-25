dojo.provide("dsms.widgets.FilteringSelect");
dojo.require("dijit.form.FilteringSelect");

dojo.declare("dsms.widgets.FilteringSelect", dijit.form.FilteringSelect, {
	// FilteringSelect that sets initial value and display value as specified
	// in order to not require server request. 
	// Also, when this is blurred (i.e. by tabbing out) and value is empty, 
	// this widget will NOT set its value to the closest match.
	
	initValue: '',
	initDisplayValue:'',
	
	postCreate: function(){
		this.inherited(arguments);
		
		this._setValue(this.initValue, this.initDisplayValue);
	},
	
	_setValueFromItem: function(/*item*/ item, /*Boolean?*/ priorityChange){
		//console.debug(this._focused);
		//console.debug(arguments);
		if (!this._focused && !priorityChange && dojo.trim(this.attr('displayedValue'))=='') {
			this._setValue('', '');
			return;
		}
		this.inherited(arguments);
	}
});