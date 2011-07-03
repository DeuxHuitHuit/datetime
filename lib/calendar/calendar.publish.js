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
	
	/**
	 * This plugin provides a calendar interface.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/calendar
	 */
	$.fn.symphonyCalendar = function(custom_settings) {
		var manager = $(this),
			settings = {
				item: 'li',
				calendar: 'div.calendar'
			},
			months = [
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

		$.extend(settings, custom_settings);

	/*---- Events -----------------------------------------------------------*/

		// Visualise
		manager.delegate(settings.item, 'visualise.calendar', function(event, range, focus) {
			var item = $(this);
			visualise(item, focus, range);
		});

		// Choosing
		manager.delegate('td', 'click.calendar', function(event) {
			var cell = $(event.target),
				calendar = cell.parents(settings.calendar),
				timestamp = parseInt(cell.attr('data-timestamp'));
				
			// Set date
			choose(calendar, timestamp, (event.shiftKey && !manager.parent().is('.simple')));
		});
			
		// Switching
		manager.delegate('span.nav a', 'click.calendar', function() {
			var calendar = $(this).parents(settings.calendar),
				direction = $(this).attr('class');
				
			// Flip to previous or next month
			flip(calendar, direction);
		});

	/*---- Functions --------------------------------------------------------*/

		// Visualise dates
		var visualise = function(item, date, range) {
			var calendar = item.find(settings.calendar),
				then = new Date(parseInt(date)),
				now = new Date(),
				start = Symphony.DateTime.reduce(range.start),
				end = Symphony.DateTime.reduce(range.end),
				length = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
				current, today,
				day, month, year,
				first, distance;
		
			// Store range
			calendar.data('range', range);

			// Handle invalid fields
			if(end == '') {
				end = null;
			}
			if(start == '') {
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
				time: date,
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
			if(day > length[month]) {
				day = 1;
				month = current.month;
				year = current.year;
			}
			
			// Set year and month
			calendar.find('span.month').text(months[current.month]).attr('data-month', current.month);	
			calendar.find('span.year').text(current.year);	
		
			// Set calendar days
			calendar.find('tbody td').removeClass().each(function() {
				var cell = $(this),
					date = new Date(year, month, day, 12, 0),
					time = date.getTime(),
					days = Symphony.DateTime.reduce(time);
				
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
		};
				
		// Choose date
		var choose = function(calendar, selected, key) {
			var current = calendar.data('range'),
				item = calendar.parents(settings.item),
				table = calendar.find('table'),
				range;
			
			// Range
			if(key && (current.start || current.end)) {

				// Check date order with invalid from date
				if(!current.start) {
					if(selected < current.end) {
						range = {
							start: selected,
							end: current.end
						};
					}
					else {
						range = {
							start: current.end,
							end: selected
						};
					}
				}
				
				// Check date order with valid from date
				else {
					if(selected < current.start) {
						range = {
							start: selected,
							end: current.start
						}
					}
					else {
						range = {
							start: current.start,
							end: selected
						}
					}
				}
			}
			
			// Single date
			else {
				
				// New range
				range = {
					start: selected,
					end: null
				};
			}
			
			// Trigger set
			item.trigger('setdate', [range, selected, 'date']);
		};
						
		
		// Flip months
		var flip = function(calendar, direction) {
			var item = calendar.parents(settings.item),
				year = calendar.find('span.year').text(),
				month = calendar.find('span.month').attr('data-month'),
				date;
			
			// Previous month
			if(direction == 'previous') {
				month--;
				if(month == -1) {
					year--;
					month = 11;
				}
			}
			
			// Next month
			else {
				month++;
				if(month == 12) {
					year++;
					month = 0;
				}
			}

			// Visualise
			date = new Date(year, month, 1);
			visualise(item, date.getTime(), calendar.data('range'));
		}

	/*---- Initialisation ---------------------------------------------------*/
			
	};
		
})(jQuery.noConflict());
