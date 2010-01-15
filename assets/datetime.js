/**
 * DATE AND TIME Extension
 * for Symphony CMS
 *
 * @author: Nils Hörrmann, post@nilshoerrmann.de
 * @date: August 2009
 */

(function($) {

	$.fn.datetime = function() {

		return this.each(function() {
			var _label = $(this);
			var _field = _label.parent('.field-datetime');
			var _input = _label.find('input');

			// language
			if(Calendar.settings == undefined) Calendar.settings = eval('(' + _input.filter(':hidden').val() + ')');
			// init
			if(Calendar.settings.multiple == 'no') _field.find('a.new').remove();
			Calendar.create(_label);
			Calendar.toggleInput(_label, 0);
			_input.filter(':not(:hidden)').each(function() {
				if(this.value != '') {
					var date = Date.parseExact(this.value, 'yyyy-MM-dd HH:mm:ss')
					if(date == null) date = Date.parseExact(this.value.substring(0, 10) + ' ' + this.value.substring(11, 19), 'yyyy-MM-dd HH:mm:ss');
					var current = Calendar.setRelativeDate(date);
					$(this).val(current);
				}
			});
			if(Calendar.settings.prepopulate == 'yes' && !_input.filter(':first').val()) _input.filter(':first').val(Calendar.setRelativeDate(Date.parse('now')));

			var _calendar = _label.next('.calendar');

			// events
			_input.click(function(event) {
				event.preventDefault();
				var date = Calendar.getDate($(this).val());
				if(date !== null || Calendar.settings.prepopulate == "no") {
					if(Calendar.settings.prepopulate == "no") date = Date.parse('now');
					Calendar.updateSelect(_calendar, date);
					Calendar.open(_calendar, date);
				} else {
					$(this).addClass('error');
				}
			}).keyup(function(event) {
				var date = Calendar.getDate($(this).val());
				if(date !== null) {
					Calendar.updateSelect(_calendar, date);
					Calendar.update(_calendar, date);
				} else {
					$(this).addClass('error');
				}
			});
			_calendar.find('select').change(function() {
				var self = $(this);

				if(self.hasClass("year")) {
					var year = self.val();
					var month = self.prev("select").val();
				} else if(self.hasClass("month")) {
					var year = self.next("select").val();
					var month = self.val();
				}

				var date = Date.parseExact(year + '-' + month, 'yyyy-M');

				Calendar.update(_calendar, date);
			});
			_calendar.find('tbody td').click(function(event) {
				var select = _calendar.find('select');
				var day = $(this);
					month = select[0].value;
					year = select[1].value;

				var date = Date.parseExact(year + '-' + month + '-' + day.text(), 'yyyy-M-d');

				if(day.hasClass('last')) date.last().month();
				if(day.hasClass('next')) date.next().month();

				if(event.altKey) {
					Calendar.setEnd(_calendar, date);
				} else {
					Calendar.setStart(_calendar, date);
				}
			});
			_label.find('a.delete').click(function(event) {
				event.preventDefault();
				event.stopPropagation();
				Calendar.removePanel(_label);
			});
			_field.click(function(event) {
				$('body').one('click', function() {
					_label.removeClass('active');
					_calendar.slideUp(100);
				});
				event.stopPropagation();
			});
		});

	};

	/*
	 * Calendar object
	 */

	var Calendar = {

		toggleInput: function(label, time) {
			if(!time && time !== 0) time = 100;
			var input = label.find('input:not([type=hidden])');
			if(input.filter(':last').val() == '') {
				label.find('span.end').slideUp(time);
				label.find('span.start').removeClass('range').find('em').text(this.settings.DATE);
			} else {
				label.find('span.end').slideDown(250);
				label.find('span.start').addClass('range').find('em').text(this.settings.FROM);
			}
		},

		open: function(calendar, date) {
			calendar.slideDown(250).siblings('.calendar').slideUp(250);
			var label = calendar.prev('label'),
                labels = label.siblings('label');
			if(labels.size() > 0) {
				label.addClass('active');
			}
			labels.removeClass('active');
			this.update(calendar, date);
		},

		create: function(label) {
			// calendar
			var calendar = $('<div class="calendar"><div class="nav"><select class="month" /><select class="year" /></div><table><thead><tr /></thead><tbody /></table><div class="capsule"><div class="start time"><strong>' + this.settings.START + '</strong><em>--:--</em></div><div class="end time"><strong>' + this.settings.END + '</strong><em>--:--</em></div></div></div>').insertAfter(label).slideUp(0);

			// Performance..
			var c_days = c_weeks = c_months = "";

			$.each(Date.CultureInfo.abbreviatedDayNames, function() {
				c_days += '<td>' + this + '</td>';
			});
			calendar.find('thead tr').append(c_days);

			var week = 1;
			while(week++ <= 6) {
				c_weeks += '<tr><td class="even"><td class="odd"><td class="even"><td class="odd"><td class="even"><td class="odd"><td class="even"></td></tr>';
			}
			calendar.find('tbody').append(c_weeks);

			// select options
			$.each(Date.CultureInfo.monthNames, function(count) {
				count++;
				c_months += '<option value="' + count + '">' + this + '</option>';
			});
			calendar.find('select.month').append(c_months);

			// time
			calendar.find('div.time').slider({
				max: 96,
				slide: function(event, ui) {
					if(ui.value != 0) {
						var time = (ui.value - 1) / 4;
                            hour = parseInt(time);
                            minute = 60 * (time - hour);

						time = new Date();
						time.setHours(hour, minute, 0);
						time = time.toString('HH:mm');
					} else {
						time = '--:--';
					}
					$(event.target).find('em').text(time);
				},
				stop: function(event, ui) {
					var slider = $(event.target),
                        label = $(event.target).parents('.calendar').prev('label');

					if(ui.value != 0) {
						var time = (ui.value - 1) / 4;
                            hour = parseInt(time);
                            minute = 60 * (time - hour);

						if(slider.hasClass('start')) {
							var start = label.find('span.start input');
							Calendar.setTime(start, hour, minute);
						} else {
							var end = label.find('span.end input');
							if(end.val() == '') {
								var start = label.find('span.start input');
								end.val(start.val());
								Calendar.toggleInput(end.parents('label'));
							}
							Calendar.setTime(end, hour, minute);
						}
						// compare times
						Calendar.compareTimes($(event.target).parents('.calendar'));
					} else {
						if(slider.hasClass('end')) {
							var calendar = $(event.target).parents('.calendar'),
                                end = calendar.prev('label').find('span.end input').val('');

							calendar.find('tbody td').removeClass('end').removeClass('range');
							Calendar.toggleInput(end.parents('label'));
						}
					}
				}
			});
		},

		update: function(calendar, date) {
			var startInput = calendar.prev('label').find('.start input').removeClass(),
                endInput = calendar.prev('label').find('.end input').removeClass();

			// dates
			var today = this.getDate('today'),
                start = this.getDate(startInput.val()),
                end = this.getDate(endInput.val());

			// fallback dates
			if(start == null) {
				start = today;
			} else {
				this.setTimeSlider(calendar.find('div.start'), start.getHours(), start.getMinutes());
			}

			if(end == null) {
				end = start;
				if(endInput.val() != '') endInput.addClass('error');
			} else {
				this.setTimeSlider(calendar.find('div.end'), end.getHours(), end.getMinutes());
			}
			if(date == null) date = start;

			// populate calendar
			var current = date.clone().clearTime().set({ day: 1 });
			if (!current.is().sunday()) current.last().sunday();
			calendar.find('tbody td').removeClass('start').removeClass('end').removeClass('today').removeClass('range').removeClass('last').removeClass('next').each(function() {
				var day = $(this);
				day.text(current.getDate());
				// month context
				if(current.toString('M') == (parseInt(date.toString('M')) - 1) || (current.toString('M') == 12 && date.toString('M') == 1)) day.addClass('last');
				if(current.toString('M') == (parseInt(date.toString('M')) + 1) || (current.toString('M') == 1 && date.toString('M') == 12)) day.addClass('next');
				// day context
				if(current.equals(today.clearTime())) day.addClass('today');
				if(current.equals(start.clearTime()) && startInput.val() != '') day.addClass('start');
				if(current.between(start.clearTime(), end.clearTime()) && end != start) day.addClass('range');

				if(end != start) {
					if(current.equals(end.clearTime())) day.addClass('end');
				}
				// move to next day
				current.next().day();
			})
		},

		updateSelect: function(calendar, date) {
			// handle empty dates
			if(!date) date = Date.today();
			// set month
			calendar.find('select.month option[value=' + date.toString('M') + ']').attr('selected', true);
			// set year
			var year = date.toString('yyyy');
			var select = calendar.find('select.year');
			select.empty().append('<option value="' + year + '" selected="selected">' + year + '</option>');
			var plus = minus = year;
			for(x = 1; x <= 5; x++) {
				plus++;
				select.prepend('<option value="' + plus + '">' + plus + '</option>');
				minus--;
				select.append('<option value="' + minus + '">' + minus + '</option>');
			}
		},

		setStart: function(calendar, date) {
			var label = calendar.prev('label'),
                start = label.find('span.start input'),
                end = label.find('span.end input');
			var current = this.getDate(start.val());
			// set date
			if(current == null) current = this.getDate('now');
			date.setHours(current.toString('HH'), current.toString('mm'));
			start.val(this.setRelativeDate(date));
			end.val('');
			// show end date
			this.toggleInput(label);
			// set end slider
			calendar.find('div.end').slider('option', 'value', 0);
			calendar.find('div.end em').text('--:--');
			// update calendar
			this.updateSelect(calendar, date);
			this.update(calendar, date);
		},

		setEnd: function(calendar, date) {
			var label = calendar.prev('label'),
                start = label.find('span.start input'),
                end = label.find('span.end input');
			var current = this.getDate(end.val());
			// set date
			if(current == null) current = this.getDate('now');
			date.setHours(current.toString('HH'), current.toString('mm'));
			end.val(this.setRelativeDate(date));
			// check start and end relation
			var startDate = this.getDate(start.val()),
				endDate = this.getDate(end.val());
			if(endDate.compareTo(startDate) == -1) {
				start.val(this.setRelativeDate(endDate));
				end.val(this.setRelativeDate(startDate));
			}
			// show end date
			this.toggleInput(label);
			// update calendar
			this.updateSelect(calendar, date);
			this.update(calendar, date);
		},

		setRelativeDate: function(date) {
			var yesterday = Date.parse('yesterday'),
				today = Date.parse('today'),
				tomorrow = Date.parse('tomorrow');

			var current = date.clone().clearTime(),
				formats = this.settings.FORMAT.split(",");

			var textFull = date.toString(this.settings.FORMAT),
				textShort = Date.parse(date).toString(formats[0]),
				time = Date.parse(date).toString(formats[1]);

			if(current.equals(yesterday)) {
				return textFull.replace(textShort, Date.RelativeDates.yesterday);
			}
			if(current.equals(today)) {
				return textFull.replace(textShort, Date.RelativeDates.today);
			}
			if(current.equals(tomorrow)) {
				return textFull.replace(textShort, Date.RelativeDates.tomorrow);
			}
			return date.toString(this.settings.FORMAT);
		},

		getDate: function(date) {
			if( date.search(Date.RelativeDates.yesterday) != -1 ||
				date.search(Date.RelativeDates.today) != -1 || date.search('today') != -1 ||
				date.search(Date.RelativeDates.now) != -1 || date.search('now') != -1 ||
				date.search(Date.RelativeDates.tomorrow) != -1)
			{
				// Dirty fix for a dateJS bug which misinterprets relative dates
				date = date.replace(Date.RelativeDates.yesterday, Date.parse('yesterday').toString('yyyy-MM-dd'));
				date = date.replace(Date.RelativeDates.today, Date.parse('today').toString('yyyy-MM-dd'));
				date = date.replace(Date.RelativeDates.now, Date.parse('now').toString('yyyy-MM-dd, HH:mm'));
				date = date.replace(Date.RelativeDates.tomorrow, Date.parse('tomorrow').toString('yyyy-MM-dd'));
				return Date.parse(date);
			}
			else {
				return Date.parseExact(date, this.settings.FORMAT);
			}
		},

		setTime: function(input, hour, minute) {
			var date = this.getDate(input.val());
			date.setHours(hour, minute, 0);
			input.val(this.setRelativeDate(date));
		},

		setTimeSlider: function(timer, hour, minute) {
			// set timer
			var time = new Date();
			time.setHours(hour, minute, 0);
			time = time.toString('HH:mm');
			timer.find('em').text(time);
			// set slider
			minute = parseInt(minute / 15);
			hour = hour * 4;
			timer.slider('option', 'value', hour + minute);
			// compare start and end time
			this.compareTimes(timer.parents('.calendar'));
		},

		compareTimes: function(calendar) {
			var startInput = calendar.prev('label').find('span.start input'),
                endInput = calendar.prev('label').find('span.end input');

			var start = this.getDate(startInput.val());
                end = this.getDate(endInput.val());

			if(end !== null && end.compareTo(start) == -1) {
                // switch dates
                startInput.val(this.setRelativeDate(end));
                this.setTimeSlider(calendar.find('div.start'), end.toString('HH'), end.toString('mm'));
                endInput.val(this.setRelativeDate(start));
                this.setTimeSlider(calendar.find('div.end'), start.toString('HH'), start.toString('mm'));
			}
		},

		addPanel: function(field) {
			var label = field.find('label:first').clone().removeClass();
			label.find('span.end').hide();
            label.find('span.start').removeClass('range').find('em').text(this.settings.DATE);

			if(Calendar.settings.prepopulate == "yes") {
				label.find('input').val('').filter(':first').val(this.getDate('now').toString('yyyy-MM-dd HH:mm:ss'));
			} else {
				label.find('input').val('');
			}

			label.find('input[type=hidden]').remove();
			field.find('a.new').before(label.hide());
			label.slideDown(100);
			label.datetime();
			// Select new panel
			var last = field.find('label:last').removeAttr('style');
			var input = last.find('input:first').focus();
			var date = Calendar.getDate(input.val());
			var calendar = label.next('.calendar');
			Calendar.updateSelect(calendar, date);
			Calendar.open(calendar, date);
			// Reset panels
			this.resetPanel(field.find('label'));
		},

		removePanel: function(label) {
			var field = label.parents('.field-datetime'),
                labels = field.find('label');

			if(label.hasClass('first') && labels.size() == 1) {
				label.find('input:eq(0)').val(this.setRelativeDate(this.getDate('now'))).removeClass('error');
				label.find('input:eq(1)').val('');
				label.find('span.end').slideUp(100);
				label.find('span.start').removeClass('range').find('em').text(this.settings.DATE);
				label.next('.calendar').slideUp(250);
			}
			else {
				Calendar.resetPanel(label.siblings('label'));
				label.slideUp(100, function() {
					var current = $(this);
					current.next('.calendar').remove();
					current.remove();
				});
			}
		},

		resetPanel: function(labels) {
			labels.removeClass('first').removeClass('last')
			labels.filter(':first').addClass('first');
			labels.filter(':last').addClass('last');
		}

	}

	/*
	 * Initialise date and time widget
	 */

	$(document).ready(function() {
		$('.field-datetime label').datetime();
		// make duplicatable
		$('.field-datetime a.new').click(function() {
			Calendar.addPanel($(this).parents('.field-datetime'));
		});
		// make sortable
		$('.field-datetime').sortable({
			axis: 'y',
			handle: 'em',
			placeholder: 'placeholder',
			tolerance: 'pointer',
			helper: 'clone',
			cursor: 'move',
			items: 'label',
			start: function(event, ui) {
				$(event.target).find('.calendar').remove();
				$(event.target).find('label').unbind();
				ui.placeholder.html(ui.helper.html());
				var start = ui.helper.find('span.start input').val();
				var end = ui.helper.find('span.end input').val();
				ui.placeholder.find('span.start input').val(start);
				ui.placeholder.find('span.end input').val(end);
				if(ui.helper.hasClass('first')) ui.placeholder.addClass('first');
				if(ui.helper.hasClass('last')) ui.placeholder.addClass('last');
			},
			change: function(event, ui) {
				var labels = ui.item.siblings('label').filter(':not(.ui-sortable-helper)');
				Calendar.resetPanel(labels);
			},
			over: function(event, ui) {
				ui.helper.css('opacity', '0');
			},
			stop: function(event, ui) {
				ui.item.css('opacity', '1');
				//Calendar.create($(event.target).find('label'));
				var labels = $(event.target).find('label');
				Calendar.resetPanel(labels);
				$(event.target).find('label').datetime();
			}
		});
	});

})(jQuery);