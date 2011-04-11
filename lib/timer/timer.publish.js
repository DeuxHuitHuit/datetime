(function($) {

	/**
	 * This plugin provides a timer interface.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/timer
	 */
	$.fn.symphonyTimer = function(custom_settings) {
		var manager = $(this),
			settings = {
				item: 'li',
				timer: 'div.timer'
			},
			minute = 100 / (24 * 60);

		$.extend(settings, custom_settings);


	/*---- Events -----------------------------------------------------------*/

		// Visualise
		manager.delegate(settings.item, 'set.calendar visualise.calendar', function(event, range, focus) {
			var item = $(this),
				timer = item.find(settings.timer);
				
			visualise(timer, range);
		});

	/*---- Functions --------------------------------------------------------*/
	
		// Visualise time
		var visualise = function(timer, range) {
			var labels = timer.find('code'),
				start = labels.filter(':eq(0)'),
				end = labels.filter(':eq(1)'),
				timeline = timer.find('div.timeline'),
				first = timeline.filter(':eq(0)'),
				last = timeline.filter(':eq(1)'),
				from, to;
			
			// Start time
			if(range.start == '') {
				from = formatTime(range.end);
				range.end = '';
			}
			else {
				from = formatTime(range.start);
			}
			
			// End time
			if(range.end != '') {
				to = formatTime(range.end);
			}
			
			// Range on single day
			if(reduce(range.start) == reduce(range.end)) {
				start.text(formatRange(from, to));
				end.text('12:00');	
				
				// Set timeline positions
				setPosition(first, from, to);	
				setPosition(last, { 
					hours: 0,
					minutes: 0
				}, {
					hours: 12,
					minutes: 0
				});	
			}
			
			// Single day
			else if(range.end == '') {
				start.text(from.time);
				end.text('12:00');
							
				// Set timeline positions
				setPosition(first, from, from);	
				setPosition(last, { 
					hours: 0,
					minutes: 0
				}, {
					hours: 12,
					minutes: 0
				});	
			}
			
			// Range over multiple days
			else {
				start.text(from.time);
				end.text(to.time);
				
				// Set timeline positions
				setPosition(first, from, { 
					hours: 24,
					minutes: 0
				});	
				setPosition(last, { 
					hours: 0,
					minutes: 0
				}, to);	
				
				// Show second timeline
				showTimeline(timer);
			}
		}
		
		// Set slider position
		var setPosition = function(timeline, from, to) {
			var range = timeline.find('.range'),
				start = (from.hours * 60 + from.minutes) * minute,
				end = (to.hours * 60 + to.minutes) * minute;
			
			// Set start position
			position = (from.hours * 60 + from.minutes) * minute;
			range.css({
				'left': start + '%',
				'width': (end - start) + '%'
			});
		}

		// Show timeline		
		var showTimeline = function(timer) {
			var timeline = timer.find('div.timeline.end'),
				range = timeline.find('div.range');
				
			// Slide down timeline
			if(timeline.is(':hidden')) {
				timeline.slideDown('fast', function() {
					range.fadeIn();
				});
			}
		};
		
		// Hide timeline
		var hideTimeline = function(timer) {
			var timeline = timer.find('div.timeline.end');
				
			// Slide down timeline
			if(timeline.is(':visible')) {
				timeline.slideUp('fast');
			}
		};
					
		// Format time
		var formatTime = function(time) {
			var date = new Date(parseInt(time)),
				hours = date.getHours(),
				minutes = date.getMinutes(),
				devider = ':';
				
			// Handle one-digit times	
			if(minutes < 10) {
				devider = ':0';
			}
			
			// Return formatted time
			return {
				time: hours.toString() + devider + minutes.toString(),
				hours: hours,
				minutes: minutes					
			};
		};
		
		// Format range
		var formatRange = function(from, to) {
	
			// Only start time defined
			if(!to) {
				return from.time;
			}
			
			// From and to defined
			else if(from.time != to.time) {
				return from.time + '–' + to.time;
			}
			
			// From and to are identical
			else {
				return from.time;
			}
		};
					
		// Reduce timestamp to days
		var reduce = function(timestamp) {
			return Math.floor((parseInt(timestamp) + 7200000) / 86400000);
		};

	/*---- Initialisation ---------------------------------------------------*/
		

	};
		
})(jQuery.noConflict());
