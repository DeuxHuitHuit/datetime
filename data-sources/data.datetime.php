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
			
			// Loop through languages
			foreach($this->dsParamLANG as $code) {
				
				// Setup current langauge
				$lang = new XMLElement('language', NULL, array('id' => $code[0]));
				setlocale(LC_TIME, $code);

				// Generate months
				$months = new XMLElement('months');
				for($i = 1; $i <= 12; $i++) {
					$time = mktime(0, 0, 0, $i);
					$month = new XMLElement(
						'month', 
						strftime('%B', $time), 
						array(
							'id' => $i,
							'abbr' => strftime('%b', $time)
						)
					);
					$months->appendChild($month);
				}
				$lang->appendChild($months);

				// Generate weekdays
				$weekdays = new XMLElement('weekdays');
				for($i = 0; $i <= 6; $i++) {
					$time = strtotime('+' . $i . ' day', strtotime('last Sunday'));
					$day = new XMLElement(
						'day', 
						strftime('%A', $time), 
						array(
							'id' => $i,
							'abbr' => strftime('%a', $time)
						)
					);
					$weekdays->appendChild($day);
				}
				$lang->appendChild($weekdays);

				// Append Result
				$result->appendChild($lang);
				
			}

			return $result;
		}
		
	}
