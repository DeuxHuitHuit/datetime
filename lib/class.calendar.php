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
		
			// Field name
			$fieldname = 'fields[' . $element . ']';
		
			// Parse start date
			if(isset($start) || $prepopulate == 1) {
				$parsed = self::formatDate($start, $time);		
				$start = $parsed['date'];
				if($parsed['status'] == 'invalid') {
					$start_class = $parsed['status'];
				}			
			}
							
			// Unset empty dates
			if(isset($end)) {
				$parsed = self::formatDate($end, $time);		
				$end = $parsed['date'];		
				if($parsed['status'] == 'invalid') {
					$end_class = $parsed['status'];
				}
				$classes[] = 'range';
			}
			
			// Additional classes
			if($class) {
				$classes[] = $class;
			}
			
			// Create element
			return new XMLElement(
				'li', 
				'<span>
					<span class="dates">
						<input type="text" name="' . $fieldname . '[start][]" value="' . $start . '" class="' . $start_class . '" /><em class="label"></em>
						<input type="text" name="' . $fieldname . '[end][]" value="' . $end . '" class="end ' . $end_class . '" /><em class="end label"></em>
					</span>
				</span>
				<div class="calendar"
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
				</div>', 
				array('class' => implode($classes, ' '))
			);
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
				$scheme = Symphony::Configuration()->get('date_format', 'region');
				if($time == 1) {
					$scheme .= Symphony::Configuration()->get('datetime_separator', 'region') . Symphony::Configuration()->get('time_format', 'region');
				}
			}
			
			// Get current date
			if(empty($date)) {
				$time_string = true;
				$parsed = LANG::localizeDate(date($scheme));
			}
			
			// Parse given date
			else {
				$time_string = strtotime(LANG::standardizeDate($date));
				$parsed = LANG::localizeDate(date($scheme, $time_string));
			}
				
			// Invalid date
			if($time_string === false) {
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
					'date' => $parsed,
					'timestamp' => $time_string
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
	