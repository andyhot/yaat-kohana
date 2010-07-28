dojo.provide("dsms.widgets.FilteringSelect");
dojo.require("dijit.form.FilteringSelect");

dojo.declare("dsms.widgets.FilteringSelect", dijit.form.FilteringSelect, {
	// FilteringSelect that sets initial value and display value as specified
	// in order to not require server request. 
	// Also, when this is blurred (i.e. by tabbing out) and value is empty, 
	// this widget will NOT set its value to the closest match.
	
	// TODO: seems i'll have to create a store that has this behavior, instead of
	// extending controls.
	
	initValue: '',
	initDisplayValue:'',
	
	postCreate: function(){
		this.inherited(arguments);
		
		//this.attr('displayedValue', this.initDisplayValue); // causes server round-trip
		dijit.form.ValidationTextBox.prototype._setValueAttr.call(this, this.initValue, null, this.initDisplayValue);
	},
	
	_setValueFromItem: function(/*item*/ item, /*Boolean?*/ priorityChange){
		// for 1.3
		//console.debug(this._focused);
		//console.debug('_setValueFromItem', arguments);
		if (!this._focused && !priorityChange && dojo.trim(this.attr('displayedValue'))=='') {
			this._setValue('', '');
			return;
		}
		this.inherited(arguments);
	},
	
	_setItemAttr: function(/*item*/ item, /*Boolean?*/ priorityChange, /*String?*/ displayedValue){
		// for >=1.4
		var prev = dojo.trim(this.attr('displayedValue'));
		if ((displayedValue && dojo.trim(displayedValue)!='') || prev) { 
			this.inherited(arguments);
		}
	}	
});