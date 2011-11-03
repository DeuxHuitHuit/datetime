<?php

	require_once(TOOLKIT . '/class.datasource.php');
	
	Class datasourceDatetime extends Datasource {
		
		/**
		 * International language codes
		 *
		 * See http://www.php.net/manual/en/function.setlocale.php for further information.
		 * The first string in the array will be used as identifier.
		 */
		public $dsParamLANG = array(
			array('en', 'en_GB'),
			array('de', 'de_DE.UTF8', 'de_DE')
		);
		
		
		/**
		 * About this data source
		 */
		public function about() {
			return array(
				'name' => __('Date and Time'),
				'author' => array(
					'name' => 'Nils Hörrmann',
					'website' => 'http://www.nilshoerrmann.de',
					'email' => 'post@nilshoerrmann.de'),
				'version' => '1.0',
				'release-date' => '2010-04-02T17:59:00+00:00'
			);	
		}
		
		/**
		 * Disallow data source parsing
		 *
		 * Custom data sources should not be parsed by Symphony's data source editor
		 */
		public function allowEditorToParse() {
			return false;
		}
		
		/**
		 * Grab data
		 *
		 * This function generates a list of month and weekday names for each language provided.
		 */
		public function grab(&$param_pool=NULL) {
			$result = new XMLElement('datetime');

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
					$months->appendChild(new XMLElement(
						'month', 
						strftime('%B', $month), 
						array(
							'id' => $count++,
							'abbr' => strftime('%b', $month)
						)
					));
				}
				$lang->appendChild($months);
				
				// Generate weekdays
				$weekdays = new XMLElement('weekdays');
				$count = 1;
				foreach($storage['weekdays'] as $weekday) {
					$weekdays->appendChild(new XMLElement(
						'day', 
						strftime('%A', $weekday), 
						array(
							'id' => $count++,
							'abbr' => strftime('%a', $weekday)
						)
					));
				}
				$lang->appendChild($weekdays);

				// Append Result
				$result->appendChild($lang);
			}

			return $result;
		}
		
	}
