(function($) {

	/**
	 * The Date and Time extension provides an interface to add 
	 * one or more single dates or date ranges to an entry.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/datetime
	 */
	$(document).ready(function() {
	
		// Language strings
		Symphony.Language.add({
			'today': false,
			'yesterday': false,
			'tomorrow': false
		});

		// Initialize Stage
		$('div.field-datetime').each(function() {
			var manager = $(this),
				stage = manager.find('div.stage'),
				selection = stage.find('ul.selection');
				
			// Store current dates
			selection.find('input').each(function() {
				var input = $(this);
				input.data('validated', input.val());
			});
			
			// Set errors
			selection.find('input.invalid').parents('span.dates').addClass('invalid');
							
		/*-----------------------------------------------------------------------*/

			// Formating
			selection.delegate('input', 'blur.datetime', function(event) {
				var input = $(this),
					dates = input.parent(),
					item = input.parents('li'),
					date = input.val(),
					validated = input.data('validated'),
					end;

				// Remove focus
				dates.removeClass('focus');
				
				// Validate
				if(date != '' && date != validated) {
					validate(input, date, false, function() {
						var next = input.siblings('input');
						
						// Remove calendar selection for invalid dates
						if(dates.is('.invalid') && !next.is(':focus')) {
							visualise(dates, null);
						}
						
						// Rebuild calendar
						else if(dates.is('.invalid') && next.is(':focus')) {
							visualise(dates, next.attr('data-timestamp'));
						}
					});					
				}
				
				// Empty date
				else if(date == '') {
					input.removeClass('invalid');
					if(dates.find('input.invalid').size() == 0) {
						dates.removeClass('invalid');
					}
					
					// Remove empty end dates
					if(input.is('.end')) {
						input.slideUp('fast', function() {
							item.removeClass('range');
						});
					}
					
					// Handle empty start dates
					else {
						end = dates.find('input.end');
						if(end.val() != '') {
							input.val(end.val());
							end.val('').trigger('blur.datetime');
							input.trigger('blur.datetime');
						}					
					}
				}				
			});
		
			// Editing
			selection.delegate('input', 'focus.datetime', function() {
				var input = $(this),
					dates = input.parent().addClass('focus'),
					date = input.attr('data-timestamp');
								
				// Visualise
				visualise(dates, date);
			});
			
			// Closing
			$('body').bind('click.datetime', function() {
				selection.find('div.calendar').slideUp('fast');
			});
			
			// Choosing
			selection.delegate('td', 'click.datetime', function(event) {
				var cell = $(event.target),
					item = cell.parents('li'),
					timestamp = parseInt(cell.attr('data-timestamp'));
					
				// Set date
				choose(item, timestamp, event.shiftKey);
			});
					
		/*-----------------------------------------------------------------------*/
			
			// Validate date
			var validate = function(input, date, update, callback) {
				var dates = input.parent();
			
				// Call validator
				$.ajax({
					type: 'GET',
					dataType: 'json',
					url: Symphony.Context.get('root') + '/symphony/extension/datetime/get/',
					data: { 
						date: date
					},
					success: function(parsed) {
					
						// Valid date
						if(parsed.status == 'valid') {
							input.attr('data-timestamp', parsed.timestamp).val(parsed.date).removeClass('invalid');
							dates.removeClass('invalid');
							contextualise(input);
						}
						
						// Invalid date
						else {
							input.attr('data-timestamp', '').addClass('invalid');
							dates.addClass('invalid');
							decontextualise(input);
						}

						// Store date
						input.data('validated', parsed.date);
						
						// Visualise
						if(update == true) {
							visualise(dates, parsed.timestamp);
						}
						
						// Callback
						if(callback) {
							callback();
						}
					}
				});
			};
			
			// Visualise dates
			var visualise = function(dates, date) {
				var item = dates.parents('li'),
					calendar = item.find('div.calendar'),
					then = new Date(parseInt(date)),
					now = new Date(),
					start = reduce(dates.find('input.start').attr('data-timestamp')),
					end = reduce(dates.find('input.end').attr('data-timestamp')),
					length = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
					current, today,
					day, month, year;

				// Handle invalid fields
				if(isNaN(end)) {
					end = null;
				}
				if(isNaN(start)) {
					start = end;
				}
					
				// Clear on error
				if(!date) {
					date = now.getTime();
					then = now;
					start = null;
					end = null;
				}
				
				// Current date
				current = {
					time: parseInt(date),
					year: then.getFullYear(),
					month: then.getMonth()
				};
				
				// Today
				now.setHours(12);
				now.setMinutes(0);
				today = {
					time: now.getTime(),
					year: now.getFullYear(),
					month: now.getMonth(),
					day: now.getDate()
				};

				// Leap years
				if(((current.year % 4 === 0) && (current.year % 100 !== 0)) || (current.year % 400 === 0)) {
					length[1] = 29;
				}
				else {
					length[1] = 28;
				}

				// Get weekday of first day in month
				first = new Date(current.year, current.month, 1);
				distance = first.getDay();
				
				// Get starting year and month
				if(current.month == 0) {
					month = 11;
					year = current.year - 1
				}
				else {
					month = current.month - 1;
					year = current.year;
				}
				
				// Get starting day
				day = length[month] - distance + 1;		
			
				// Set calendar days
				calendar.find('tbody td').removeClass().each(function() {
					var cell = $(this),
						date = new Date(year, month, day, 12, 0);
						time = date.getTime();
						days = reduce(time);
					
					// Set day
					cell.text(day).attr('data-timestamp', time);
						
					// Last month
					if(month == current.month - 1 || (current.month == 0 && month == 11)) {
						cell.addClass('last');
					}
					
					// Today
					if(year == today.year && month == today.month && day == today.day) {
						cell.addClass('today');
					}
					
					// Selected:
					// Or clause needed for single dates
					if((start <= days && days <= end) || start == days || end == days) {
						cell.addClass('selected');
					}
									
					// Next month
					if(month == current.month + 1 || (current.month == 11 && month == 0)) {
						cell.addClass('next');
					}
						
					// Check and set month context	
					day++;
					if(day > length[month]) {
						day = 1;
						
						if(month == 11) {
							month = 0;
							year++;
						}
						else {
							month++;
						}
					}
				});				
											
				// Show calendar
				calendar.slideDown('fast');
			};
			
			// Choose date
			var choose = function(item, selected, key) {
				var start = item.find('input.start'),
					end = item.find('input.end'),
					selected = parseInt(selected),
					from = parseInt(start.attr('data-timestamp'));
					to = parseInt(end.attr('data-timestamp')),
					now = new Date();
				
				// Range
				if(key && (!isNaN(from) || !isNaN(to))) {

					// Check date order with invalid from date
					if(isNaN(from)) {
						if(selected < to) {
							from = setDate(selected, now.getTime());
						}
						else {
							from = to
							to = setDate(selected, now.getTime());
						}
					}
					
					// Check date order with valid from date
					else {
						if(selected < from) {
							to = from;
							from = setDate(selected, now.getTime());
						}
						else {
							to = setDate(selected, now.getTime());
						}
					}
					
					// Set dates
					validate(start, from, false, function() {
						validate(end, to, true);
						end.slideDown('fast', function() {
							item.addClass('range');				
						});
					});
				}
				
				// Single date
				else {
				
					// Remove end date
					end.val('').attr('data-timestamp', null).slideUp('fast', function() {
						item.removeClass('range');
					});

					// Set new date
					timestamp = setDate(selected, from, true);
					validate(start, timestamp, true);				
				}
			};
		
			// Add context
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
					decontextualise(input);
				}
			};
			
			// Remove context
			var decontextualise = function(input) {
				input.next('em.label').fadeOut('fast');
			};
			
			// Reduce
			var reduce = function(timestamp) {
				
				// Add an hour (3600000) to identify midnight (0:00) correctly			
				return Math.floor((parseInt(timestamp) + 3600000) / (24 * 60 * 60 * 1000));
			};
			
			var setDate = function(selected, old) {
				var selected = new Date(parseInt(selected)),
					old = new Date(parseInt(old)),
					hours = old.getHours(),
					minutes = old.getMinutes();
					
				// Set date, keep time
				selected.setHours(hours);
				selected.setMinutes(minutes);
				
				// Return timestamp
				return selected.getTime();				
			};
							
		/*-----------------------------------------------------------------------*/

			selection.find('input').each(function() {
				contextualise($(this));
			});
			
		});

	});
		
})(jQuery.noConflict());
