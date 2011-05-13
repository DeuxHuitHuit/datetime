<?php

	class Calendar {
	
		/**
		 * Create date element.
		 *
		 * @param string $start
		 *  start date
		 * @param string $end
		 *  end date
		 * @param mixed $class
		 *  class names that will be added to the date element
		 * @param int $prepopulate
		 *  if set to 1, prepopulate element with the current date 
		 * @param int $time
		 *  if set to 1, display time
		 * @return XMLElement
		 *  returns a date element
		 */
		public static function createDate($element, $start=NULL, $end=NULL, $class=NULL, $prepopulate=1, $time=1) {
			$classes = array();
					
			// This is hacky: remove empty end dates
			if($end == $start) {
				$end = NULL;
			}
		
			// Range
			if(isset($end)) {
				$classes[] = 'range';
			}
			
			// Additional classes
			if($class) {
				$classes[] = $class;
			}
			
			// Get timer
			if($time == 1) {
				$cutter = '<div class="timer">' .
					self::__createTimeline('start') . 
					self::__createTimeline('end') . 
				'</div>';
			}
			
			// Create element
			return new XMLElement(
				'li', 
				'<span>
					<span class="dates">' . 
						self::__createDateField($element, 'start', $start, $time, $prepopulate) . 
						self::__createDateField($element, 'end', $end, $time) . 
					'</span>
				</span>
				<div class="calendar">' .
					self::__createCalendar() .			
					$cutter .
				'</div>', 
				array('class' => implode($classes, ' '))
			);
		}
		
		/**
		 * Create a date input field containing the given date
		 *
		 * @param string $element
		 *  the Symphony field name
		 * @param string $type
		 *  either 'start' or 'end'
		 * @param string $date
		 *  a date
		 * @param int $time
		 *  display the time, if set to 1; either 1 or 0
		 * @param int $prepopulate
		 *  prepopulate with current date, if set to 1; either 1 or 0
		 * @return string
		 *  returns an input field as string
		 */		
		private static function __createDateField($element, $type, $date, $time, $prepopulate=0) {
		
			// Parse date
			if(isset($date) || $prepopulate) {
				$parsed = self::formatDate($date, $time);
				
				// Generate field
				if($parsed['status'] == 'invalid') {
					$class = 'invalid';
				}
			}
			
			// Generate field
			return '<input type="text" name="fields[' . $element . '][' . $type . '][]" value="' . $parsed['date'] . '" data-timestamp="' . $parsed['timestamp'] . '" class="' . $type . ' ' . $class . '" /><em class="' . $type . ' label"></em>';
		}
		
		private static function __createCalendar() {
			return '<div class="date">
				<strong>
					<span class="month"></span>
					<span class="year"></span>
				</strong>
				<span class="nav">
					<a class="previous">&#171;</a>
					<a class="next">&#187;</a>
				</span>
				<table>
					<thead>
						<tr>
							<td>' . __('Sun') . '</td>
							<td>' . __('Mon') . '</td>
							<td>' . __('Tue') . '</td>
							<td>' . __('Wed') . '</td>
							<td>' . __('Thu') . '</td>
							<td>' . __('Fri') . '</td>
							<td>' . __('Sat') . '</td>
						</tr>
					</thead>
					<tbody>
						<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
						<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
						<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
						<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
						<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
						<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
					</tbody>
				</table>
			</div>';
		}
		
		private static function __createTimeline($type) {
			return '<div class="timeline ' . $type . '">
				<span class="hour1"></span>
				<span class="hour2"></span>
				<span class="hour3"></span>
				<span class="hour4"></span>
				<span class="hour5"></span>
				<span class="hour6"></span>
				<span class="hour7"></span>
				<span class="hour8"></span>
				<span class="hour9"></span>
				<span class="hour10"></span>
				<span class="hour11"></span>
				<span class="hour12"></span>
				<span class="hour13"></span>
				<span class="hour14"></span>
				<span class="hour15"></span>
				<span class="hour16"></span>
				<span class="hour17"></span>
				<span class="hour18"></span>
				<span class="hour19"></span>
				<span class="hour20"></span>
				<span class="hour21"></span>
				<span class="hour22"></span>
				<span class="hour23"></span>
				<div class="range">
					<code>0:00</code>
					<span class="start"></span>
					<span class="active"></span>
					<span class="end"></span>
				</div>
			</div>';
		}
	
		/**
		 * Format date
		 *
		 * @param string $date
		 *  date
		 * @param int $time
		 *  if set to 1, add time
		 * @param string $scheme
		 *  date and time scheme
		 * @param boolean $json
		 *  if set to true, return JSON formatted result
		 * @return 
		 *  returns either an array or JSON object with the status and the parsed value of the given date
		 */
		public static function formatDate($date=NULL, $time=1, $scheme=NULL, $json=false) {
		
			// Get scheme
			if(empty($scheme)) {
				$scheme = __SYM_DATE_FORMAT__;
				if($time == 1) {
					$scheme = __SYM_DATETIME_FORMAT__;
				}
			}

			// Get current time
			if(empty($date)) {
				$timestamp = time();
			}
			
			// Get given time
			elseif(ctype_digit($date)) {

				// Switch between milliseconds and seconds
				if($date > 9999999999) {
					$date = $date / 1000;
				}                    
				$timestamp = $date;
			}
			else {
				$timestamp = $date;
			}

			// Parse date
			$timestamp = DateTimeObj::format($timestamp, 'U');
				
			// Invalid date
			if($timestamp === false) {
				$result = array(
					'status' => 'invalid',
					'date' => $date,
					'timestamp' => false
				);
			}
			
			// Valid date
			else {
				$result = array(
					'status' => 'valid',
					'date' => DateTimeObj::format($timestamp, $scheme, true),
					'timestamp' => number_format($timestamp * 1000, 0, '', '')
				);
			}
				
			// Return result
			if($json) {
				return json_encode($result);
			}
			else {
				return $result;
			}
		}
	
	}
	