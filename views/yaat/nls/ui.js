dojo.provide("dsms.nls.ui");

dsms.nls.ui = {
		
	get : function(){
		if (this[djConfig.locale])
			return this[djConfig.locale];
		else
			return this['en_US'];
	},
		
	'el' : {
	
		selectCategory: 'Επιλέξτε Κατηγορία',
		chooseCategory: "OK"
	
	},  
	
	'en_US' : {
		
		selectCategory: 'Select Category',
		chooseCategory: 'OK'
	}		
};