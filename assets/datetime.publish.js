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
							
		/*-----------------------------------------------------------------------*/

			// Formating
			selection.delegate('input', 'blur.datetime', function() {
				var input = $(this),
					dates = input.parent(),
					date = input.val(),
					current = input.data('current');
					
				// Remove focus
				dates.removeClass('focus');
				
				// Get date
				if(date != '' && date != current) {
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
								input.val(parsed.date).removeClass('invalid');
								dates.removeClass('invalid');
								input.attr('data-timestamp', parsed.timestamp * 1000);
								contextualise(input);
							}
							
							// Invalid date
							else {
								input.attr('data-timestamp', null).addClass('invalid');
								dates.addClass('invalid');
								decontextualise(input);
							}

							// Store date
							input.data('current', parsed.date);
						}
					});
				}
				else if(date == '') {
					input.removeClass('invalid');
				}
			});
			
			// Contextualise given dates
			selection.find('input').trigger('blur');
		
			// Editing
			selection.delegate('input', 'focus.datetime', function() {
				var input = $(this),
					dates = input.parent(),
					item = input.parents('li'),
					start = parseInt(item.find('input:eq(0)').attr('data-timestamp')),
					end = parseInt(item.find('input:eq(1)').attr('data-timestamp')),
					date = null;
					
				// Set focus
				dates.addClass('focus');
				
				// Get date
				if(input.is('.end')) {
					date = end;
				}
				else {
					date = start;
				}
				
				// Check invalid dates
				if(isNaN(date)) {
					date = '';
				}

				// Display calendar
				item.trigger('visualise.datetime', [date, start, end]);
			});
			
			// Calendar
			selection.delegate('li', 'visualise.datetime', function(event, date, start, end) {
				visualise(event.target, date, start, end);
			});
			
			// Closing
			$('body').bind('click.datetime', function() {
				selection.find('div.calendar').slideUp('fast');
			});
					
		/*-----------------------------------------------------------------------*/
		
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
			
			// Visualise dates
			var visualise = function(item, date, start, end) {
				var item = $(item),
					calendar = item.find('div.calendar'),
					length = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
					now = new Date(),
					date = new Date(date),
					today_year, today_month, today,
					current_year, current_month, distance, 
					year, month, day;
					
				// Reduce times
				start = reduce(start);
				end = reduce(end); 
					
				// Today
				today_year = now.getFullYear();
				today_month = now.getMonth();
				today = now.getDate();
				
				// Current date
				current_year = date.getFullYear();
				current_month = date.getMonth();

				// Leap years
				if(((current_year % 4 === 0) && (current_year % 100 !== 0)) || (current_year % 400 === 0)) {
					length[1] = 29;
				}
				else {
					length[1] = 28;
				}
				
				// Get weekday of first day in month
				first = new Date(current_year, current_month, 1);
				distance = first.getDay();
				
				// Get starting year and month
				if(current_month == 0) {
					month = 11;
					year = current_year - 1
				}
				else {
					month = current_month - 1;
					year = current_year;
				}
				
				// Get starting day
				day = length[month] - distance + 1;

				// Set calendar days
				calendar.find('tbody td').removeClass().each(function() {
					var cell = $(this),
						current = new Date(year, month, day, 12, 0);
						time = reduce(current.getTime());
					
					// Set day
					cell.text(day);
						
					// Last month
					if(month == current_month - 1 || (current_month == 0 && month == 11)) {
						cell.addClass('last');
					}
					
					// Today
					if(year == today_year && month == today_month && day == today) {
						cell.addClass('today');
					}
					
					// Selected
					if((start <= time && time <= end) || start == time) {
						cell.addClass('selected');
					}
									
					// Next month
					if(month == current_month + 1 || (current_month == 11 && month == 0)) {
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
			
			// Reduce
			var reduce = function(time) {
				
				// Add an hour (3600000) to identify midnight (0:00) correctly			
				return Math.floor((parseInt(time) + 3600000) / (24 * 60 * 60 * 1000));
			};

		});

	});
		
})(jQuery.noConflict());
