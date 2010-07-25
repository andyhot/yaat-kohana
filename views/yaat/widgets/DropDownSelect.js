dojo.provide("dsms.widgets.DropDownSelect");
dojo.require("dijit.form.Button");
dojo.require("dijit.Menu");
dojo.require("dojo.data.ItemFileReadStore");

dojo.declare("dsms.widgets.DropDownSelect", dijit.form.DropDownButton, {
	// From http://blog.toonetown.com/2007/11/dojo-widget-part-1-styleable-dropdown.html
	// Usage:
	// <select dojoType="dsms.widgets.DropDownSelect">
	// <option value="opt1">First Option</option>
	// <option value="opt2">Second Option</option>
	// </select>

	store: null,
	dropDown: null,
	_isPopulated: false,
	_lastOption: "",
	onChange: function(){},
	postMixInProperties: function(){
		this.inherited(arguments);
		var span = dojo.doc.createElement("span"), selItem = null;
		if (!this.store)
		{
			var items = dojo.query("> option", this.srcNodeRef).map(function(node){
					node.style.display="none";
					return { value: node.getAttribute("value"), name: String(node.innerHTML) };
			});
			this.store = new dojo.data.ItemFileReadStore({data: {identifier:"value", items:items}});
			if(items && items.length && !this.value)
			{
				selItem = items[this.srcNodeRef.selectedIndex != -1 ? this.srcNodeRef.selectedIndex : 0];
			}
		}
		this._initSelect(selItem, span);
		if (!this.dropDown)
		{
			this.dropDown = new dijit.Menu();
		}
		dojo.place(span, this.srcNodeRef, "first");
	},
	_initSelect: function(item, span){
		if (item)
		{
			this.value = item.value;
			span.innerHTML = item.name;
		}
		else if (this.store)
		{
			var _this = this;
			this.store.fetch({onComplete: function(i, r){ 
				if (i.length && i[0]) _this._initSelect(i[0], span);
			}});
		}
	},
	_menuItemClick: function(item){
		var val = item.value;
		if (this._lastOption != val)
		{
			this.setValue(val);
			this.setLabel(item.name);
			this._lastOption = val;
			this.onChange(val);
		}
	},
	_loadCallback: function(items, request){
		var dropDown = this.dropDown, _this = this;
		if (!dropDown) { return; }
		dojo.forEach(items, function(item){
			dropDown.addChild(new dijit.MenuItem({
					label: item.name,
					onClick: function(){ _this._menuItemClick(item);}}));
		});
		_this._isPopulated = true;
		dsms.widgets.DropDownSelect.superclass._toggleDropDown.call(this);
	},
	_toggleDropDown: function(){
		var dropDown = this.dropDown;
		if (dropDown && !dropDown.isShowingNow && !this._isPopulated)
		{
			this.store.fetch({onComplete:dojo.hitch(this, "_loadCallback")});
			return;			
		}
		this.inherited(arguments);
	}
});