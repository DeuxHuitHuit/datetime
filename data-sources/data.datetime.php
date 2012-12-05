<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourceDatetime extends Datasource {
		
		/**
		 * International language codes
		 *
		 * Please define the included languages in your configuration
		 */
		public $dsParamLANG = array();
		
		/**
		 * Initialise data source
		 */
		public function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
			$this->dsParamLANG = array();
			
			// Load language codes from configuration
			$languages = Symphony::Configuration()->get('datetime');
			if(is_array($languages)) {
				foreach($languages as $name => $codes) {
					$this->dsParamLANG[] = explode(', ', $codes);
				}
			}
		}
				
		/**
		 * About this data source
		 */
		public function about() {
			return array(
				'name' => __('Date and Time'),
				'author' => array(
					'name' => 'Büro für Web- und Textgestaltung',
					'website' => 'http://hananils.de'
				),
				'version' => '2.0',
				'release-date' => '2012-02-07'
			);	
		}
		
		/**
		 * Disallow data source parsing
		 */
		public function allowEditorToParse() {
			return false;
		}
		
		/**
		 * This function generates a list of month and weekday names for each language provided.
		 */
		public function grab(&$param_pool=NULL) {
			$result = new XMLElement('datetime');
			
			// No language specified
			if(empty($this->dsParamLANG)) {
				$empty = new XMLElement('error', __('No language specified. Please select one or more in the system preferences.'));
				$result->appendChild($empty);
				return $result;
			}

			// Date
			$date = new DateTime('1st January');
			$storage = array();
			
			// Months
			$storage['months'] = array();
			for($i = 1; $i <= 12; $i++) {
				$storage['months'][] = $date->getTimestamp();
				$date->modify('+1 month');
			}
			
			// Weekdays
			$storage['weekdays'] = array();
			$date->modify('last Sunday');
			for($i = 1; $i <= 7; $i++) {
				$storage['weekdays'][] = $date->getTimestamp();
				$date->modify('+1 day');
			}
			
			// Loop through languages
			foreach($this->dsParamLANG as $code) {
				
				// Setup current langauge
				$lang = new XMLElement('language', NULL, array('id' => $code[0]));
				setlocale(LC_TIME, $code);

				// Generate months
				$months = new XMLElement('months');
				$count = 1;
				foreach($storage['months'] as $month) {
					$month_name = $this->convertIfWindows(strftime('%B', $month));
					$month_abbr = $this->convertIfWindows(strftime('%b', $month));
					$months->appendChild(new XMLElement(
						'month', 
						$month_name, 
						array(
							'id' => $count++,
							'abbr' => $month_abbr
						)
					));
				}
				$lang->appendChild($months);
				
				// Generate weekdays
				$weekdays = new XMLElement('weekdays');
				$count = 1;
				foreach($storage['weekdays'] as $weekday) {
					$weekday_name = $this->convertIfWindows(strftime('%A', $weekday));
					$weekday_abbr = $this->convertIfWindows(strftime('%a', $weekday));
					$weekdays->appendChild(new XMLElement(
						'day', 
						$weekday_name, 
						array(
							'id' => $count++,
							'abbr' => $weekday_abbr
						)
					));
				}
				$lang->appendChild($weekdays);

				// Append Result
				$result->appendChild($lang);
			}

			return $result;
		}

		private function convertIfWindows($str) {
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				return iconv('ISO-8859-1', 'UTF-8', $str);
			} else {
				return $str;
			}
		}
		
	}
