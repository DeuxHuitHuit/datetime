<?php

	/**
	 * @package datetime
	 */
	/**
	 * This field provides an interface to manage single or multiple dates as well as date ranges.
	 */
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	if(!class_exists('Stage')) {
		require_once(EXTENSIONS . '/datetime/lib/stage/class.stage.php');
	}
	if(!class_exists('Calendar')) {
		require_once(EXTENSIONS . '/datetime/lib/calendar/class.calendar.php');
	}

	Class fieldDatetime extends Field {
	
		const RANGE = 1;
		const START = 2;
		const END = 3;
		const STRICT = 4;
		const EXTRANGE = 5; // same as RANGE, but end dates can be = to start date
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#__construct
		 */
		function __construct(&$parent) {	
			parent::__construct($parent);
			$this->_name = __('Date/Time');
			$this->_required = true;
			$this->set('location', 'sidebar');
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#canFilter
		 */
		function canFilter() {
			return true;
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#isSortable
		 */
		function isSortable() {
			return true;
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#canPrePopulate
		 */
		function canPrePopulate() {
			return false;
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#allowDatasourceOutputGrouping
		 */
		function allowDatasourceOutputGrouping() {
			return true;
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#allowDatasourceParamOutput
		 */
		function allowDatasourceParamOutput() {
			return true;
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#displaySettingsPanel
		 */
		function displaySettingsPanel(&$wrapper, $errors=NULL) {
	
			// Initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

		/*-----------------------------------------------------------------------*/

			// Behaviour
			$fieldset = Stage::displaySettings(
				$this->get('id'), 
				$this->get('sortorder'), 
				__('Behaviour'),
				array('constructable', 'draggable')
			);
			$group = $fieldset->getChildren();

			// Handle missing settings
			if(!$this->get('id') && $errors == NULL) {
				$this->set('prepopulate', 1);
				$this->set('time', 1);
				$this->set('range', 1);
			}
			
			// Time
			$setting = new XMLElement('label', '<input name="fields[' . $this->get('sortorder') . '][time]" value="yes" type="checkbox"' . ($this->get('time') == 0 ? '' : ' checked="checked"') . '/> ' . __('Allow time editing') . ' <i>' . __('This will display date and time in the interface') . '</i>');
			$group[0]->appendChild($setting);
			
			// Ranges
			$setting = new XMLElement('label', '<input name="fields[' . $this->get('sortorder') . '][range]" value="yes" type="checkbox"' . ($this->get('range') == 0 ? '' : ' checked="checked"') . '/> ' . __('Allow date ranges') . ' <i>' . __('This will enable range editing') . '</i>');
			$group[0]->appendChild($setting);
			
			// Prepopulate
			$setting = new XMLElement('label', '<input name="fields[' . $this->get('sortorder') . '][prepopulate]" value="yes" type="checkbox"' . ($this->get('prepopulate') == 0 ? '' : ' checked="checked"') . '/> ' . __('Pre-populate this field with today\'s date') . ' <i>' . __('This will automatically add the current date to new entries') . '</i>');
			$group[0]->appendChild($setting);
			
			// Append behaviour settings
			$wrapper->appendChild($fieldset);

		/*-----------------------------------------------------------------------*/

			// General
			$fieldset = new XMLElement('fieldset');
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			$this->appendRequiredCheckbox($group);
			$this->appendShowColumnCheckbox($group);
			$fieldset->appendChild($group);
			$wrapper->appendChild($fieldset);
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#commit
		 */
		function commit() {
	
			// Prepare commit
			if(!parent::commit()) return false;
			$id = $this->get('id');
			if($id === false) return false;
	
			// Set up fields
			$fields = array();
			$fields['field_id'] = $id;
			$fields['time'] = ($this->get('time') ? 1 : 0);
			$fields['range'] = ($this->get('range') ? 1 : 0);
			$fields['prepopulate'] = ($this->get('prepopulate') ? 1 : 0);
	
			// Save new stage settings for this field
			$stage = $this->get('stage');
			$stage['destructable'] = 1;
			Stage::saveSettings($this->get('id'), $stage, 'datetime');

			// Delete old field settings
			Symphony::Database()->query(
				"DELETE FROM `tbl_fields_" . $this->handle() . "` WHERE `field_id` = '$id' LIMIT 1"
			);
	
			// Save new field setting
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#displayPublishPanel
		 */
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL) {
		
			// Houston, we have problem: we've been called out of context!
			$callback = Administration::instance()->getPageCallback();
			if($callback['context']['page'] != 'edit' && $callback['context']['page'] != 'new') {
				return;
			}
			
			// Stage
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/lib/stage/stage.publish.js', 101, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/lib/stage/stage.publish.css', 'screen', 102, false);
			
			// Datetime
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/datetime.publish.js', 103, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/assets/datetime.publish.css', 'screen', 104, false);
			
			// Calendar
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/lib/calendar/calendar.publish.css', 'screen', 105, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/lib/calendar/calendar.publish.js', 106, false);
			
			// Timer
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/lib/timer/timer.publish.css', 'screen', 107, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/lib/timer/timer.publish.js', 108, false);
	
			// Help
			$help = '';
			if($this->get('range') == 1) {
				$help = '<i>' . __('Range: <code>shift</code> + click') . '</i>';
			}
	
			// Field label
			$fieldname = 'fields['  .$this->get('element_name') . ']';
			$label = new XMLElement('label', $this->get('label') . $help);
			$wrapper->appendChild($label);
			
			// Get settings
			$settings = array();
			$stage = Stage::getComponents($this->get('id'));
			if(in_array('constructable', $stage)) {
				$settings[] = 'multiple';
			}
			else {
				$settings[] = 'single';
			}
			if($this->get('prepopulate') == 1) {
				$settings[] = 'prepopulate';
			}
			if($this->get('range') == 0) {
				$settings[] = 'simple';
			}
						 
			// Existing dates
			$content = array();
			if(is_array($data)) {
				if(!is_array($data['start'])) $data['start'] = array($data['start']);
				if(!is_array($data['end'])) $data['end'] = array($data['end']);
				
				for($i = 0; $i < count($data['start']); $i++) {
					$content[] = Calendar::createDate($this->get('element_name'), $data['start'][$i], $data['end'][$i], NULL, $this->get('prepopulate'), $this->get('time'));
				}
			}
			
			// Current date and time
			else {
				$content[] = Calendar::createDate($this->get('element_name'), NULL, NULL, NULL, $this->get('prepopulate'), $this->get('time'));
			}
			
			// Add template
			$content[] = Calendar::createDate($this->get('element_name'), NULL, NULL, 'template empty create', $this->get('prepopulate'), $this->get('time'));
		
			// Create stage
			$stage = Stage::create('datetime', $this->get('id'), implode($settings, ' '), $content);
			
			// Append Stage
			if($stage) {
				$wrapper->appendChild($stage);
			}
		}
			
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#processRawFieldData
		 */
		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL) {
			$status = self::__OK__;
			if(!is_array($data)) return NULL;
		
			// Clean up dates
			$result = array('start' => array(), 'end' => array());
			for($i = 0; $i < count($data['start']); $i++) {
				if(!empty($data['start'][$i])) {
					
					// Parse start date
					$parsed = Calendar::formatDate($data['start'][$i], true, 'Y-m-d H:i:s');			
					$result['start'][] = $parsed['date'];
					
					// Empty end date
					if(empty($data['end'][$i])) {
						$result['end'][] = $parsed['date'];
					}
					
					// Specific end date
					else {
						$parsed = Calendar::formatDate($data['end'][$i], true, 'Y-m-d H:i:s');			
						$result['end'][] = $parsed['date'];
					}
				}
			}

			// Result
			if(empty($data['start'][0])) {
				return NULL;
			}
			else {
				return $result;
			}
		}
		
		/**
		 * This function prepares values for import with XMLImporter
		 *
		 * @param string|array $data
		 *	Data that should be prepared for import
		 * @return array
		 *  Return an associative array of start and end dates
		 */		
		function prepareImportValue($data) {
			if(!is_array($data)) $data = array($data);

			// Reformat array
			if(!array_key_exists('start', $data)) {
				$datetime = array();
				
				// Start date
				$datetime['start'] = array($data[0]);
				
				// End date
				if($data[1]) {
					$datetime['end'] = array($data[1]);
				}
				else {
					$datetime['end'] = array($data[0]);
				}

				return $datetime;
			}
			
			return $data;
		}		
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#createTable
		 */
		function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				 `id` int(11) unsigned NOT NULL auto_increment,
				 `entry_id` int(11) unsigned NOT NULL,
				 `start` datetime NOT NULL,
				 `end` datetime NOT NULL,
				 PRIMARY KEY (`id`),
				 KEY `entry_id` (`entry_id`)
				);"
			);
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#prepareTableValue
		 */
		function prepareTableValue($data, XMLElement $link=NULL) {
			if(!is_array($data['start'])) $data['start'] = array($data['start']);
			if(!is_array($data['end'])) $data['end'] = array($data['end']);
			
			// Handle empty dates
			if(empty($data['start'][0])) {
				if($link) {
					$href = $link->getAttribute('href');
					return '<a href="' . $href . '">' . __('No Date') . '</a>';
				}
				else {
					return __('No Date');
				}
			}
			
			// Get schema
			if($this->get('time') == 1) {
				$scheme = __SYM_DATETIME_FORMAT__;
			}
			else {
				$scheme = __SYM_DATE_FORMAT__;
			}
	
			// Parse dates
			$value = array();
			for($i = 0; $i < count($data['start']); $i++) {
				$start = new DateTime($data['start'][$i]);
				$separator = ' &#8211; ';

				// Date range
				if($data['end'][$i] != $data['start'][$i]) {
					$end = new DateTime($data['end'][$i]);
	
					// Different start and end days
					if($start->format('D-M-Y') != $end->format('D-M-Y')) {
						$value[] = LANG::localizeDate($start->format($scheme) . $separator . $end->format($scheme));
					}
					
					// Same day
					else {
					
						// Show time
						if($this->get('time') == 1) {
						
							// Adjust separator
							if(Symphony::Configuration()->get('time_format', 'region') == 'H:i') {
								$separator = '&#8211;';
							}
							
							$value[] = LANG::localizeDate(
								$start->format($scheme) . $separator . $end->format(Symphony::Configuration()->get('time_format', 'region'))
							);
						}
						
						// Hide time
						else {
							$value[] = LANG::localizeDate($start->format($scheme));
						}
					}
				}
				
				// Single date
				else {
					$value[] = LANG::localizeDate($start->format($scheme));
				}
			}
	
			// Link?
			if($link) {
				$href = $link->getAttribute('href');
				return '<a href="' . $href . '">' . implode($value, ', <br />') . '</a>';
			}
			else {
				return implode($value, ', <br />');
			}
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#buildSortingSQL
		 */
		function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC') {
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_" . $this->get('id') . "` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`start` $order");
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#buildDSRetrivalSQL
		 */
		function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			
			// Parse dates
			$dates = array();
			foreach($data as $string) {
				$range = $this->__parseString($string);
				if(!empty($range)) {
					$dates[] = $range;
				}
			}

			// Build filter SQL
			if(!empty($dates)) {
				$this->__buildFilterSQL($dates, $mode, $joins, $where, $andOperation);
			}

			return true;
		}
		
		/**
		 * Build filter sql.
		 *
		 * @param array $dates
		 *	An array of all date ranges that have been set as filters
		 * @param string $mode
		 *	The filtering mode allowing filtering by start date, end date or full date range
		 * @param string $joins
		 *	Tables joins
		 * @param string $where
		 *	Filter statements
		 * @param boolean $andOperation
		 *	Connect filters with 'AND' if true, defaults to false
		 */
		private function __buildFilterSQL($dates, $mode, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
	
			// Get filter connection
			if($andOperation) {
				$connector = ' AND ';
			}
			else {
				$connector = ' OR ';
			}

			// Prepare SQL
			foreach($dates as $range) {
			
				// Filter mode
				switch($range['mode']) {
				
					// Filter by start date
					case self::START:
						$tmp[] = "(`t$field_id`.start BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "')";
						break;
					
					// Filter by end date
					case self::END:
						$tmp[] = "(`t$field_id`.end BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "')";
						break;
					
					// Filter by full date range, start and end have to be in range	
					case self::STRICT:
						$tmp[] = "((`t$field_id`.start BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "') AND
								   (`t$field_id`.end BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "'))";
						break;
					
					// Filter by full date range, start or end have to be in range
					case self::RANGE:
						$tmp[] = "((`t$field_id`.start BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "') OR 
								   (`t$field_id`.end BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "') OR 
								   (`t$field_id`.start < '" . $range['start']->format('Y-m-d H:i:s') . "' AND `t$field_id`.end > '" . $range['end']->format('Y-m-d H:i:s') . "'))";
						break;		

					// Filter by extended date range	
					case self::EXTRANGE:
						$tmp[] = "((`t$field_id`.start BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "') OR 
								   (`t$field_id`.end BETWEEN '" . $range['start']->format('Y-m-d H:i:s') . "' AND '" . $range['end']->format('Y-m-d H:i:s') . "') OR 
								   (`t$field_id`.start < '" . $range['start']->format('Y-m-d H:i:s') . "' AND `t$field_id`.end > '" . $range['end']->format('Y-m-d H:i:s') . "') OR
								   (`t$field_id`.start < '" . $range['start']->format('Y-m-d H:i:s') . "' AND `t$field_id`.end = `t$field_id`.start))";
						break;	
				}
			}
			
			// Build SQL
			$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON `e`.`id` = `t$field_id`.entry_id ";
			$where .= " AND (" . implode($connector, $tmp) . ") ";
		} 
		
		/**
		 * Parse string and create date range to be used for data source filtering.
		 *
		 * @param string $string
		 *	A filter string
		 * @return array
		 *  Returns an array containing the filter range as Datetime objects, 
		 *	if the given string could be parsed
		 */
		private function __parseString($string) {
			$string = trim($string);

			// Filter by start date
			if(strpos($string, 'start:') === 0) {
				$this->__removeModeFromString($string);
				$mode = self::START;
			}
			
			// Filter by end date
			elseif(strpos($string, 'end:') === 0) {
				$this->__removeModeFromString($string);
				$mode = self::END;
			}
			
			// Filter by full range (strict)
			elseif(strpos($string, 'strict:') === 0) {
				$this->__removeModeFromString($string);
				$mode = self::STRICT;
			}
			
			// Remove unsupported regular expressions prefixes in order to support Publish Filtering
			elseif(strpos($string, 'regexp:') === 0) {
				$this->__removeModeFromString($string);
				$mode = self::RANGE;
			}
			
			// Filter by extended range (end date can be null)
			elseif(strpos($string, 'extended:') === 0) {
				$this->__removeModeFromString($string);
				$mode = self::EXTRANGE;
			}
			
			// Filter by full range
			else {
				$mode = self::RANGE;
			}

		/*-----------------------------------------------------------------------*/
		
			// Earlier than
			if(strpos($string, 'earlier than') !== false) {
				$string = substr($string, 13);
				$start = $this->__getDate('1970-01-01');
				$end = $this->__getDate($this->__getEarliestDate($string));
			}
			
			// Later than
			elseif(strpos($string, 'later than') !== false) {
				$string = substr($string, 11);
				$start = $this->__getDate($this->__getLatestDate($string));
				$end = $this->__getDate('2038-01-01');
			}
			
			// Today
			elseif($string == 'today') {
				$start = $this->__getDate('today 00:00');
				$end = $this->__getDate('today 23:59');
			}
			
			// In range
			elseif(strpos($string, ' to ') !== false) {
				$dates = explode(' to ', $string);
				$start = $this->__getDate($this->__getEarliestDate($dates[0]));
				$end = $this->__getDate($this->__getLatestDate($dates[1]));
			}
			
			// Single date
			else {
				$start = $this->__getDate($this->__getEarliestDate($string));
				$end = $this->__getDate($this->__getLatestDate($string));
			}
			
			// Return valid date ranges
			if($start !== NULL && $end !== NULL) {
				return array(
					'start' => $start,
					'end' => $end,
					'mode' => $mode
				);		
			}
		}
		
		/**
		 * Remove filter mode from the first data source filter.
		 *
		 * @param string $string
		 *	Current data source filter
		 */
		private function __removeModeFromString(&$string) {
			$filter = explode(':', $string, 2);
			$string = $filter[1];
		}
		
		/**
		 * Convert string to Datetime object. Log error, if given date is invalid.
		 *
		 * @param string $string
		 *  String to be converted to Datetime object
		 * @return Datetime
		 *	Returns a Datetime object on success or `NULL` on failure
		 */
		private function __getDate($string) {

			// Get date and time object
			try {
				$date = new DateTime(Lang::standardizeDate($string));
			}
			
			// Catch and log invalid dates
			catch(Exception $e) {
				Symphony::$Log->pushToLog(
					'Date and Time could not parse the following date: ' . trim($string) . '. It will be ignored for data source filtering.', 
					E_ERROR, true
				);		
				$date = NULL;
			}
			
			return $date;
		}
		
		/**
		 * Get earliest date.
		 *
		 * @param string $string
		 *	Complete date string to represent the first possible date
		 * @return string
		 *	Returns date string
		 */
		private function __getEarliestDate($string) {
		
			// Only year given
			if(preg_match('/^\d{4}$/i', trim($string))) {
				$string .= '-01-01 00:00';
			}
			
			return $string;
		}
		
		/**
		 * Get latest date.
		 *
		 * @param string $string
		 *	Complete date string to represent the latest possible date
		 * @return string
		 *	Returns date string
		 */
		private function __getLatestDate($string) {
		
			// Find date components
			preg_match('/^(\d{4})[-\/]?(\d{1,2})?[-\/]?(\d{1,2})?\s?(\d{1,2}:\d{2})?$/i', trim($string), $matches);
		
			if(empty($matches)) {
				return $string;
			}

			// No month, day or time given
			else if(!isset($matches[2])) {
				return 'last day of december ' . $string . ' 23:59';
			}
			
			// No day or time give
			elseif(!isset($matches[3])) {
				return 'last day of ' . $string . ' 23:59';
			}

			// No time given
			elseif(!isset($matches[4])) {
				return $string . ' 23:59';
			}
			
			// Time given
			else {
				return $string;
			}
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#groupRecords
		 */
		public function groupRecords($records) {
			if(!is_array($records) || empty($records)) return;
			$groups = array('year' => array());
	
			// Walk through dates
			foreach($records as $entry) {
				$data = $entry->getData($this->get('id'));
				if(!is_array($data['start'])) $data['start'] = array($data['start']);
				if(!is_array($data['end'])) $data['end'] = array($data['end']);
				
				// Create calendar
				for($i = 0; $i < count($data['start']); $i++) {
					$start = new DateTime($data['start'][$i]);
					$end = new DateTime($data['end'][$i]);
					
					// Find matching months
					while($start->format('Y-m-01') <= $end->format('Y-m-01')) {
						$year = $start->format('Y');
						$month = $start->format('n');
						
						// Add entry
						$groups['year'][$year]['attr']['value'] = $year;
						$groups['year'][$year]['groups']['month'][$month]['attr']['value'] = $start->format('m');
						$groups['year'][$year]['groups']['month'][$month]['records'][] = $entry;
						
						// Jump to next month
						$start->modify('+1 month');
					}
				}
			}
	
			// Sort years and months
			ksort($groups['year']);
			foreach($groups['year'] as $year) {
				$current = $year['attr']['value'];
				ksort($groups['year'][$current]['groups']['month']);
			}
	
			// Return calendar groups
			return $groups;
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#appendFormattedElement
		 */
		public function appendFormattedElement(&$wrapper, $data, $encode = false) {
			$datetime = new XMLElement($this->get('element_name'));
	
			// Prepare data
			if(!is_array($data['start'])) $data['start'] = array($data['start']);
			if(!is_array($data['end'])) $data['end'] = array($data['end']);

			// Get timeline
			$timeline = $data['start'];
			sort($timeline);
	
			// Generate XML
			foreach($data['start'] as $id => $date) {
				$date = new XMLElement('date');
				$date->setAttribute('timeline', array_search($data['start'][$id], $timeline) + 1);
				
				// Start date
				$start = new DateTime($data['start'][$id]);
				$date->appendChild(
					$start = new XMLElement(
						'start', 
						$start->format('Y-m-d'), 
						array(
							'iso' => $start->format('c'),
							'time' => $start->format('H:i'),
							'weekday' => $start->format('N'),
							'offset' => $start->format('O')
						)
					)
				);
	
				// Date range
				if($data['end'][$id] != $data['start'][$id]) {
					$end = new DateTime($data['end'][$id]);
					$date->appendChild(
						$end = new XMLElement(
							'end', 
							$end->format('Y-m-d'), 
							array(
								'iso' => $end->format('c'),
								'time' => $end->format('H:i'),
								'weekday' => $end->format('N'),
								'offset' => $end->format('O')
							)
						)
					);
					$date->setAttribute('type', 'range');
				}
				
				// Single date
				else {
					$date->setAttribute('type', 'exact');
				}
				
				$datetime->appendChild($date);
			}
	
			// append date and time to data source
			$wrapper->appendChild($datetime);
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#getParameterPoolValue
		 */
		public function getParameterPoolValue($data) {
			$start = array();
			foreach($data['start'] as $date) {
				$start[] = DateTimeObj::format($date, 'Y-m-d H:i:s');
			}
	
			return implode(',', $start);
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#getExampleFormMarkup
		 */
		public function getExampleFormMarkup() {
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields['.$this->get('element_name').'][start][]'));
			
			return $label;
		}
	}
