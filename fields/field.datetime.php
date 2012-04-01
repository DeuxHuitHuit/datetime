<?php

	/**
	 * @package datetime
	 */
	/**
	 * This field provides an interface to manage single or multiple dates as well as date ranges.
	 */
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	if(!class_exists('Calendar')) {
		require_once(EXTENSIONS . '/datetime/lib/class.calendar.php');
	}

	require_once TOOLKIT . '/fields/field.date.php';

	Class fieldDatetime extends Field {

		const RANGE = 1;
		const START = 2;
		const END = 3;
		const STRICT = 4;
		const EXTRANGE = 5; // same as RANGE, but end dates can be = to start date

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#__construct
		 */
		function __construct() {
			parent::__construct();
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
		 * Method that flag the DS to add a DISTINCT keyword when retreiving entries
		 * @see symphony/lib/toolkit/Field::requiresSQLGrouping()
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#requiresSQLGrouping
		 */
		public function requiresSQLGrouping(){
			return true;
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#displaySettingsPanel
		 */
		function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {

			// Initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

		/*-----------------------------------------------------------------------*/

			$columns = new XMLElement('div', null, array('class' => 'two columns'));
			$wrapper->appendChild($columns);

			// Prepopulation
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][prepopulate]', 'yes', 'checkbox');
			if($this->get('prepopulate') == 1) {
				$checkbox->setAttribute('checked', 'checked');
			}
			$setting = new XMLElement('label', __('%s Pre-populate with current date', array($checkbox->generate())), array('class' => 'column'));
			$columns->appendChild($setting);
			
			// Time
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][time]', 'yes', 'checkbox');
			if($this->get('time') == 1) {
				$checkbox->setAttribute('checked', 'checked');
			}
			$setting = new XMLElement('label', __('%s Display time', array($checkbox->generate())), array('class' => 'column'));
			$columns->appendChild($setting);
			
			// Multiple dates
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][multiple]', 'yes', 'checkbox');
			if($this->get('multiple') == 1) {
				$checkbox->setAttribute('checked', 'checked');
			}
			$setting = new XMLElement('label', __('%s Allow multiple dates', array($checkbox->generate())), array('class' => 'column'));
			$columns->appendChild($setting);
			
			// Date ranges
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][range]', 'yes', 'checkbox');
			if($this->get('range') == 1) {
				$checkbox->setAttribute('checked', 'checked');
			}
			$setting = new XMLElement('label', __('%s Enable date ranges', array($checkbox->generate())), array('class' => 'column'));
			$columns->appendChild($setting);

		/*-----------------------------------------------------------------------*/

			// General
			$fieldset = new XMLElement('fieldset');
			$group = new XMLElement('div', NULL, array('class' => 'two columns'));
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
			$fields['prepopulate'] = ($this->get('prepopulate') ? 1 : 0);
			$fields['time'] = ($this->get('time') ? 1 : 0);
			$fields['multiple'] = ($this->get('multiple') ? 1 : 0);
			$fields['range'] = ($this->get('range') ? 1 : 0);

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
		function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null) {

			// Houston, we have problem: we've been called out of context!
			$callback = Administration::instance()->getPageCallback();
			if($callback['context']['page'] != 'edit' && $callback['context']['page'] != 'new') {
				return;
			}

			// Datetime
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/datetime.publish.js', 103, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/assets/datetime.publish.css', 'screen', 104, false);

			// Calendar
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/assets/calendar.publish.css', 'screen', 105, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/calendar.publish.js', 106, false);

			// Timer
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/assets/timer.publish.css', 'screen', 107, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/timer.publish.js', 108, false);

			// Help
			$help = '';
			if($this->get('range') == 1 && $this->get('required') == 'yes') {
				$help = '<i>' . __('Range: <code>shift</code> + click') . '</i>';
			}
			elseif($this->get('range') == 1 && $this->get('required') == 'no') {
				$help = '<i>' . __('Optional') . ', ' . __('range: <code>shift</code> + click') . '</i>';
			}
			elseif($this->get('range') == 0 && $this->get('required') == 'no') {
				$help = '<i>' . __('Optional') . '</i>';
			}

			// Field label
			$fieldname = 'fields['  .$this->get('element_name') . ']';
			$label = new XMLElement('label', $this->get('label') . $help);
			$wrapper->appendChild($label);

			// Get settings
			$settings = array('dark', 'frame');
			if($this->get('multiple') == 1) {
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

			// Create interface
			$duplicator = new XMLElement('div', null, array(
				'class' => implode(' ', $settings)
			));
			$list = new XMLElement('ol', null, array(
				'data-add' => __('Add date'),
				'data-remove' => __('Remove')
			));

			// Existing dates
			if(is_array($data)) {
				if(!is_array($data['start'])) $data['start'] = array($data['start']);
				if(!is_array($data['end'])) $data['end'] = array($data['end']);

				for($i = 0; $i < count($data['start']); $i++) {
					$list->appendChild(
						Calendar::createDate($this->get('element_name'), $data['start'][$i], $data['end'][$i], NULL, $this->get('prepopulate'), $this->get('time'))
					);
				}
			}

			// Current date and time
			else {
				$list->appendChild(
					Calendar::createDate($this->get('element_name'), NULL, NULL, NULL, $this->get('prepopulate'), $this->get('time'))
				);
			}

			// Add template
			$list->appendChild(
				Calendar::createDate($this->get('element_name'), NULL, NULL, 'template', $this->get('prepopulate'), $this->get('time'))
			);

			// Append Duplicator
			$duplicator->appendChild($list);
			if(!is_null($flagWithError)) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($duplicator, $flagWithError));
			}
			else {
				$wrapper->appendChild($duplicator);
			}
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#checkPostFieldData
		 */
		public function checkPostFieldData($data, &$message, $entry_id = null) {
			if($this->get('required') && empty($data['start'][0])) {
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			// At the moment the Date validation is done via AJAX, so we can return __OK__.
			// If a user enters an invalid date and immediately saves (skipping AJAX) then
			// an odd result is returned. Possible TODO here for validating the dates and
			// returning `__INVALID_FIELDS__` if they fail.
			return self::__OK__;
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#processRawFieldData
		 */
		function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=null) {
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
			if(is_array($data[0])) $data = $data[0];

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
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#prepareTableValue
		 */
		function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
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
			$field_id = $this->get('id');

			// If we already have a JOIN to the entry table, don't create another one,
			// this prevents issues where an entry with multiple dates is returned multiple
			// times in the SQL, but is actually the same entry.
			if(!preg_match('/`t' . $field_id . '`/', $joins)) {
				$joins .= "LEFT OUTER JOIN `tbl_entries_data_" . $field_id . "` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
				$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`start` $order");
			}
			else {
				$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`t" . $field_id . "`.`start` $order");
			}
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
				$date = new DateTime(fieldDate::cleanFilterString(Lang::standardizeDate($string)));
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
		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
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
		public function getParameterPoolValue(array $data, $entry_id=NULL) {
			if(!is_array($data['start'])) $data['start'] = array($data['start']);
			if(!is_array($data['end'])) $data['end'] = array($data['end']);

			$values = array();
			for($i = 0; $i < count($data['start']); $i++) {
				$start = $this->__getEarliestDate($data['start'][$i]);
				$end = $this->__getLatestDate($data['end'][$i]);

				// Different dates
				if($start != $end) {
					$values[] = $start . ' to ' . $end;
				}

				// Same date
				else {
					$values[] = $start;
				}
			}

			return $values;
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
