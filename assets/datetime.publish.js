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
		// date to 0, for use with dates detection
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
		$('div.field-datetime').each(function datetime() {
			var field = $(this),
				help = field.find('i a.help'),
				instructions = field.find('div.help'),
				datetime = field.find('.dark.frame'),
				dates = datetime.find('ol'),
				headers = dates.find('header'),
				width = 0;
			
		/*---- Events -----------------------------------------------------------*/
		
			// Destructor
			datetime.on('constructstop.duplicator constructshow.duplicator', 'li', function(event) {
				var item = $(this),
					destructor = item.find('a.destructor');
					
				if(width < 1) {
					width = destructor.width();
				}
			
				if(width > 0) {
					item.find('header div').css('margin-right', width + 10);
				}
			});
		
			// Constructing
			datetime.on('constructshow.duplicator', 'li', function(event) {
				var item = $(this),
					input = item.find('input.start'),
					all = item.prevAll(),
					prev;
				
				// Prepopulate with previous date, if possible
				if(datetime.is('.prepopulate') && all.length > 0 && !(dates.is('.destructing') && all.length == 1)) {
					prev = all.filter(':first').find('input.start');
					input.val(prev.val()).attr('data-timestamp', prev.attr('data-timestamp'));
				}
			});
			datetime.on('constructstop.duplicator', 'li', function(event) {
				var item = $(this),
					input = item.find('input.start');
				
				// Store and contextualise date
				input.data('validated', input.val());
				contextualise(input);
			});
		
			// Visualising
			datetime.on('focus click', 'input', function(event) {
				var input = $(this);

				// Set focus
				datetime.find('.focus').removeClass('focus');
				input.parent().addClass('focus');
		
				// Visualise
				visualise(input);
			});
			
			// Setting
			datetime.on('setdate.datetime', 'li', function(event, range, focus, mode) {
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
			datetime.on('settime.datetime', 'li', function(event, first, last, mode, focus) {
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
			if(!datetime.is('.simple')) {
				datetime.on('keydown.datetime', 'input', function(event) {
					var input = $(this);

					// Tab key
					if(event.which == 9 && !event.shiftKey && input.is('.start')) {
						var item = input.parents('li');
						
						event.preventDefault();

						// Show end date
						input.nextAll('input.end').show().focus();
		
						// Expand calendar
						if(item.is('.collapsed')) {
							item.trigger('expand.collapsible');
						}
					}
				});
			}
			
			// Validating
			datetime.on('blur.datetime', 'input', function(event) {
				var input = $(this),
					date = input.val(),
					validated = input.data('validated');
					
				// Remove focus
				input.parent().removeClass('focus');
				
				// Empty date
				if(date == '') {
					empty(input);
				}
				
				// Validate
				else if(date != validated) {
					validate(input, date, true);			
				}			
			});
			
			// Help
			help.on('click.datetime', function(event) {
				
				// Show help
				if(instructions.is(':hidden')) {
					instructions.slideDown('fast');
					help.text(help.attr('data-hide'));
				}
				
				// Hide help
				else {
					instructions.slideUp('fast');
					help.text(help.attr('data-show'));
				}
			});
						
		/*---- Functions --------------------------------------------------------*/
		
			// Visualise date
			var visualise = function(input) {
				var item = input.parents('li'),
					datespan = input.parent(),
					date = input.attr('data-timestamp'),
					start = datespan.find('input.start').attr('data-timestamp'),
					end = datespan.find('input.end').attr('data-timestamp');
		
				item.trigger('visualise', [{
					start: start,
					end: end
				}, date]);
			};
		
			// Validate and set date
			var validate = function(input, date, visualise) {
				var item = input.parents('li'),
					datespan = input.parent(),
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
										start: datespan.find('.start').attr('data-timestamp'),
										end: datespan.find('.end').attr('data-timestamp')
									}, input.attr('data-timestamp')]);
								}
							}
							
							// Invalid date
							else {
								input.attr('data-timestamp', '').addClass('invalid');
								visualise(input);
							}
	
							// Store date
							input.data('validated', parsed.date);
							
							// Display status
							displayStatus(datespan);
		
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
					datespan = input.parent(),
					end = datespan.find('.end');
			
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
				displayStatus(datespan);
				
				// Hide end date
				end.attr('data-timestamp', '').val('').slideUp('fast', function() {
					item.removeClass('range');
				});
			};
			
			// Display validity status
			var displayStatus = function(datespan) {
			
				// At least one date is invalid
				if(datespan.find('input.invalid').size() > 0) {
					datespan.addClass('invalid');
				}
				
				// All dates are valid
				else {
					datespan.removeClass('invalid');
				}
			};	

			// Get context
			var contextualise = function(input) {
				var datespan = input.parent(),
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
			dates.symphonyCalendar();
			dates.symphonyTimer();
	
			// Initialise dates
			dates.find('input').each(function() {
				var input = $(this);
				
				// Store date
				input.data('validated', input.val());
				
				// Contexualise
				contextualise(input);
				
				// Visualise calendar once
				if(input.is('.start')) {
					visualise(input);
				}
			}).load();
	
			// Set errors
			dates.find('input.invalid').parent('div').addClass('invalid');
		
			// Initialise datetime 
			if(!datetime.is('.single')) {
			
				// Multiple dates
				dates.symphonyDuplicator({
					orderable: false,
					collapsible: false,
					minimum: (datetime.is('.prepopulate') ? 1 : 0),
				});
				
				// Orderable dates
				datetime.symphonyOrderable({
					items: 'li',
					handles: 'header',
					ignore: ''
				});
			}
			
			// Collapsible calendar
			datetime.symphonyCollapsible({
					items: 'li',
					handles: 'header',
					ignore: 'input',
					storage: 'symphony.datetime.' + Symphony.Context.get('env').section_handle + '.' + field.attr('id') + '.'
				})
				.on('dblclick.datetime', 'input', function toggleAll(event) {
	
					// Expand/collapse all
					$(this).parent().trigger('dblclick');
				})
				.on('click.datetime', 'input', function toggleInput(event) {
					var input = $(this),
						item = input.parents('li');
					
					// Expand	
					if(item.is('.collapsed')) {
						item.trigger('expand.collapsible');
					}
				});	
		});

	});
		
})(jQuery.noConflict());
