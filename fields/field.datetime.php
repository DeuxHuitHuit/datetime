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
	
		const SIMPLE = 0;
		const REGEXP = 1;
		const RANGE = 3;
		const ERROR = 4;

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#__construct
		 */
		function __construct(&$parent) {	
			parent::__construct($parent);
			$this->_name = __('Date/Time');
			$this->_required = true;
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
			}
			
			// Time
			$setting = new XMLElement('label', '<input name="fields[' . $this->get('sortorder') . '][time]" value="yes" type="checkbox"' . ($this->get('time') == 0 ? '' : ' checked="checked"') . '/> ' . __('Allow time editing') . ' <i>' . __('This will display date and time in the interface') . '</i>');
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
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#checkFields
		 */
		function checkFields(&$errors, $checkForDuplicates=true) {
			parent::checkFields($errors, $checkForDuplicates);
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
	
			// Field label
			$fieldname = 'fields['  .$this->get('element_name') . ']';
			$label = new XMLElement('label', $this->get('label') . '<i>' . __('Hold <code>shift</code> and click to add a date range') . '</i>');
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
					
					// Parse end date
					if(!empty($data['end'][$i])) {
						$parsed = Calendar::formatDate($data['end'][$i], true, 'Y-m-d H:i:s');			
						$result['end'][] = $parsed['date'];
					}
					else {
					
						// This is hacky: store empty end dates
						$result['end'][] = 'none';
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
			if(!is_array($data)) array($data);
			
			// Create start and end dates
		    return array(
		        'start' => $data[0],
		        'end' => (isset($data[1]) ? $data[1] : NULL)
		    );
		}		
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#createTable
		 */
		function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`entry_id` int(11) unsigned NOT NULL,
				`start` varchar(255) NOT NULL,
				`end` varchar(255) NULL,
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
	
			// Parse dates
			$value = array();
			foreach($data['start'] as $id => $date) {
				if(empty($date)) continue;			
				$start = Calendar::formatDate($data['start'][$id], $this->get('time'));
				$separator = ' &#8211; ';

				// Date range
				if(!empty($data['end'][$id]) && $data['end'][$id] != 'none') {
					$start_day = Calendar::formatDate($data['start'][$id], false, 'D-M-Y');
					$end_day = Calendar::formatDate($data['end'][$id], false, 'D-M-Y');
					$end = Calendar::formatDate($data['end'][$id], $this->get('time'));
	
					// Different start and end days
					if($start_day['date'] != $end_day['date']) {
						$value[] = $start['date'] . $separator . $end['date'];
					}
					
					// Same day
					else {
					
						// Show time
						if($this->get('time') == 1) {
						
							// Adjust separator
							if(Symphony::Configuration()->get('time_format', 'region') == 'H:i') {
								$separator = '&#8211;';
							}
							
							$end_time = LANG::localizeDate(date(Symphony::Configuration()->get('time_format', 'region'), strtotime($data['end']	[$id])));
							$value[] = $start['date'] . $separator . $end_time;
						}
						
						// Hide time
						else {
							$value[] = $start['date'];
						}
					}
				}
				
				// Single date
				else {
					$value[] = $start['date'];
				}
			}
	
			return implode($value, '<br />');
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#buildSortingSQL
		 */
		function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC') {
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`start` $order");
		}
	
		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#buildDSRetrivalSQL
		 */
		function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			if(self::isFilterRegex($data[0])) {
				$field_id = $this->get('id');
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`sym_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.start REGEXP `{$pattern}` OR t{$field_id}_{$this->_key}.end REGEXP `{$pattern}`
				";
				return true;
			}
	
			$parsed = array();
	
			foreach($data as $string) {
				$type = self::__parseFilter($string);
				if($type == self::ERROR) return false;
				if(!is_array($parsed[$type])) $parsed[$type] = array();
				$parsed[$type][] = $string;
			}
	
			foreach($parsed as $type => $value) {
				switch($type) {
					case self::RANGE:
						if(!empty($value)) $this->__buildRangeFilterSQL($value, $joins, $where, $andOperation);
						break;
	
					case self::SIMPLE:
						if(!empty($value)) $this->__buildSimpleFilterSQL($value, $joins, $where, $andOperation);
						break;
				}
			}
	
			return true;
		}
	
		/**
		 * Build sql for single dates.
		 *
		 * @param array $data
		 * @param string $joins
		 * @param string $where
		 * @param boolean $andOperation
		 */
		protected function __buildSimpleFilterSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
	
			$connector = ' OR '; // filter separated with commas
			if($andOperation == 1) $connector = ' AND '; // filter conntected with plus signs
	
			foreach($data as $date) {
				$tmp[] = "'" . DateTimeObj::get('Y-m-d H:i:s', strtotime($date)) . "' BETWEEN
					DATE_FORMAT(`t$field_id".$this->key."`.start, '%Y-%m-%d %H:%i:%s') AND
					DATE_FORMAT(`t$field_id".$this->key."`.end, '%Y-%m-%d %H:%i:%s')";
			}
			$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
			$where .= " AND (".implode($connector, $tmp).") ";
			$this->key++;
		}
	
		/**
		 * Build sql for dates ranges.
		 *
		 * @param array $data
		 * @param string $joins
		 * @param string $where
		 * @param boolean $andOperation
		 */
		protected function __buildRangeFilterSQL($data, &$joins, &$where, $andOperation=false) {
			$field_id = $this->get('id');
	
			$connector = ' OR '; // filter separated with commas
			if($andOperation == 1) $connector = ' AND '; // filter conntected with plus signs
	
			foreach($data as $date) {
				$tmp[] = "(DATE_FORMAT(`t$field_id".$this->key."`.start, '%Y-%m-%d %H:%i:%s') BETWEEN
					'" . DateTimeObj::get('Y-m-d H:i:s', strtotime($date['start'])) . "' AND
					'" . DateTimeObj::get('Y-m-d H:i:s', strtotime($date['end'])) . "' OR
					DATE_FORMAT(`t$field_id".$this->key."`.end, '%Y-%m-%d %H:%i:%s') BETWEEN
					'" . DateTimeObj::get('Y-m-d H:i:s', strtotime($date['start'])) . "' AND
					'" . DateTimeObj::get('Y-m-d H:i:s', strtotime($date['end'])) . "')";
			}
			$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
			$where .= " AND (".implode($connector, $tmp).") ";
			$this->key++;
		}
	
		/**
		 * Clean up date string.
		 * This function is a copy from the core date field.
		 *
		 * @param string $string
		 */
		protected static function __cleanFilterString($string) {
			$string = trim($string);
			$string = trim($string, '-/');
			return urldecode($string);
		}
	
		/**
		 * Parse filter string for shorthand dates and ranges.
		 * This function is a copy from the core date field.
		 *
		 * @param string $string
		 */
		protected static function __parseFilter(&$string) {
			$string = self::__cleanFilterString($string);
	
			// Check its not a regexp
			if(preg_match('/^regexp:/i', $string)) {
				$string = str_replace('regexp:', '', $string);
				return self::REGEXP;
			}
	
			// Look to see if its a shorthand date (year only), and convert to full date
			elseif(preg_match('/^(1|2)\d{3}$/i', $string)) {
				$string = "$string-01-01 to $string-12-31";
			}
	
			elseif(preg_match('/^(earlier|later) than (.*)$/i', $string, $match)) {
	
				$string = $match[2];
	
				if(!self::__isValidDateString($string)) return self::ERROR;
	
				$time = strtotime($string);
	
				switch($match[1]){
					case 'later': $string = DateTimeObj::get('Y-m-d H:i:s', $time+1) . ' to 2038-01-01'; break;
					case 'earlier': $string = '1970-01-03 to ' . DateTimeObj::get('Y-m-d H:i:s', $time-1); break;
				}
	
			}
	
			// Look to see if its a shorthand date (year and month), and convert to full date
			elseif(preg_match('/^(1|2)\d{3}[-\/]\d{1,2}$/i', $string)) {
	
				$start = "$string-01";
	
				if(!self::__isValidDateString($start)) return self::ERROR;
	
				$string = "$start to $string-" . date('t', strtotime($start));
			}
	
			// Match for a simple date (Y-m-d), check its ok using checkdate() and go no further
			elseif(!preg_match('/\s+to\s+/i', $string)) {
	
				if(!self::__isValidDateString($string)) return self::ERROR;
	
				$string = DateTimeObj::get('Y-m-d H:i:s', strtotime($string));
				return self::SIMPLE;
	
			}
	
			//	A date range, check it's ok!
			elseif(preg_match('/\s+to\s+/i', $string)) {
	
				if(!$parts = preg_split('/\s+to\s+/', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;
	
				foreach($parts as $i => &$part) {
					if(!self::__isValidDateString($part)) return self::ERROR;
	
					$part = DateTimeObj::get('Y-m-d H:i:s', strtotime($part));
				}
	
				$string = "$parts[0] to $parts[1]";
			}
	
			// Parse the full date range and return an array
			if(!$parts = preg_split('/\s+to\s+/', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;
	
			$parts = array_map(array('self', '__cleanFilterString'), $parts);
	
			list($start, $end) = $parts;
	
			if(!self::__isValidDateString($start) || !self::__isValidDateString($end)) return self::ERROR;
	
			$string = array('start' => $start, 'end' => $end);
	
			return self::RANGE;
		}
	
		/**
		 * Validate date.
		 * This function is a copy from the core date field.
		 *
		 * @param string $string
		 */
		protected static function __isValidDateString($string) {
			$string = trim($string);
	
			if(empty($string)) return false;
	
			// Its not a valid date, so just return it as is
			if(!$info = getdate(strtotime($string))) return false;
			elseif(!checkdate($info['mon'], $info['mday'], $info['year'])) return false;
	
			return true;
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
				foreach($data['start'] as $id => $start) {
					$start = date('Y-m-01', strtotime($start));
					if($data['end'][$id] == NULL) $data['end'][$id] = $start;
					$end = date('Y-m-01', strtotime($data['end'][$id]));
					$starttime = strtotime($start);
					$endtime = strtotime($end);
					
					// Find matching months
					while($starttime <= $endtime) {
						$year = date('Y', $starttime);
						$month[1] = date('n', $starttime);
						$month[2] = date('m', $starttime);
						
						// Add entry
						$groups['year'][$year]['attr']['value'] = $year;
						$groups['year'][$year]['groups']['month'][$month[1]]['attr']['value'] = $month[2];
						$groups['year'][$year]['groups']['month'][$month[1]]['records'][] = $entry;
						
						// Jump to next month
						$starttime = strtotime(date('Y-m-01', $starttime) . ' +1 month');
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
	
			// Get timeline
			if(!is_array($data['start'])) $data['start'] = array($data['start']);
			if(!is_array($data['end'])) $data['end'] = array($data['end']);
			$timeline = $data['start'];
			sort($timeline);
	
			// Generate XML
			foreach($data['start'] as $id => $date) {
				if(empty($date)) continue;
				$date = new XMLElement('date');
				$date->setAttribute('timeline', array_search($data['start'][$id], $timeline) + 1);
				
				// Start date
				$timestamp = strtotime($data['start'][$id]);
				$parsed = Calendar::formatDate($data['start'][$id], false, 'Y-m-d');
				$date->appendChild(
					$start = new XMLElement('start', $parsed['date'], array(
							'iso' => DateTimeObj::get('c', $timestamp),
							'time' => DateTimeObj::get('H:i', $timestamp),
							'weekday' => DateTimeObj::get('N', $timestamp),
							'offset' => DateTimeObj::get('O', $timestamp),
							'status' => $parsed['status']
						)
					)
				);
	
				// Date range
				if(!empty($data['end'][$id]) && $data['end'][$id] != 'none') {
					$timestamp = strtotime($data['end'][$id]);
					$parsed = Calendar::formatDate($data['end'][$id], false, 'Y-m-d');
					$date->appendChild(
						$end = new XMLElement('end', $parsed['date'], array(
								'iso' => DateTimeObj::get('c', $timestamp),
								'time' => DateTimeObj::get('H:i', $timestamp),
								'weekday' => DateTimeObj::get('N', $timestamp),
								'offset' => DateTimeObj::get('O', $timestamp),
								'status' => $parsed['status']
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
				$start[] = DateTimeObj::get('Y-m-d H:i:s', strtotime($date));
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
