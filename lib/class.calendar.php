<?php

	class Calendar {
	
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
				if(strlen($date) > 10) {
					$date = substr($date, 0, -3);
				}
				$timestamp = $date;
			}
			else {
				$timestamp = $date;
			}

			// Parse date
			$timestamp = DateTimeObj::format($timestamp, 'U', false);
				
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
					'date' => DateTimeObj::format($timestamp, $scheme, true, date_default_timezone_get()),
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
	