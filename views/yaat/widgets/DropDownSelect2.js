dojo.provide("dsms.widgets.DropDownSelect2");

dojo.require("dijit.form.Button");
dojo.require("dijit.form.FilteringSelect");
dojo.require("dijit.Menu");
dojo.require("dojo.data.ItemFileReadStore");

dojo.declare("dsms.widgets.DropDownSelect2", dijit.form.DropDownButton, {
	// From http://blog.toonetown.com/2007/11/styleable-dropdown-part-ii.html
	store: null,
	dropDown: null,
	hasDownArrow: true,
	_isPopulated: false,
	_lastValue: "",
	onChange: function(){},
	_getValueField: function(){ return "value"; },
	_getItemByValue: function(value){
		var ret = null;
		this.store.fetch({query: {value: value}, onComplete: function(i, r){
			if (i.length && i[0]) ret = i[0];
		}});
		return ret;
	},
	postMixInProperties: function(){
		//this.inherited(arguments);
		dijit.form.ComboBoxMixin.prototype.postMixInProperties.apply(this, arguments);
		this.dropDown = new dijit.Menu();
		dojo.place(dojo.doc.createElement("span"), this.srcNodeRef, "first");
	},
	postCreate: function(){
		this._menuItemClick(this.value);
		this.inherited(arguments);
	},
	_menuItemClick: function(item){
		var str = (typeof item == "string"),
			i = str ? this._getItemByValue(item) : item,
			val = i ? i.value : null;
		if (!val || val == this._lastValue)
			return;
		this.setValue(val);
		this.setLabel(i.name);
		this._lastValue = val;
		if (!str) this.onChange(val);
	},
	_toggleDropDown: function(){
		var _this = this, dropDown = _this.dropDown;
		if (dropDown && !dropDown.isShowingNow && !_this._isPopulated)
		{
			_this.store.fetch({
				onItem:function(i){
					dropDown.addChild(new dijit.MenuItem({
										label: i.name,
										onClick: function(){ 
											_this._menuItemClick(i);
										}}));
				}, 
				onComplete: function(){
					_this._isPopulated = true;
					dsms.widgets.DropDownSelect2.superclass._toggleDropDown.call(_this);
				}
			});
			return;			
		}
		this.inherited(arguments);
	}
});