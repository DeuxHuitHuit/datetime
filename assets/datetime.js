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
			var label = $(this);
			var field = label.parent('.field-datetime');
			var input = label.find('input');
			// language
			if(Calendar.settings == undefined) Calendar.settings = eval('(' + input.filter(':hidden').val() + ')');
			// init
			if(Calendar.settings.prepopulate == 'yes' && !input.filter(':first').val()) input.filter(':first').val(Calendar.setRelativeDate(Date.parse('now')));
			if(Calendar.settings.multiple == 'no') field.find('a.new').remove();
			Calendar.create(label);	
			Calendar.toggleInput(label, 0);
			input.filter(':not(:hidden)').each(function() {
				if(this.value != '') {
					var current = Calendar.setRelativeDate(Date.parse(this.value.substring(0, 19)));
					$(this).val(current);
				}
			});
			// events
			input.click(function(event) { 
				event.preventDefault();
				var date = Calendar.getDate($(this).val());
				if(date !== null) {
					var calendar = label.next('.calendar');
					Calendar.updateSelect(calendar, date);
					Calendar.open(calendar, date);
				}
				else {
					$(this).addClass('error');
				}
			}).keyup(function(event) {
				var calendar = $(this).parents('label').next('.calendar');
				var date = Calendar.getDate($(this).val());
				if(date !== null) {
					Calendar.updateSelect(calendar, date);
					Calendar.update(calendar, date);
				}
				else {
					$(this).addClass('error');
				}
			});
			label.next('.calendar').find('select').change(function() {
				var select = $(this).siblings().andSelf();
				var date = Date.parse(select[0].value + ' ' + select[1].value);
				Calendar.update(select.parents('.calendar'), date);
			});
			label.next('.calendar').find('tbody td').click(function(event) {
				var select = label.next('.calendar').find('select');
				var day = $(this);
				var month = select[0].value;
				var year = select[1].value;
				var date = Date.parse(select[0].value + ' ' + select[1].value + ' ' + day.text());
				if(day.hasClass('last')) {
					if(date.is().january()) date.last().year();
					date.last().month();
				}
				if(day.hasClass('next')) {
					if(date.is().december()) date.next().year();
					date.next().month();
				}
				if(event.altKey) {
					Calendar.setEnd(label.next('.calendar'), date);
				}
				else {
					Calendar.setStart(label.next('.calendar'), date);
				}
			});
			label.find('a.delete').click(function(event) {
				event.preventDefault();
				event.stopPropagation();
				var label = $(this).parents('label');
				Calendar.removePanel(label);
			});
			$('.field-datetime').click(function(event) {
				$('body').one('click', function() {
					$('.field-datetime label').removeClass('active');
					$('.field-datetime .calendar').slideUp(100);
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
				label.find('span.start').removeClass('range');
				label.find('span.start em').text(this.settings.DATE);
			}
			else {
				label.find('span.end').slideDown(250);
				label.find('span.start').addClass('range');
				label.find('span.start em').text(this.settings.FROM);
			}
		},
	
		open: function(calendar, date) {
			calendar.slideDown(250).siblings('.calendar').slideUp(250);
			var label = calendar.prev('label');
			var labels = label.siblings('label');
			if(labels.size() > 0) {
				label.addClass('active');
			}
			labels.removeClass('active');
			this.update(calendar, date);
		},
		
		create: function(label) {
			// calendar
			var calendar = $('<div class="calendar"><div class="nav"><select class="month" /><select class="year" /></div><table><thead><tr /></thead><tbody /></table><div class="capsule"><div class="start time"><strong>' + this.settings.START + '</strong><em>--:--</em></div><div class="end time"><strong>' + this.settings.END + '</strong><em>--:--</em></div></div></div>').insertAfter(label).slideUp(0);
			$.each(Date.CultureInfo.abbreviatedDayNames, function() {
				calendar.find('thead tr').append('<td>' + this + '</td>');
			});
			var week = 1;
			while(week++ <= 6) {
				calendar.find('tbody').append('<tr><td class="even"><td class="odd"><td class="even"><td class="odd"><td class="even"><td class="odd"><td class="even"></td></tr>');
			}
			// select options
			$.each(Date.CultureInfo.monthNames, function(count) {
				count++;
				calendar.find('select.month').append('<option value="' + count + '">' + this + '</option>');
			});
			// time
			calendar.find('div.time').slider({
				max: 96,
				slide: function(event, ui) {
					if(ui.value != 0) {
						var time = (ui.value - 1) / 4;
						var hour = parseInt(time);
						var minute = 60 * (time - hour);
						time = new Date();
						time.setHours(hour, minute, 0);
						time = time.toString('HH:mm');
					}
					else {
						time = '--:--';
					}
					$(event.target).find('em').text(time);
				},
				stop: function(event, ui) {
					var slider = $(event.target);
					if(ui.value != 0) {
						var time = (ui.value - 1) / 4;
						var hour = parseInt(time);
						var minute = 60 * (time - hour);
						if(slider.hasClass('start')) {
							var start = $(event.target).parents('.calendar').prev('label').find('span.start input');
							Calendar.setTime(start, hour, minute);
						}
						else {
							var end = $(event.target).parents('.calendar').prev('label').find('span.end input');
							if(end.val() == '') {
								var start = $(event.target).parents('.calendar').prev('label').find('span.start input');
								end.val(start.val());
								Calendar.toggleInput(end.parents('label'));
							}
							Calendar.setTime(end, hour, minute);
						}
						// compare times
						Calendar.compareTimes($(event.target).parents('.calendar'));
					}
					else {
						if(slider.hasClass('end')) {
							var calendar = $(event.target).parents('.calendar');
							var end = calendar.prev('label').find('span.end input').val('');
							calendar.find('tbody td').removeClass('end').removeClass('range');
							Calendar.toggleInput(end.parents('label'));
						}
					}
				}
			});
		},
		
		update: function(calendar, date) {
			var startInput = calendar.prev('label').find('.start input').removeClass();
			var endInput = calendar.prev('label').find('.end input').removeClass();
			// dates
			var today = this.getDate('today');
			var start = this.getDate(startInput.val());
			var end = this.getDate(endInput.val());
			// fallback dates
			if(end == null) { 
				end = start;
				if(endInput.val() != '') endInput.addClass('error');
			}
			else {
				this.setTimeSlider(calendar.find('div.end'), end.toString('HH'), end.toString('mm'));
			}
			if(date == null) date = start;
			if(start == null) {
				startInput.addClass('error');
				return false;
			}
			else {
				this.setTimeSlider(calendar.find('div.start'), start.toString('HH'), start.toString('mm'));
			}
			// populate calendar
			var current = date.clone();
			current.clearTime();
			current.set({ day: 1 });
			if (!current.is().sunday()) current.last().sunday();
			calendar.find('tbody td').removeClass('start').removeClass('end').removeClass('today').removeClass('range').each(function() {
				var day = $(this);
				day.text(current.toString('d'));
				// month context
				if(current.toString('M') == (parseInt(date.toString('M')) - 1) || (current.toString('M') == 12 && date.toString('M') == 1)) day.addClass('last');
				if(current.toString('M') == (parseInt(date.toString('M')) + 1) || (current.toString('M') == 1 && date.toString('M') == 12)) day.addClass('next');
				// day context
				if(current.equals(today.clearTime())) day.addClass('today');
				if(current.equals(start.clearTime())) day.addClass('start');
				if(current.between(start.clearTime(), end.clearTime())) day.addClass('range');
				if(end != start) {
					if(current.equals(end.clearTime())) day.addClass('end');			
				}
				// move to next day
				current.next().day();
			})
		},
		
		updateSelect: function(calendar, date) {
			// set month
			calendar.find('select.month option[value=' + date.toString('M') + ']').attr('selected', true);
			// set year
			var year = date.toString('yyyy');
			var select = calendar.find('select.year');
			select.find('option').remove().end().append('<option value="' + year + '" selected="selected">' + year + '</option>');
			var plus = minus = year;
			for(x = 1; x <= 5; x++) {
				plus++;
				select.prepend('<option value="' + plus + '">' + plus + '</option>');
				minus--;
				select.append('<option value="' + minus + '">' + minus + '</option>');		
			}
		},
			
		setStart: function(calendar, date) {
			var label = calendar.prev('label');
			var start = label.find('span.start input');
			var end = label.find('span.end input');
			var current = this.getDate(start.val());
			// set date
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
			var label = calendar.prev('label');
			var start = label.find('span.start input');
			var end = label.find('span.end input');
			var current = this.getDate(end.val());
			// set date
			if(current == null) current = this.getDate('now');
			date.setHours(current.toString('HH'), current.toString('mm'));
			end.val(this.setRelativeDate(date));
			// check start and end relation
			var startDate = this.getDate(start.val());
			var endDate = this.getDate(end.val());
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
			var current = date.clone().clearTime();
			var textFull = date.toString(this.settings.FORMAT);
			var textShort = textFull.split(',')[0];
			var yesterday = Date.parse('yesterday').clearTime();
			var today = Date.parse('today').clearTime();
			var tomorrow = Date.parse('tomorrow').clearTime();
			if(current.equals(yesterday)) {
				return textFull.replace(textShort, 'yesterday');
			}
			if(current.equals(today)) {
				return textFull.replace(textShort, 'today');
			}
			if(current.equals(tomorrow)) {
				return textFull.replace(textShort, 'tomorrow');
			}
			return date.toString(this.settings.FORMAT);
		},
		
		getDate: function(date) {
			if(date.search(Date.CultureInfo.regexPatterns.yesterday) != -1 || date.search(Date.CultureInfo.regexPatterns.today) != -1 || date.search(Date.CultureInfo.regexPatterns.now) != -1 || date.search(Date.CultureInfo.regexPatterns.tomorrow) != -1) {
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
			var startInput = calendar.prev('label').find('span.start input');
			var endInput = calendar.prev('label').find('span.end input');
			var start = this.getDate(startInput.val());
			var end = this.getDate(endInput.val());
			if(end !== null) {
				if(end.compareTo(start) == -1) {
					// switch dates
					startInput.val(this.setRelativeDate(end));
					this.setTimeSlider(calendar.find('div.start'), end.toString('HH'), end.toString('mm'));
					endInput.val(this.setRelativeDate(start));
					this.setTimeSlider(calendar.find('div.end'), start.toString('HH'), start.toString('mm'));
				}
			}
		},
		
		addPanel: function(field) {
			var label = field.find('label:first').clone().removeClass();
			label.find('span.end').hide();
			label.find('span.start').removeClass('range');
			label.find('span.start em').text(this.settings.DATE);
			var offset = parseInt(Date.today().getTimezoneOffset() / -60);
			label.find('input').val('').filter(':first').val(this.getDate('now').toString('yyyy-MM-ddTHH:mm:ss') + '+' + offset + '00');
			label.find('input[type=hidden]').remove();
			field.find('a.new').before(label.hide());
			label.slideDown(100);
			label.datetime();
			this.resetPanel(field.find('label'));
		},
		
		removePanel: function(label) {
			var field = label.parents('.field-datetime');
			var labels = field.find('label');
			if(label.hasClass('first') && labels.size() == 1) {
				label.find('input').val(this.setRelativeDate(this.getDate('now'))).removeClass('error');
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
			labels.removeClass('first').removeClass('last');
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
		$('.field-datetime a.new').click(function(event) {
			var field = $(this).parents('.field-datetime');
			Calendar.addPanel(field);
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


/**
 * Version: 1.0 Alpha-1 
 * Build Date: 13-Nov-2007
 * Copyright (c) 2006-2007, Coolite Inc. (http://www.coolite.com/). All rights reserved.
 * License: Licensed under The MIT License. See license.txt and http://www.datejs.com/license/. 
 * Website: http://www.datejs.com/ or http://www.coolite.com/datejs/
 */
Date.CultureInfo={name:"en-US",englishName:"English (United States)",nativeName:"English (United States)",dayNames:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],abbreviatedDayNames:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],shortestDayNames:["Su","Mo","Tu","We","Th","Fr","Sa"],firstLetterDayNames:["S","M","T","W","T","F","S"],monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],abbreviatedMonthNames:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],amDesignator:"AM",pmDesignator:"PM",firstDayOfWeek:0,twoDigitYearMax:2029,dateElementOrder:"mdy",formatPatterns:{shortDate:"M/d/yyyy",longDate:"dddd, MMMM dd, yyyy",shortTime:"h:mm tt",longTime:"h:mm:ss tt",fullDateTime:"dddd, MMMM dd, yyyy h:mm:ss tt",sortableDateTime:"yyyy-MM-ddTHH:mm:ss",universalSortableDateTime:"yyyy-MM-dd HH:mm:ssZ",rfc1123:"ddd, dd MMM yyyy HH:mm:ss GMT",monthDay:"MMMM dd",yearMonth:"MMMM, yyyy"},regexPatterns:{jan:/^jan(uary)?/i,feb:/^feb(ruary)?/i,mar:/^mar(ch)?/i,apr:/^apr(il)?/i,may:/^may/i,jun:/^jun(e)?/i,jul:/^jul(y)?/i,aug:/^aug(ust)?/i,sep:/^sep(t(ember)?)?/i,oct:/^oct(ober)?/i,nov:/^nov(ember)?/i,dec:/^dec(ember)?/i,sun:/^su(n(day)?)?/i,mon:/^mo(n(day)?)?/i,tue:/^tu(e(s(day)?)?)?/i,wed:/^we(d(nesday)?)?/i,thu:/^th(u(r(s(day)?)?)?)?/i,fri:/^fr(i(day)?)?/i,sat:/^sa(t(urday)?)?/i,future:/^next/i,past:/^last|past|prev(ious)?/i,add:/^(\+|after|from)/i,subtract:/^(\-|before|ago)/i,yesterday:/^yesterday/i,today:/^t(oday)?/i,tomorrow:/^tomorrow/i,now:/^n(ow)?/i,millisecond:/^ms|milli(second)?s?/i,second:/^sec(ond)?s?/i,minute:/^min(ute)?s?/i,hour:/^h(ou)?rs?/i,week:/^w(ee)?k/i,month:/^m(o(nth)?s?)?/i,day:/^d(ays?)?/i,year:/^y((ea)?rs?)?/i,shortMeridian:/^(a|p)/i,longMeridian:/^(a\.?m?\.?|p\.?m?\.?)/i,timezone:/^((e(s|d)t|c(s|d)t|m(s|d)t|p(s|d)t)|((gmt)?\s*(\+|\-)\s*\d\d\d\d?)|gmt)/i,ordinalSuffix:/^\s*(st|nd|rd|th)/i,timeContext:/^\s*(\:|a|p)/i},abbreviatedTimeZoneStandard:{GMT:"-000",EST:"-0400",CST:"-0500",MST:"-0600",PST:"-0700"},abbreviatedTimeZoneDST:{GMT:"-000",EDT:"-0500",CDT:"-0600",MDT:"-0700",PDT:"-0800"}};
Date.getMonthNumberFromName=function(name){var n=Date.CultureInfo.monthNames,m=Date.CultureInfo.abbreviatedMonthNames,s=name.toLowerCase();for(var i=0;i<n.length;i++){if(n[i].toLowerCase()==s||m[i].toLowerCase()==s){return i;}}
return-1;};Date.getDayNumberFromName=function(name){var n=Date.CultureInfo.dayNames,m=Date.CultureInfo.abbreviatedDayNames,o=Date.CultureInfo.shortestDayNames,s=name.toLowerCase();for(var i=0;i<n.length;i++){if(n[i].toLowerCase()==s||m[i].toLowerCase()==s){return i;}}
return-1;};Date.isLeapYear=function(year){return(((year%4===0)&&(year%100!==0))||(year%400===0));};Date.getDaysInMonth=function(year,month){return[31,(Date.isLeapYear(year)?29:28),31,30,31,30,31,31,30,31,30,31][month];};Date.getTimezoneOffset=function(s,dst){return(dst||false)?Date.CultureInfo.abbreviatedTimeZoneDST[s.toUpperCase()]:Date.CultureInfo.abbreviatedTimeZoneStandard[s.toUpperCase()];};Date.getTimezoneAbbreviation=function(offset,dst){var n=(dst||false)?Date.CultureInfo.abbreviatedTimeZoneDST:Date.CultureInfo.abbreviatedTimeZoneStandard,p;for(p in n){if(n[p]===offset){return p;}}
return null;};Date.prototype.clone=function(){return new Date(this.getTime());};Date.prototype.compareTo=function(date){if(isNaN(this)){throw new Error(this);}
if(date instanceof Date&&!isNaN(date)){return(this>date)?1:(this<date)?-1:0;}else{throw new TypeError(date);}};Date.prototype.equals=function(date){return(this.compareTo(date)===0);};Date.prototype.between=function(start,end){var t=this.getTime();return t>=start.getTime()&&t<=end.getTime();};Date.prototype.addMilliseconds=function(value){this.setMilliseconds(this.getMilliseconds()+value);return this;};Date.prototype.addSeconds=function(value){return this.addMilliseconds(value*1000);};Date.prototype.addMinutes=function(value){return this.addMilliseconds(value*60000);};Date.prototype.addHours=function(value){return this.addMilliseconds(value*3600000);};Date.prototype.addDays=function(value){return this.addMilliseconds(value*86400000);};Date.prototype.addWeeks=function(value){return this.addMilliseconds(value*604800000);};Date.prototype.addMonths=function(value){var n=this.getDate();this.setDate(1);this.setMonth(this.getMonth()+value);this.setDate(Math.min(n,this.getDaysInMonth()));return this;};Date.prototype.addYears=function(value){return this.addMonths(value*12);};Date.prototype.add=function(config){if(typeof config=="number"){this._orient=config;return this;}
var x=config;if(x.millisecond||x.milliseconds){this.addMilliseconds(x.millisecond||x.milliseconds);}
if(x.second||x.seconds){this.addSeconds(x.second||x.seconds);}
if(x.minute||x.minutes){this.addMinutes(x.minute||x.minutes);}
if(x.hour||x.hours){this.addHours(x.hour||x.hours);}
if(x.month||x.months){this.addMonths(x.month||x.months);}
if(x.year||x.years){this.addYears(x.year||x.years);}
if(x.day||x.days){this.addDays(x.day||x.days);}
return this;};Date._validate=function(value,min,max,name){if(typeof value!="number"){throw new TypeError(value+" is not a Number.");}else if(value<min||value>max){throw new RangeError(value+" is not a valid value for "+name+".");}
return true;};Date.validateMillisecond=function(n){return Date._validate(n,0,999,"milliseconds");};Date.validateSecond=function(n){return Date._validate(n,0,59,"seconds");};Date.validateMinute=function(n){return Date._validate(n,0,59,"minutes");};Date.validateHour=function(n){return Date._validate(n,0,23,"hours");};Date.validateDay=function(n,year,month){return Date._validate(n,1,Date.getDaysInMonth(year,month),"days");};Date.validateMonth=function(n){return Date._validate(n,0,11,"months");};Date.validateYear=function(n){return Date._validate(n,1,9999,"seconds");};Date.prototype.set=function(config){var x=config;if(!x.millisecond&&x.millisecond!==0){x.millisecond=-1;}
if(!x.second&&x.second!==0){x.second=-1;}
if(!x.minute&&x.minute!==0){x.minute=-1;}
if(!x.hour&&x.hour!==0){x.hour=-1;}
if(!x.day&&x.day!==0){x.day=-1;}
if(!x.month&&x.month!==0){x.month=-1;}
if(!x.year&&x.year!==0){x.year=-1;}
if(x.millisecond!=-1&&Date.validateMillisecond(x.millisecond)){this.addMilliseconds(x.millisecond-this.getMilliseconds());}
if(x.second!=-1&&Date.validateSecond(x.second)){this.addSeconds(x.second-this.getSeconds());}
if(x.minute!=-1&&Date.validateMinute(x.minute)){this.addMinutes(x.minute-this.getMinutes());}
if(x.hour!=-1&&Date.validateHour(x.hour)){this.addHours(x.hour-this.getHours());}
if(x.month!==-1&&Date.validateMonth(x.month)){this.addMonths(x.month-this.getMonth());}
if(x.year!=-1&&Date.validateYear(x.year)){this.addYears(x.year-this.getFullYear());}
if(x.day!=-1&&Date.validateDay(x.day,this.getFullYear(),this.getMonth())){this.addDays(x.day-this.getDate());}
if(x.timezone){this.setTimezone(x.timezone);}
if(x.timezoneOffset){this.setTimezoneOffset(x.timezoneOffset);}
return this;};Date.prototype.clearTime=function(){this.setHours(0);this.setMinutes(0);this.setSeconds(0);this.setMilliseconds(0);return this;};Date.prototype.isLeapYear=function(){var y=this.getFullYear();return(((y%4===0)&&(y%100!==0))||(y%400===0));};Date.prototype.isWeekday=function(){return!(this.is().sat()||this.is().sun());};Date.prototype.getDaysInMonth=function(){return Date.getDaysInMonth(this.getFullYear(),this.getMonth());};Date.prototype.moveToFirstDayOfMonth=function(){return this.set({day:1});};Date.prototype.moveToLastDayOfMonth=function(){return this.set({day:this.getDaysInMonth()});};Date.prototype.moveToDayOfWeek=function(day,orient){var diff=(day-this.getDay()+7*(orient||+1))%7;return this.addDays((diff===0)?diff+=7*(orient||+1):diff);};Date.prototype.moveToMonth=function(month,orient){var diff=(month-this.getMonth()+12*(orient||+1))%12;return this.addMonths((diff===0)?diff+=12*(orient||+1):diff);};Date.prototype.getDayOfYear=function(){return Math.floor((this-new Date(this.getFullYear(),0,1))/86400000);};Date.prototype.getWeekOfYear=function(firstDayOfWeek){var y=this.getFullYear(),m=this.getMonth(),d=this.getDate();var dow=firstDayOfWeek||Date.CultureInfo.firstDayOfWeek;var offset=7+1-new Date(y,0,1).getDay();if(offset==8){offset=1;}
var daynum=((Date.UTC(y,m,d,0,0,0)-Date.UTC(y,0,1,0,0,0))/86400000)+1;var w=Math.floor((daynum-offset+7)/7);if(w===dow){y--;var prevOffset=7+1-new Date(y,0,1).getDay();if(prevOffset==2||prevOffset==8){w=53;}else{w=52;}}
return w;};Date.prototype.isDST=function(){console.log('isDST');return this.toString().match(/(E|C|M|P)(S|D)T/)[2]=="D";};Date.prototype.getTimezone=function(){return Date.getTimezoneAbbreviation(this.getUTCOffset,this.isDST());};Date.prototype.setTimezoneOffset=function(s){var here=this.getTimezoneOffset(),there=Number(s)*-6/10;this.addMinutes(there-here);return this;};Date.prototype.setTimezone=function(s){return this.setTimezoneOffset(Date.getTimezoneOffset(s));};Date.prototype.getUTCOffset=function(){var n=this.getTimezoneOffset()*-10/6,r;if(n<0){r=(n-10000).toString();return r[0]+r.substr(2);}else{r=(n+10000).toString();return"+"+r.substr(1);}};Date.prototype.getDayName=function(abbrev){return abbrev?Date.CultureInfo.abbreviatedDayNames[this.getDay()]:Date.CultureInfo.dayNames[this.getDay()];};Date.prototype.getMonthName=function(abbrev){return abbrev?Date.CultureInfo.abbreviatedMonthNames[this.getMonth()]:Date.CultureInfo.monthNames[this.getMonth()];};Date.prototype._toString=Date.prototype.toString;Date.prototype.toString=function(format){var self=this;var p=function p(s){return(s.toString().length==1)?"0"+s:s;};return format?format.replace(/dd?d?d?|MM?M?M?|yy?y?y?|hh?|HH?|mm?|ss?|tt?|zz?z?/g,function(format){switch(format){case"hh":return p(self.getHours()<13?self.getHours():(self.getHours()-12));case"h":return self.getHours()<13?self.getHours():(self.getHours()-12);case"HH":return p(self.getHours());case"H":return self.getHours();case"mm":return p(self.getMinutes());case"m":return self.getMinutes();case"ss":return p(self.getSeconds());case"s":return self.getSeconds();case"yyyy":return self.getFullYear();case"yy":return self.getFullYear().toString().substring(2,4);case"dddd":return self.getDayName();case"ddd":return self.getDayName(true);case"dd":return p(self.getDate());case"d":return self.getDate().toString();case"MMMM":return self.getMonthName();case"MMM":return self.getMonthName(true);case"MM":return p((self.getMonth()+1));case"M":return self.getMonth()+1;case"t":return self.getHours()<12?Date.CultureInfo.amDesignator.substring(0,1):Date.CultureInfo.pmDesignator.substring(0,1);case"tt":return self.getHours()<12?Date.CultureInfo.amDesignator:Date.CultureInfo.pmDesignator;case"zzz":case"zz":case"z":return"";}}):this._toString();};
Date.now=function(){return new Date();};Date.today=function(){return Date.now().clearTime();};Date.prototype._orient=+1;Date.prototype.next=function(){this._orient=+1;return this;};Date.prototype.last=Date.prototype.prev=Date.prototype.previous=function(){this._orient=-1;return this;};Date.prototype._is=false;Date.prototype.is=function(){this._is=true;return this;};Number.prototype._dateElement="day";Number.prototype.fromNow=function(){var c={};c[this._dateElement]=this;return Date.now().add(c);};Number.prototype.ago=function(){var c={};c[this._dateElement]=this*-1;return Date.now().add(c);};(function(){var $D=Date.prototype,$N=Number.prototype;var dx=("sunday monday tuesday wednesday thursday friday saturday").split(/\s/),mx=("january february march april may june july august september october november december").split(/\s/),px=("Millisecond Second Minute Hour Day Week Month Year").split(/\s/),de;var df=function(n){return function(){if(this._is){this._is=false;return this.getDay()==n;}
return this.moveToDayOfWeek(n,this._orient);};};for(var i=0;i<dx.length;i++){$D[dx[i]]=$D[dx[i].substring(0,3)]=df(i);}
var mf=function(n){return function(){if(this._is){this._is=false;return this.getMonth()===n;}
return this.moveToMonth(n,this._orient);};};for(var j=0;j<mx.length;j++){$D[mx[j]]=$D[mx[j].substring(0,3)]=mf(j);}
var ef=function(j){return function(){if(j.substring(j.length-1)!="s"){j+="s";}
return this["add"+j](this._orient);};};var nf=function(n){return function(){this._dateElement=n;return this;};};for(var k=0;k<px.length;k++){de=px[k].toLowerCase();$D[de]=$D[de+"s"]=ef(px[k]);$N[de]=$N[de+"s"]=nf(de);}}());Date.prototype.toJSONString=function(){return this.toString("yyyy-MM-ddThh:mm:ssZ");};Date.prototype.toShortDateString=function(){return this.toString(Date.CultureInfo.formatPatterns.shortDatePattern);};Date.prototype.toLongDateString=function(){return this.toString(Date.CultureInfo.formatPatterns.longDatePattern);};Date.prototype.toShortTimeString=function(){return this.toString(Date.CultureInfo.formatPatterns.shortTimePattern);};Date.prototype.toLongTimeString=function(){return this.toString(Date.CultureInfo.formatPatterns.longTimePattern);};Date.prototype.getOrdinal=function(){switch(this.getDate()){case 1:case 21:case 31:return"st";case 2:case 22:return"nd";case 3:case 23:return"rd";default:return"th";}};
(function(){Date.Parsing={Exception:function(s){this.message="Parse error at '"+s.substring(0,10)+" ...'";}};var $P=Date.Parsing;var _=$P.Operators={rtoken:function(r){return function(s){var mx=s.match(r);if(mx){return([mx[0],s.substring(mx[0].length)]);}else{throw new $P.Exception(s);}};},token:function(s){return function(s){return _.rtoken(new RegExp("^\s*"+s+"\s*"))(s);};},stoken:function(s){return _.rtoken(new RegExp("^"+s));},until:function(p){return function(s){var qx=[],rx=null;while(s.length){try{rx=p.call(this,s);}catch(e){qx.push(rx[0]);s=rx[1];continue;}
break;}
return[qx,s];};},many:function(p){return function(s){var rx=[],r=null;while(s.length){try{r=p.call(this,s);}catch(e){return[rx,s];}
rx.push(r[0]);s=r[1];}
return[rx,s];};},optional:function(p){return function(s){var r=null;try{r=p.call(this,s);}catch(e){return[null,s];}
return[r[0],r[1]];};},not:function(p){return function(s){try{p.call(this,s);}catch(e){return[null,s];}
throw new $P.Exception(s);};},ignore:function(p){return p?function(s){var r=null;r=p.call(this,s);return[null,r[1]];}:null;},product:function(){var px=arguments[0],qx=Array.prototype.slice.call(arguments,1),rx=[];for(var i=0;i<px.length;i++){rx.push(_.each(px[i],qx));}
return rx;},cache:function(rule){var cache={},r=null;return function(s){try{r=cache[s]=(cache[s]||rule.call(this,s));}catch(e){r=cache[s]=e;}
if(r instanceof $P.Exception){throw r;}else{return r;}};},any:function(){var px=arguments;return function(s){var r=null;for(var i=0;i<px.length;i++){if(px[i]==null){continue;}
try{r=(px[i].call(this,s));}catch(e){r=null;}
if(r){return r;}}
throw new $P.Exception(s);};},each:function(){var px=arguments;return function(s){var rx=[],r=null;for(var i=0;i<px.length;i++){if(px[i]==null){continue;}
try{r=(px[i].call(this,s));}catch(e){throw new $P.Exception(s);}
rx.push(r[0]);s=r[1];}
return[rx,s];};},all:function(){var px=arguments,_=_;return _.each(_.optional(px));},sequence:function(px,d,c){d=d||_.rtoken(/^\s*/);c=c||null;if(px.length==1){return px[0];}
return function(s){var r=null,q=null;var rx=[];for(var i=0;i<px.length;i++){try{r=px[i].call(this,s);}catch(e){break;}
rx.push(r[0]);try{q=d.call(this,r[1]);}catch(ex){q=null;break;}
s=q[1];}
if(!r){throw new $P.Exception(s);}
if(q){throw new $P.Exception(q[1]);}
if(c){try{r=c.call(this,r[1]);}catch(ey){throw new $P.Exception(r[1]);}}
return[rx,(r?r[1]:s)];};},between:function(d1,p,d2){d2=d2||d1;var _fn=_.each(_.ignore(d1),p,_.ignore(d2));return function(s){var rx=_fn.call(this,s);return[[rx[0][0],r[0][2]],rx[1]];};},list:function(p,d,c){d=d||_.rtoken(/^\s*/);c=c||null;return(p instanceof Array?_.each(_.product(p.slice(0,-1),_.ignore(d)),p.slice(-1),_.ignore(c)):_.each(_.many(_.each(p,_.ignore(d))),px,_.ignore(c)));},set:function(px,d,c){d=d||_.rtoken(/^\s*/);c=c||null;return function(s){var r=null,p=null,q=null,rx=null,best=[[],s],last=false;for(var i=0;i<px.length;i++){q=null;p=null;r=null;last=(px.length==1);try{r=px[i].call(this,s);}catch(e){continue;}
rx=[[r[0]],r[1]];if(r[1].length>0&&!last){try{q=d.call(this,r[1]);}catch(ex){last=true;}}else{last=true;}
if(!last&&q[1].length===0){last=true;}
if(!last){var qx=[];for(var j=0;j<px.length;j++){if(i!=j){qx.push(px[j]);}}
p=_.set(qx,d).call(this,q[1]);if(p[0].length>0){rx[0]=rx[0].concat(p[0]);rx[1]=p[1];}}
if(rx[1].length<best[1].length){best=rx;}
if(best[1].length===0){break;}}
if(best[0].length===0){return best;}
if(c){try{q=c.call(this,best[1]);}catch(ey){throw new $P.Exception(best[1]);}
best[1]=q[1];}
return best;};},forward:function(gr,fname){return function(s){return gr[fname].call(this,s);};},replace:function(rule,repl){return function(s){var r=rule.call(this,s);return[repl,r[1]];};},process:function(rule,fn){return function(s){var r=rule.call(this,s);return[fn.call(this,r[0]),r[1]];};},min:function(min,rule){return function(s){var rx=rule.call(this,s);if(rx[0].length<min){throw new $P.Exception(s);}
return rx;};}};var _generator=function(op){return function(){var args=null,rx=[];if(arguments.length>1){args=Array.prototype.slice.call(arguments);}else if(arguments[0]instanceof Array){args=arguments[0];}
if(args){for(var i=0,px=args.shift();i<px.length;i++){args.unshift(px[i]);rx.push(op.apply(null,args));args.shift();return rx;}}else{return op.apply(null,arguments);}};};var gx="optional not ignore cache".split(/\s/);for(var i=0;i<gx.length;i++){_[gx[i]]=_generator(_[gx[i]]);}
var _vector=function(op){return function(){if(arguments[0]instanceof Array){return op.apply(null,arguments[0]);}else{return op.apply(null,arguments);}};};var vx="each any all".split(/\s/);for(var j=0;j<vx.length;j++){_[vx[j]]=_vector(_[vx[j]]);}}());(function(){var flattenAndCompact=function(ax){var rx=[];for(var i=0;i<ax.length;i++){if(ax[i]instanceof Array){rx=rx.concat(flattenAndCompact(ax[i]));}else{if(ax[i]){rx.push(ax[i]);}}}
return rx;};Date.Grammar={};Date.Translator={hour:function(s){return function(){this.hour=Number(s);};},minute:function(s){return function(){this.minute=Number(s);};},second:function(s){return function(){this.second=Number(s);};},meridian:function(s){return function(){this.meridian=s.slice(0,1).toLowerCase();};},timezone:function(s){return function(){var n=s.replace(/[^\d\+\-]/g,"");if(n.length){this.timezoneOffset=Number(n);}else{this.timezone=s.toLowerCase();}};},day:function(x){var s=x[0];return function(){this.day=Number(s.match(/\d+/)[0]);};},month:function(s){return function(){this.month=((s.length==3)?Date.getMonthNumberFromName(s):(Number(s)-1));};},year:function(s){return function(){var n=Number(s);this.year=((s.length>2)?n:(n+(((n+2000)<Date.CultureInfo.twoDigitYearMax)?2000:1900)));};},rday:function(s){return function(){switch(s){case"yesterday":this.days=-1;break;case"tomorrow":this.days=1;break;case"today":this.days=0;break;case"now":this.days=0;this.now=true;break;}};},finishExact:function(x){x=(x instanceof Array)?x:[x];var now=new Date();this.year=now.getFullYear();this.month=now.getMonth();this.day=1;this.hour=0;this.minute=0;this.second=0;for(var i=0;i<x.length;i++){if(x[i]){x[i].call(this);}}
this.hour=(this.meridian=="p"&&this.hour<13)?this.hour+12:this.hour;if(this.day>Date.getDaysInMonth(this.year,this.month)){throw new RangeError(this.day+" is not a valid value for days.");}
var r=new Date(this.year,this.month,this.day,this.hour,this.minute,this.second);if(this.timezone){r.set({timezone:this.timezone});}else if(this.timezoneOffset){r.set({timezoneOffset:this.timezoneOffset});}
return r;},finish:function(x){x=(x instanceof Array)?flattenAndCompact(x):[x];if(x.length===0){return null;}
for(var i=0;i<x.length;i++){if(typeof x[i]=="function"){x[i].call(this);}}
if(this.now){return new Date();}
var today=Date.today();var method=null;var expression=!!(this.days!=null||this.orient||this.operator);if(expression){var gap,mod,orient;orient=((this.orient=="past"||this.operator=="subtract")?-1:1);if(this.weekday){this.unit="day";gap=(Date.getDayNumberFromName(this.weekday)-today.getDay());mod=7;this.days=gap?((gap+(orient*mod))%mod):(orient*mod);}
if(this.month){this.unit="month";gap=(this.month-today.getMonth());mod=12;this.months=gap?((gap+(orient*mod))%mod):(orient*mod);this.month=null;}
if(!this.unit){this.unit="day";}
if(this[this.unit+"s"]==null||this.operator!=null){if(!this.value){this.value=1;}
if(this.unit=="week"){this.unit="day";this.value=this.value*7;}
this[this.unit+"s"]=this.value*orient;}
return today.add(this);}else{if(this.meridian&&this.hour){this.hour=(this.hour<13&&this.meridian=="p")?this.hour+12:this.hour;}
if(this.weekday&&!this.day){this.day=(today.addDays((Date.getDayNumberFromName(this.weekday)-today.getDay()))).getDate();}
if(this.month&&!this.day){this.day=1;}
return today.set(this);}}};var _=Date.Parsing.Operators,g=Date.Grammar,t=Date.Translator,_fn;g.datePartDelimiter=_.rtoken(/^([\s\-\.\,\/\x27]+)/);g.timePartDelimiter=_.stoken(":");g.whiteSpace=_.rtoken(/^\s*/);g.generalDelimiter=_.rtoken(/^(([\s\,]|at|on)+)/);var _C={};g.ctoken=function(keys){var fn=_C[keys];if(!fn){var c=Date.CultureInfo.regexPatterns;var kx=keys.split(/\s+/),px=[];for(var i=0;i<kx.length;i++){px.push(_.replace(_.rtoken(c[kx[i]]),kx[i]));}
fn=_C[keys]=_.any.apply(null,px);}
return fn;};g.ctoken2=function(key){return _.rtoken(Date.CultureInfo.regexPatterns[key]);};g.h=_.cache(_.process(_.rtoken(/^(0[0-9]|1[0-2]|[1-9])/),t.hour));g.hh=_.cache(_.process(_.rtoken(/^(0[0-9]|1[0-2])/),t.hour));g.H=_.cache(_.process(_.rtoken(/^([0-1][0-9]|2[0-3]|[0-9])/),t.hour));g.HH=_.cache(_.process(_.rtoken(/^([0-1][0-9]|2[0-3])/),t.hour));g.m=_.cache(_.process(_.rtoken(/^([0-5][0-9]|[0-9])/),t.minute));g.mm=_.cache(_.process(_.rtoken(/^[0-5][0-9]/),t.minute));g.s=_.cache(_.process(_.rtoken(/^([0-5][0-9]|[0-9])/),t.second));g.ss=_.cache(_.process(_.rtoken(/^[0-5][0-9]/),t.second));g.hms=_.cache(_.sequence([g.H,g.mm,g.ss],g.timePartDelimiter));g.t=_.cache(_.process(g.ctoken2("shortMeridian"),t.meridian));g.tt=_.cache(_.process(g.ctoken2("longMeridian"),t.meridian));g.z=_.cache(_.process(_.rtoken(/^(\+|\-)?\s*\d\d\d\d?/),t.timezone));g.zz=_.cache(_.process(_.rtoken(/^(\+|\-)\s*\d\d\d\d/),t.timezone));g.zzz=_.cache(_.process(g.ctoken2("timezone"),t.timezone));g.timeSuffix=_.each(_.ignore(g.whiteSpace),_.set([g.tt,g.zzz]));g.time=_.each(_.optional(_.ignore(_.stoken("T"))),g.hms,g.timeSuffix);g.d=_.cache(_.process(_.each(_.rtoken(/^([0-2]\d|3[0-1]|\d)/),_.optional(g.ctoken2("ordinalSuffix"))),t.day));g.dd=_.cache(_.process(_.each(_.rtoken(/^([0-2]\d|3[0-1])/),_.optional(g.ctoken2("ordinalSuffix"))),t.day));g.ddd=g.dddd=_.cache(_.process(g.ctoken("sun mon tue wed thu fri sat"),function(s){return function(){this.weekday=s;};}));g.M=_.cache(_.process(_.rtoken(/^(1[0-2]|0\d|\d)/),t.month));g.MM=_.cache(_.process(_.rtoken(/^(1[0-2]|0\d)/),t.month));g.MMM=g.MMMM=_.cache(_.process(g.ctoken("jan feb mar apr may jun jul aug sep oct nov dec"),t.month));g.y=_.cache(_.process(_.rtoken(/^(\d\d?)/),t.year));g.yy=_.cache(_.process(_.rtoken(/^(\d\d)/),t.year));g.yyy=_.cache(_.process(_.rtoken(/^(\d\d?\d?\d?)/),t.year));g.yyyy=_.cache(_.process(_.rtoken(/^(\d\d\d\d)/),t.year));_fn=function(){return _.each(_.any.apply(null,arguments),_.not(g.ctoken2("timeContext")));};g.day=_fn(g.d,g.dd);g.month=_fn(g.M,g.MMM);g.year=_fn(g.yyyy,g.yy);g.orientation=_.process(g.ctoken("past future"),function(s){return function(){this.orient=s;};});g.operator=_.process(g.ctoken("add subtract"),function(s){return function(){this.operator=s;};});g.rday=_.process(g.ctoken("yesterday tomorrow today now"),t.rday);g.unit=_.process(g.ctoken("minute hour day week month year"),function(s){return function(){this.unit=s;};});g.value=_.process(_.rtoken(/^\d\d?(st|nd|rd|th)?/),function(s){return function(){this.value=s.replace(/\D/g,"");};});g.expression=_.set([g.rday,g.operator,g.value,g.unit,g.orientation,g.ddd,g.MMM]);_fn=function(){return _.set(arguments,g.datePartDelimiter);};g.mdy=_fn(g.ddd,g.month,g.day,g.year);g.ymd=_fn(g.ddd,g.year,g.month,g.day);g.dmy=_fn(g.ddd,g.day,g.month,g.year);g.date=function(s){return((g[Date.CultureInfo.dateElementOrder]||g.mdy).call(this,s));};g.format=_.process(_.many(_.any(_.process(_.rtoken(/^(dd?d?d?|MM?M?M?|yy?y?y?|hh?|HH?|mm?|ss?|tt?|zz?z?)/),function(fmt){if(g[fmt]){return g[fmt];}else{throw Date.Parsing.Exception(fmt);}}),_.process(_.rtoken(/^[^dMyhHmstz]+/),function(s){return _.ignore(_.stoken(s));}))),function(rules){return _.process(_.each.apply(null,rules),t.finishExact);});var _F={};var _get=function(f){return _F[f]=(_F[f]||g.format(f)[0]);};g.formats=function(fx){if(fx instanceof Array){var rx=[];for(var i=0;i<fx.length;i++){rx.push(_get(fx[i]));}
return _.any.apply(null,rx);}else{return _get(fx);}};g._formats=g.formats(["yyyy-MM-ddTHH:mm:ss","ddd, MMM dd, yyyy H:mm:ss tt","ddd MMM d yyyy HH:mm:ss zzz","d"]);g._start=_.process(_.set([g.date,g.time,g.expression],g.generalDelimiter,g.whiteSpace),t.finish);g.start=function(s){try{var r=g._formats.call({},s);if(r[1].length===0){return r;}}catch(e){}
return g._start.call({},s);};}());Date._parse=Date.parse;Date.parse=function(s){var r=null;if(!s){return null;}
try{r=Date.Grammar.start.call({},s);}catch(e){return null;}
return((r[1].length===0)?r[0]:null);};Date.getParseFunction=function(fx){var fn=Date.Grammar.formats(fx);return function(s){var r=null;try{r=fn.call({},s);}catch(e){return null;}
return((r[1].length===0)?r[0]:null);};};Date.parseExact=function(s,fx){return Date.getParseFunction(fx)(s);};
