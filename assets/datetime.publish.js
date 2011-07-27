(function($) {

	Symphony.Language.add({
		'today': false,
		'yesterday': false,
		'tomorrow': false
	});
	
	Symphony.DateTime = {
	
		// Reduce timestamp to days
		reduce: function(timestamp) {
			return Math.floor((this.clearTime(timestamp) + 7200000) / 86400000);
		},

		// Given a timestamp, set the hours and minutes of the resulting
		// date to 0, for use with selection detection
		clearTime: function(timestamp) {
			if(timestamp == '') return timestamp;

			var date = new Date(parseInt(timestamp));
			date.setHours(0);
			date.setMinutes(0);

			return date.getTime();
		}
	};

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
					calendar = item.find('div.calendar'),
					dates = input.parent().addClass('focus'),
					date = input.attr('data-timestamp'),
					start = dates.find('input.start').attr('data-timestamp'),
					end = dates.find('input.end').attr('data-timestamp');
					
				// Show help
				help.fadeIn('fast');

				// Set focus
				dates.addClass('focus').siblings('.focus').removeClass('focus');
		
				// Visualise
				if(!dates.is('.invalid')) {
					item.trigger('visualise', [{
						start: start,
						end: end
					}, date]);
					calendar.slideDown('fast');		
				}
			});
			
			// Setting
			selection.delegate('li', 'setdate.datetime', function(event, range, focus, mode) {
				var item = $(this),
					start = item.find('input.start'),
					end = item.find('input.end'),
					from = mergeTimes(start.attr('data-timestamp'), range.start, mode),
					to;
					
				// Move multiple day range to single day
				if(mode === 'single') {
					to = mergeTimes(start.attr('data-timestamp'), range.end, mode);
				}
				else {
					to = mergeTimes(end.attr('data-timestamp'), range.end, mode);
				}
					
				// Date range
				if(range.start && range.end) {
					validate(start, from, false);
					validate(end, to, false);
					end.slideDown('fast');
					item.addClass('range');
				}

				// Single date
				else {
					validate(start, from, false);
					empty(end);
					item.removeClass('range');
				}
				
				// Visualise
				item.trigger('visualise', [{
					start: from,
					end: to
				}, focus]);
			});
			selection.delegate('li', 'settime.datetime', function(event, first, last, mode, focus) {
				var item = $(this),
					start = item.find('.start'),
					end = item.find('.end'),
					range = {
						start: null,
						end: null
					},
					from, to;
					
				// Start time
				from = new Date(parseInt(start.attr('data-timestamp')));
				from.setHours(first.hours);
				from.setMinutes(first.minutes);
				range.start = from.getTime();
				
				// End time, date range over multiple days
				if(mode == 'multiple' && last != null) {
					to = new Date(parseInt(end.attr('data-timestamp')));
					to.setHours(last.hours);
					to.setMinutes(last.minutes);
					range.end = to.getTime();
				}
				
				// End time, date range on single day
				else if(mode == 'single' && last != null) {
					to = from;
					to.setHours(last.hours);
					to.setMinutes(last.minutes);
					range.end = to.getTime();
				}
				
				// Set focus
				if(focus == 'start') {
					focus = range.start;
				}
				else {
					focus = range.end;
				}
							
				// Visualise
				item.trigger('setdate', [range, focus, mode]);
			});
			
			// Keypress
			if(!stage.is('.simple')) {
				selection.delegate('input', 'keydown.datetime', function(event) {
					var input = $(this);

					// If tab is pressed while the user is in the first
					// date, allow the focus to shifted to the end date
					// instead of the calendar.
					if(event.which == 9 && input.is('.start')) {
						input.nextAll('input.end').show().focus();
						event.preventDefault();
					}
				});
			}
			
			// Validating
			selection.delegate('input', 'blur.datetime', function(event) {
				var input = $(this),
					date = input.val(),
					validated = input.data('validated');
				
				// Empty date
				if(date == '') {
					empty(input);
				}
				
				// Validate
				else if(date != validated) {
					validate(input, date, true);			
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
					dates = input.parent(),
					calendar = item.find('div.calendar');
				
				// Call validator
				if(input.attr('data-timestamp') != date) {
					$.ajax({
						type: 'GET',
						dataType: 'json',
						url: Symphony.Context.get('root') + '/symphony/extension/datetime/get/',
						data: { 
							date: date,
							time: Math.min(calendar.find('.timeline').size(), 1)
						},
						success: function(parsed) {
						
							// Valid date
							if(parsed.status == 'valid') {
								input.attr('data-timestamp', parsed.timestamp).val(parsed.date).removeClass('invalid');
							
								// Visualise
								if(visualise === true) {
									item.trigger('visualise', [{
										start: dates.find('.start').attr('data-timestamp'),
										end: dates.find('.end').attr('data-timestamp')
									}, input.attr('data-timestamp')]);
								}
							}
							
							// Invalid date
							else {
								input.attr('data-timestamp', '').addClass('invalid');
								
								// Clear
								calendar.slideUp('fast');		
							}
	
							// Store date
							input.data('validated', parsed.date);
							
							// Display status
							displayStatus(dates);
		
							// Get date context
							contextualise(input);
						}
					});
				}
			};
			
			// Merge new date with old times
			var mergeTimes = function(current, update, mode) {

				// Empty date	
				if(update == null || update == '') {
					return '';
				}
				
				// New date
				else if(current == null || current == '') {
					return update;
				}
				
				// Existing date
				else {
					var time, date
					
					// Set date, keep time
					if(mode == 'date') {
						time = new Date(parseInt(current)),
						date = new Date(parseInt(update));
					}
					
					// Set time, keep date
					else {
						time = new Date(parseInt(update)),
						date = new Date(parseInt(current));
					}
						
					// Set hours and minutes
					date.setHours(time.getHours());
					date.setMinutes(time.getMinutes());
	
					return date.getTime();
				}
			}
			
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
				end.attr('data-timestamp', '').slideUp('fast', function() {
					item.removeClass('range');
				});
			};
			
			// Display validity status
			var displayStatus = function(dates) {
			
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
				day = Symphony.DateTime.reduce(time);
				today = Symphony.DateTime.reduce(now.getTime());
				
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
