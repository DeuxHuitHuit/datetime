(function($) {

	Symphony.Language.add({
		'today': false,
		'yesterday': false,
		'tomorrow': false
	});

	/**
	 * The Date and Time extension provides an interface to add 
	 * one or more single dates or date ranges to an entry.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/datetime
	 */
	$(document).ready(function() {

		// Date and time
		$('div.field-datetime').each(function() {
			var manager = $(this),
				help = manager.find('label i'),
				stage = manager.find('div.stage'),
				selection = stage.find('ul.selection');
			
		/*---- Events -----------------------------------------------------------*/
		
			// Constructing
			stage.bind('constructstop', function(event, item) {
				var input = item.find('input.start');
				
				// Store and contextualise date
				input.data('validated', input.val());
				contextualise(input);
			});
		
			// Visualising
			selection.delegate('input', 'focus.datetime', function() {
				var input = $(this),
					item = input.parents('li'),
					calendar = item.find('div.calendar');
					dates = input.parent().addClass('focus'),
					date = input.attr('data-timestamp');
					
				// Show help
				help.fadeIn('fast');

				// Set focus
				dates.addClass('focus').siblings('.focus').removeClass('focus');
		
				// Visualise
				calendar.slideDown('fast');		
				item.trigger('visualise');
			});
			
			// Setting
			selection.delegate('li', 'set.datetime', function(event, range, focus) {
				var item = $(this),
					start = item.find('.start'),
					end = item.find('.end');
				
				// Start date
				if(range.start != null && range.start != start.attr('data-timestamp')) {
					validate(start, range.start, false);
				}
				
				// End date
				if(range.end != null && range.end != end.attr('data-timestamp')) {
					validate(end, range.end, false);
				}
			});
			
			// Validating
			selection.delegate('input', 'blur.datetime', function(event) {
				var input = $(this),
					date = input.val(),
					validated = input.data('validated');
				
				// Validate
				if(date != '' && date != validated) {
					validate(input, date, true);					
				}
				
				// Empty date
				else if(date == '') {
					empty(input);
				}				
			});
						
			// Closing
			$('body').bind('click.datetime', function() {
				
				// Hide help
				help.fadeOut('fast');
				
				// Hide calendar
				selection.find('div.calendar').slideUp('fast');
				selection.find('.focus').removeClass('focus');
			});
						
		/*---- Functions --------------------------------------------------------*/
		
			// Validate and set date
			var validate = function(input, date, visualise) {
				var item = input.parents('li'),
					dates = input.parent();
			
				// Call validator
				$.ajax({
					type: 'GET',
					dataType: 'json',
					url: Symphony.Context.get('root') + '/symphony/extension/datetime/get/',
					data: { 
						date: date
					},
					success: function(parsed) {
						console.log(parsed);
					
						// Valid date
						if(parsed.status == 'valid') {
							input.attr('data-timestamp', parsed.timestamp).val(parsed.date).removeClass('invalid');
						}
						
						// Invalid date
						else {
							input.attr('data-timestamp', '').addClass('invalid');
						}

						// Store date
						input.data('validated', parsed.date);
						
						// Display status
						displayStatus(dates);
	
						// Get date context
						contextualise(input);
						
						// Visualise
						if(visualise === true) {
							item.trigger('visualise', [{
								start: dates.find('.start').attr('data-timestamp'),
								end: dates.find('.end').attr('data-timestamp')
							}, input.attr('data-timestamp')]);
						}
					}
				});
			};
			
			// Empty date
			var empty = function(input) {
				var item = input.parents('li'),
					dates = input.parent(),
					end = dates.find('.end');
			
				// Empty dates are valid
				input.removeClass('invalid');

				// Merge with end date
				if(input.is('.start') && end.val() != '') {
					input.val(end.val());
					end.val('');
					
					// Keep errors
					if(end.is('.invalid')) {
						end.removeClass('invalid');
						input.addClass('invalid');					
					}
				}
				
				// Display status
				displayStatus(dates);
				
				// Hide end date
				end.slideUp('fast', function() {
					item.removeClass('range');
				});
			};
			
			// Display validity status
			var displayStatus = function(dates) {
			
				console.log(dates, dates.find('input.invalid').size());
			
				// At least one date is invalid
				if(dates.find('input.invalid').size() > 0) {
					dates.addClass('invalid');
				}
				
				// All dates are valid
				else {
					dates.removeClass('invalid');
				}
			};	

			// Get context
			var contextualise = function(input) {
				var dates = input.parent(),
					time = parseInt(input.attr('data-timestamp')),
					now = new Date(),
					day, today, yesterday, tomorrow, label;
				
				// Reduze timestamps to days:
				day = reduce(time);
				today = reduce(now.getTime());
				
				// Create label
				if(day == today) {
					label = Symphony.Language.get('today');
				}
				else if(today - day == 1) {
					label = Symphony.Language.get('yesterday');
				}
				else if(day - today == 1) {
					label = Symphony.Language.get('tomorrow');
				}
				
				// Attach label
				if(label) {
					input.next('em.label').text(label).fadeIn('fast');
				}
				
				// Detach label
				else {
					input.next('em.label').fadeOut('fast');
				}
			};
			
			// Reduce timestamp to days
			var reduce = function(timestamp) {
				
				// Add an hour = 60 * 60 * 1000 = 3600000 to identify midnight (0:00) correctly,
				// divide by one day = 24 * 60 * 60 * 1000 = 86400000
				return Math.floor((parseInt(timestamp) + 3600000) / 86400000);
			};
			
		/*---- Initialisation ---------------------------------------------------*/
		
			// Create calendar and timer
			selection.symphonyCalendar();
			selection.symphonyTimer();

			// Store and contextualise dates
			selection.find('input').each(function() {
				var input = $(this);
				input.data('validated', input.val());
				contextualise(input);
			}).load();

			// Set errors
			selection.find('input.invalid').parents('span.dates').addClass('invalid');

			// Hide help
			help.hide();
			
		});

	});
		
})(jQuery.noConflict());
