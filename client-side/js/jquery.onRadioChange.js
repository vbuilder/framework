(function($){

	$.fn.extend({
	
		onRadioChange: function(handler) {
		
			//Iterate over the current set of matched elements
            return this.each(function() {
            	var radio = $(this),
            		form = radio.parents('FORM'),
            		allOptions = form.find('INPUT[name=' + radio.attr('name') + ']'),
            		dataFieldName = '__' + radio.attr('name') + 'Previous',
            		previous = undefined;
            	
            	allOptions.change(function () {
	            	var currOption = $(this);
	            		            
	            	/* currOption.change(function () {
		            	alert('NEXT');
	            	}); */
	            		            
	            	// if(form.data(dataFieldName) != undefined && form.data(dataFieldName).val() == radio.val() && !radio.is(':checked')) 
	            	if(previous != undefined && previous.val() == radio.val() && !radio.is(':checked')) 
	            		handler(false, /* form.data(dataFieldName)*/ previous);
	            		            	
	            	if(currOption.val() == radio.val() && radio.is(':checked'))
	            		handler(true, /* form.data(dataFieldName)*/ previous);
	            		
	            	previous = currOption;
	            	// form.data(dataFieldName, currOption);
            	});     	
            });
		
		}

	});

})(jQuery);