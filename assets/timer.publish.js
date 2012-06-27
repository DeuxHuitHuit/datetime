(function($) {

	/**
	 * This plugin provides a timer interface.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/timer
	 */
	$.fn.symphonyTimer = function(custom_settings) {
		var dates = $(this),
			settings = {
				item: 'li',
				timer: 'div.timer'
			},
			selector = $('<div class="selector"><code class="current">0:00</code><span class="current"></span></div>'),
			minute = 100 / (24 * 60);

		$.extend(settings, custom_settings);


	/*---- Events -----------------------------------------------------------*/
		
		// Select
		dates.on('click.timer', 'div.timeline', function(event) {
			var timeline = $(this);
			choose(timeline, event.pageX, (event.shiftKey && !dates.parent().is('.simple')));
		});

		// Visualise
		dates.on('visualise.timer', settings.item, function(event, range, focus) {
			var item = $(this),
				timer = item.find(settings.timer),
				ranges = timer.find('div.range'),
				times = ranges.find('code');
				
			// Empty range
			if(range.start == '' && range.end == '') {
				timer.hide();
			}
			
			// Display time
			else {
				
				// Visualise time
				visualise(timer, range);		

				// Position timer
				if(times.filter(':eq(0)').is(':hidden')) {
					times.css('opacity', 0);
				}
				timer.slideDown('fast', function() {
					timer.find('div.range').each(function() {
						setTimerPosition($(this));
						times.animate({
							opacity: 1
						});
					});
				});
			}
		});
		
		// Hover
		dates.on('mouseover.timer', 'div.timeline', function(event) {
			var timeline = $(this),
				selected = getTime(timeline, event.pageX),
				date = new Date(),
				time;

			// Append selector
			if(timeline.has('.selector').size() == 0) {
				timeline.append(selector);
			}
			
			// Set time
			date.setHours(selected.hours);
			date.setMinutes(selected.minutes);
			time = formatTime(date.getTime());
			selector.find('code').text(time.time);
			
			// Show selector
			setPosition(selector, selected, selected);
			selector.css('visibility', 'visible');
		});
		dates.on('mouseout.timer', settings.timer, function(event) {
		
			// Hide selector
			selector.css('visibility', 'hidden');
		});
			
		// Resizing
		$(window).on('resize.timer', function() {
			$(settings.timer).find('div.timeline').each(function() {
				var range = $(this).find('div.range');
				setTimerPosition(range);
			});
		});

	/*---- Functions --------------------------------------------------------*/
	
		// Select time
		var choose = function(timeline, position, key) {
			var end = timeline.parent().find('div.timeline.end'),
				current = timeline.parent().data('range'),
				selected = getTime(timeline, position);
				
			// Adjust time later than 23:59
			if(selected.hours > 23) {
				selected = {
					hours: 23,
					minutes: 55
				};
			}
		
			// Time range
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
			
			// Single time
			else {
			
				// Single date
				if(end.is(':hidden')) {
					timeline.parents(settings.item).trigger('settime', [selected, null, 'single', 'start']);
				}
				
				// Date range
				else {
					timeline.parents(settings.item).trigger('settime', [selected, selected, 'multiple', 'start']);
				}
			}
		};
	
		// Visualise time
		var visualise = function(timer, range) {
			var labels = timer.find('code:not(.current)'),
				start = labels.filter(':eq(0)'),
				end = labels.filter(':eq(1)'),
				timeline = timer.find('div.timeline'),
				first = timeline.filter(':eq(0)').find('div.range'),
				last = timeline.filter(':eq(1)').find('div.range'),
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
			if(Symphony.DateTime.reduce(range.start) == Symphony.DateTime.reduce(range.end)) {
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
					hours: 23,
					minutes: 55
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
		};
		
		// Get time
		var getTime = function(timeline, position) {
			var left = timeline.offset().left,
				width = timeline.width(),
				selection = Math.min(Math.max(position - left, 0), width - 1),
				time = selection / width * 24,
				hours = Math.floor(time);
					
			return {
				hours: hours,
				minutes: Math.floor(12 * (time - hours)) * 5
			};
		};
		
		// Set slider position
		var setPosition = function(range, from, to) {
			var start = (from.hours * 60 + from.minutes) * minute,
				end = (to.hours * 60 + to.minutes) * minute,
				width = end - start || 0;
			
			// Position slider
			range.css({
				'left': start + '%',
				'width': width + '%'
			});
			
			// Position timer
			if(range.is('.selector')) {
				setTimerPosition(range);
			}
		};

		// Set timer position	
		var setTimerPosition = function(range) {
			var timeline = range.parent(),
				time = range.find('code'),
				length = timeline.width(),
				left = range.position() ? range.position().left : 0,
				width = range.width(),
				time_width = time.width() || 28,
				max = 0;
				
			// get position
			if(timeline.is('.start') || range.is('.selector')) {

				// Correct left positioning
				if(left <= 4) {
					max = 3;
				}
			
				time.css({
					'left': Math.min(length - left - time_width - 2, max),
					'right': 'auto'
				});
			}
			else {
				time.css({
					'right': Math.min(width - time_width - 4, max),
					'left': 'auto'
				});
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
				timer.find('div.timeline.start span.end').hide();
			}
		};
		
		// Hide timeline
		var hideTimeline = function(timer) {
			var timeline = timer.find('div.timeline.end');
				
			// Slide down timeline
			if(timeline.is(':visible')) {
				timeline.slideUp('fast');
				timer.find('div.timeline.start span.end').show();
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
				return from.time + ' – ' + to.time;
			}
			
			// From and to are identical
			else {
				return from.time;
			}
		};

	};
		
})(jQuery.noConflict());
