/*
 * CALENDAR
 * for Symphony
 *
 * @author: Nils Hörrmann, post@nilshoerrmann.de
 * @source: http://github.com/nilshoerrmann/calendar
 */


/*-----------------------------------------------------------------------------
	Language strings
-----------------------------------------------------------------------------*/	 

	Symphony.Language.add({
	
		// Months
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
		'December': false,
		
		// Weekdays
		'Sun': false,
		'Mon': false,
		'Tue': false,
		'Wed': false,
		'Thu': false,
		'Fri': false,
		'Sat': false,
		
		// Relative dates
		'yesterday': false,
		'today': false,
		'now': false,
		'tomorrow': false
		
	}); 
	

/*-----------------------------------------------------------------------------
	Calendar plugin
-----------------------------------------------------------------------------*/

	jQuery.fn.symphonyCalendar = function(custom_settings) {

		// Get objects
		var objects = this;
		
		// Get settings
		var settings = {
			start:				'input:nth-child(1)',
			end:				'input:nth-child(2)',
			calendar:			false,
			delay_initialize:	false
		};
		jQuery.extend(settings, custom_settings);


	/*-------------------------------------------------------------------------
		Calendar
	-------------------------------------------------------------------------*/

		objects = objects.map(function() {
		
			// Get elements
			var object = this;

		/*-------------------------------------------------------------------*/
			
			if (object instanceof jQuery === false) {
				object = jQuery(object);
			}
			
			object.calendar = {
			
				initialize: function() {
				
					// Get calendar
					if(settings.calendar == false) {
						calendar = jQuery('<div class="calendar" />').appendTo(object);
					}
					else {
						calendar = object.find(settings.calendar);
					}
					
					// Start date
					start = new Date(Date.parse(object.find(settings.start).val()));
					start = object.calendar.resetTime(start);
					
					// End date
					if(settings.end) {
						end = new Date(Date.parse(object.find(settings.end).val()));
					}
					else {
						end = new Date(Date.parse(object.find(settings.start).val()));
					}
					end = object.calendar.resetTime(end);
									
					// Add calendar
					object.calendar.generateMonth(start);
					
				},
				
			/*---------------------------------------------------------------*/
				
				generateMonth: function(start) {
				
					// Store current month				
					month = start.getMonth();
					
					// Store today
					today = new Date();
					today = object.calendar.resetTime(today);
					today = today.getTime();
							
					// Create table
					table = jQuery('<table><tbody></tbody></table>').appendTo(calendar);
					head = jQuery(
						'<thead><tr>' +
							'<td>' + Symphony.Language.get('Sun') + '</td>' +
							'<td>' + Symphony.Language.get('Mon') + '</td>' +
							'<td>' + Symphony.Language.get('Tue') + '</td>' +
							'<td>' + Symphony.Language.get('Wed') + '</td>' +
							'<td>' + Symphony.Language.get('Thu') + '</td>' +
							'<td>' + Symphony.Language.get('Fri') + '</td>' +
							'<td>' + Symphony.Language.get('Sat') + '</td>' +
						'</tr></thead>'
					).prependTo(table);
					
					// Create independent date reference and jump to first day in month
					var date = new Date();
					date.setTime(start.valueOf());
					date.setDate(1);
					
					// Generate weeks
					for(w = 0; w < 6; w++) {
						date = object.calendar.generateWeek(date);
					}
				
				},
				
				generateWeek: function(date) {

					// First day of the week				
					var current = object.calendar.getFirstDayInWeek(date);
				
					// Create current week
					var row = jQuery('<tr />').appendTo(table);
					for(d = 0; d < 7; d++) {
						row.append(object.calendar.generateDay(current));
						current = object.calendar.getNextDayInWeek(current);
					}
					
					// Return current loop date
					return current;
			
				},
				
				generateDay: function(current) {
				
					// Create day element				
					var day = jQuery('<td>' + current.getDate() + '</td>');
					
					// Odd column?
					if(current.getDay() % 2 != 0) {
						day.addClass('odd');
					}
					
					// Last, current or next month?
					currentMonth = current.getMonth()
					if(currentMonth < month) {
						day.addClass('last');
					}
					else if(currentMonth > month) {
						day.addClass('next');
					}
					
					// Today?
					if(current.getTime() == today) {
						day.addClass('today');
					}
					
					// Selected?
					console.log(start, current, end);
					if(start <= current && current <= end) {
						day.addClass('selected');
					}
					
					// Return day element
					return day;
					
				},
				
			/*---------------------------------------------------------------*/
				
				getFirstDayInWeek: function(date) {
				    return new Date(date.getTime() - (date.getDay() * 24 * 60 * 60 * 1000));
				},
				
				getNextDayInWeek: function(date) {
					return new Date(date.getTime() + (24 * 60 * 60 * 1000));
				},
				
				resetTime: function(date) {
					date.setHours(0);
					date.setMinutes(0);
					date.setSeconds(0);
					date.setMilliseconds(0);
					return date;
				}
							
			}
			
			if (settings.delay_initialize !== true) {
				object.calendar.initialize();
			}
			
			return object;
		});
		
		return objects;

	}
	

/*-----------------------------------------------------------------------------
	Apply calendar plugin
-----------------------------------------------------------------------------*/

	jQuery(document).ready(function() {
		jQuery('div.field-date').symphonyCalendar();
	});
