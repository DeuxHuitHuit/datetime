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
		
		// Select
		manager.delegate('div.timeline', 'click.timer', function(event) {
			var timeline = $(this);
			choose(timeline, event.pageX, event.shiftKey);
		});

		// Visualise
		manager.delegate(settings.item, 'visualise.timer', function(event, range, focus) {
			var item = $(this),
				timer = item.find(settings.timer);
				
			visualise(timer, range);
		});
			
		// Resizing
		$(window).bind('resize.timer', function() {
			$(settings.timer).find('div.timeline').each(function() {
				var timeline = $(this)
				setTimerPosition(timeline);
			});
		});

	/*---- Functions --------------------------------------------------------*/
	
		// Select time
		var choose = function(timeline, position, key) {
			var end = timeline.parent().find('div.timeline.end'),
				current = timeline.parent().data('range'),
				left = timeline.offset().left,
				width = timeline.width();
				selection = position - left,
				time = selection / width * 24,
				hours = Math.floor(time),
				selected = {
					hours: hours,
					minutes: Math.floor(12 * (time - hours)) * 5
				};
				
			// Adjust time later than 23:59
			if(hours > 23) {
				selected = {
					hours: 23,
					minutes: 55
				};
			}
		
			// Date range
			if(key) {
				
				// First timeline
				if(timeline.is('.start')) {
				
					// Range over multiple days
					if(end.is(':visible')) {
						timeline.parents(settings.item).trigger('settime', [selected, current.to, 'multiple', 'start']);
					}
					
					// Range on single day, new start
					else if((current.from.hours * 100 + current.from.minutes) > (selected.hours * 100 + selected.minutes)) {
	
						// Handle ranges that are created from a single day
						if(current.to == null) {
							current.to = current.from;
						}
						timeline.parents(settings.item).trigger('settime', [selected, current.to, 'single', 'start']);
					}
					
					// Range on single day, new end
					else {
						timeline.parents(settings.item).trigger('settime', [current.from, selected, 'single', 'start']);
					}
				}
				
				// Last timeline
				else {
				
					// Range over multiple days, new end date
					timeline.parents(settings.item).trigger('settime', [current.from, selected, 'multiple', 'end']);
				}
			}
			
			// Single date
			else {
				timeline.parents(settings.item).trigger('settime', [selected, null, 'single', 'start']);
			}
		};
	
		// Visualise time
		var visualise = function(timer, range) {
			var labels = timer.find('code'),
				start = labels.filter(':eq(0)'),
				end = labels.filter(':eq(1)'),
				timeline = timer.find('div.timeline'),
				first = timeline.filter(':eq(0)'),
				last = timeline.filter(':eq(1)'),
				from, to;

			// Show timer
			timer.find('div.range').show();
			
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
				
				// Hide second timeline
				hideTimeline(timer);
				
				// Store range
				timer.data('range', {
					from: from,
					to: to
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
				
				// Hide second timeline
				hideTimeline(timer);
	
				// Store range
				timer.data('range', {
					from: from,
					to: null
				});
			}
			
			// Range over multiple days
			else {
				start.text(from.time);
				end.text(to.time);
				
				// Set timeline positions
				setPosition(first, from, { 
					hours: 24,
					minutes: 30
				});	
				setPosition(last, { 
					hours: 0,
					minutes: 0
				}, to);	
				
				// Show second timeline
				showTimeline(timer);
				
				// Store range
				timer.data('range', {
					from: from,
					to: to
				});
			}
		}
		
		// Set slider position
		var setPosition = function(timeline, from, to) {
			var range = timeline.find('.range'),
				start = (from.hours * 60 + from.minutes) * minute,
				end = (to.hours * 60 + to.minutes) * minute,
				width = end - start || 0;
			
			// Position slider
			position = (from.hours * 60 + from.minutes) * minute;
			range.css({
				'left': start + '%',
				'width': width + '%'
			});
			
			// Position timer
			setTimerPosition(timeline);
		}

		// Set timer position	
		var setTimerPosition = function(timeline) {
			var range = timeline.find('div.range'),
				time = range.find('code'),
				length = timeline.width(),
				left = range.position().left,
				width = range.width();
		
			// Set position
			if(timeline.is('.start')) {
				time.css('left', Math.min(length - left - parseInt(time.width()), 0));
			}
			else {
				time.css('right', Math.min(width - parseInt(time.width()) + 3, 0));
			}			
		};
			
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
