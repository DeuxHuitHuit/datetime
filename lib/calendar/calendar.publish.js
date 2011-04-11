(function($) {

	// Language strings
	Symphony.Language.add({
		'January': false,
		'February': false,
		'March': false,
		'April': false,
		'May': false,
		'June': false,
		'July': false,
		'August': false,
		'September': false,
		'October': false,
		'November': false,
		'December': false
	});
	
	// Store months
	var months = [
		Symphony.Language.get('January'),
		Symphony.Language.get('February'),
		Symphony.Language.get('March'),
		Symphony.Language.get('April'),
		Symphony.Language.get('May'),
		Symphony.Language.get('June'),
		Symphony.Language.get('July'),
		Symphony.Language.get('August'),
		Symphony.Language.get('September'),
		Symphony.Language.get('October'),
		Symphony.Language.get('November'),
		Symphony.Language.get('December')
	];

	/**
	 * This plugin provides a calendar interface.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/calendar
	 */
	$.fn.symphonyCalendar = function() {
		var manager = $(this);

	/*---- Events -----------------------------------------------------------*/

		// Visualise
		manager.delegate('li', 'visualise', function(event, date, range) {
			visualise(date, range);
		});

	/*---- Functions --------------------------------------------------------*/

		// Visualise date
		var visualise = function(date, range) {

		};

	};
		
})(jQuery.noConflict());
