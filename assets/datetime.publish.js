
(function($) {

	/**
	 * The Date and Time extension provides an interface to add 
	 * one or more single dates or date ranges to an entry.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 */
 	$.fn.symphonyDatetime = function(custom_settings) {
		var objects = this;
		var settings = {};
		
		$.extend(settings, custom_settings);
		
	/*-----------------------------------------------------------------------*/
	

	
	};

	// Initialize Date and Time extension	
	$('document').ready(function() {
		$('div.field-datetime').symphonyDatetime();
	});
	
})(jQuery.noConflict());
