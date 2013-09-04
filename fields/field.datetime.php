<?php

	/**
	 * @package datetime
	 */
	/**
	 * This field provides an interface to manage single or multiple dates as well as date ranges.
	 */
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once TOOLKIT . '/fields/field.date.php';
	require_once(EXTENSIONS . '/datetime/lib/class.calendar.php');

	Class fieldDatetime extends fieldDate {

		const SIMPLE = 0;
		const REGEXP = 1;

		const RANGE = 10;
		const START = 11;
		const END = 12;
		const STRICT = 13;
		const EXTRANGE = 14;

		const ERROR = 20;

		function __construct() {
			parent::__construct();
			$this->_name = __('Date/Time');
			$this->_required = true;
			$this->set('location', 'sidebar');
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		function canPrePopulate() {
			return false;
		}

		/**
		 * Method that flag the DS to add a DISTINCT keyword when retreiving entries
		 * @see symphony/lib/toolkit/Field::requiresSQLGrouping()
		 * @see http://symphony-cms.com/learn/api/2.3/toolkit/field/#requiresSQLGrouping
		 */
		public function requiresSQLGrouping(){
			return true;
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

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

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

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
				'<header>
					<div>' .
						self::__createDateField($element, 'start', $start, $time, $prepopulate) .
						self::__createDateField($element, 'end', $end, $time) .
				'	</div>
				</header>
				<div class="calendar content">' .
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
				$parsed = Calendar::formatDate($date, $time);

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
				<nav>
					<a class="previous">&#171;</a>
					<div class="switch">
						<ul class="months"></ul>
						<ul class="years"></ul>
					</div>
					<a class="next">&#187;</a>
				</nav>
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
		 * Get filtering mode from string.
		 *
		 * @param string $string
		 *	A filter string
		 * @return string
		 *  Returns the filter mode
		 */
		private function __getModeFromString(&$string) {
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

			return $mode;
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
		 * Build range filter sql.
		 *
		 * @param array $data
		 *	An array of all date ranges that have been set as filters
		 * @param string $joins
		 *	Tables joins
		 * @param string $where
		 *	Filter statements
		 * @param boolean $andOperation
		 *	Connect filters with 'AND' if true, defaults to false
		 */
		public function buildRangeFilterSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			// Get filter connection
			if($andOperation) {
				$connector = ' AND ';
			}
			else {
				$connector = ' OR ';
			}

			// Prepare SQL
			foreach($data as $range) {

				// Filter mode
				switch($range['mode']) {

					// Filter by start date
					case self::START:
						$tmp[] = "(`t$field_id`.start BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "')";
						break;

					// Filter by end date
					case self::END:
						$tmp[] = "(`t$field_id`.end BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "')";
						break;

					// Filter by full date range, start and end have to be in range
					case self::STRICT:
						$tmp[] = "((`t$field_id`.start BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "') AND
								(`t$field_id`.end BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "'))";
						break;

					// Filter by full date range, start or end have to be in range
					case self::RANGE:
						$tmp[] = "((`t$field_id`.start BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "') OR
								(`t$field_id`.end BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "') OR
								(`t$field_id`.start < '" . $range['start'] . "' AND `t$field_id`.end > '" . $range['end'] . "'))";
						break;

					// Filter by extended date range
					case self::EXTRANGE:
						$tmp[] = "((`t$field_id`.start BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "') OR
								(`t$field_id`.end BETWEEN '" . $range['start'] . "' AND '" . $range['end'] . "') OR
								(`t$field_id`.start < '" . $range['start'] . "' AND `t$field_id`.end > '" . $range['end'] . "') OR
								(`t$field_id`.start < '" . $range['start'] . "' AND `t$field_id`.end = `t$field_id`.start))";
						break;
				}
			}

			// Build SQL
			$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON `e`.`id` = `t$field_id`.entry_id ";
			$where .= " AND (" . implode($connector, $tmp) . ") ";
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {

			// Initialize field settings based on class defaults (name, placement)
			field::displaySettingsPanel($wrapper, $errors);

		/*-----------------------------------------------------------------------*/

			$columns = new XMLElement('div', null, array('class' => 'two columns'));
			$wrapper->appendChild($columns);

			// Prepopulation
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][prepopulate]', 'yes', 'checkbox');
			if((int)$this->get('prepopulate') === 1) {
				$checkbox->setAttribute('checked', 'checked');
			}
			$setting = new XMLElement('label', __('%s Pre-populate with current date', array($checkbox->generate())), array('class' => 'column'));
			$columns->appendChild($setting);

			// Time
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][time]', 'yes', 'checkbox');
			if((int)$this->get('time') === 1) {
				$checkbox->setAttribute('checked', 'checked');
			}
			$setting = new XMLElement('label', __('%s Display time', array($checkbox->generate())), array('class' => 'column'));
			$columns->appendChild($setting);

			// Multiple dates
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][multiple]', 'yes', 'checkbox');
			if((int)$this->get('multiple') === 1) {
				$checkbox->setAttribute('checked', 'checked');
			}
			$setting = new XMLElement('label', __('%s Allow multiple dates', array($checkbox->generate())), array('class' => 'column'));
			$columns->appendChild($setting);

			// Date ranges
			$checkbox = Widget::Input('fields[' . $this->get('sortorder') . '][range]', 'yes', 'checkbox');
			if((int)$this->get('range') === 1) {
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

		function commit() {

			// Prepare commit
			if(!field::commit()) return false;
			$id = $this->get('id');
			if($id === false) return false;

			// Set up fields
			$fields = array();
			$fields['field_id'] = $id;
			$fields['prepopulate'] = ($this->get('prepopulate') ? 1 : 0);
			$fields['time'] = ($this->get('time') ? 1 : 0);
			$fields['multiple'] = ($this->get('multiple') ? 1 : 0);
			$fields['range'] = ($this->get('range') ? 1 : 0);

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null) {

			// Houston, we have problem: we've been called out of context!
			if( !Symphony::Engine() instanceof Administration ){
				return;
			}

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

			// Field label
			$fieldname = 'fields['  .$this->get('element_name') . ']';
			$label = new XMLElement('label', $this->get('label') . '<i>' . ($this->get('required') == 'no' ? __('Optional') : '') . '</i>');
			$wrapper->appendChild($label);

			// Get settings
			$settings = array('dark', 'frame');
			if((int)$this->get('multiple') === 1) {
				$settings[] = 'multiple';
			}
			else {
				$settings[] = 'single';
			}
			if((int)$this->get('prepopulate') === 1) {
				$settings[] = 'prepopulate';
			}
			if((int)$this->get('range') === 0) {
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
						self::createDate($this->get('element_name'), $data['start'][$i], $data['end'][$i], NULL, $this->get('prepopulate'), $this->get('time'))
					);
				}
			}

			// Current date and time
			elseif((int)$this->get('prepopulate') === 1 || (int)$this->get('multiple') === 0) {
				$list->appendChild(
					self::createDate($this->get('element_name'), NULL, NULL, NULL, $this->get('prepopulate'), $this->get('time'))
				);
			}

			// Add template
			if((int)$this->get('multiple') === 1) {
				$template = self::createDate($this->get('element_name'), NULL, NULL, 'template', $this->get('prepopulate'), $this->get('time'));
				$template->setAttribute('data-name', 'datetime');
				$template->setAttribute('data-type', 'datetime');
				$list->appendChild($template);
			}

			// Append Duplicator
			$duplicator->appendChild($list);
			if(!is_null($flagWithError)) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($duplicator, $flagWithError));
			}
			else {
				$wrapper->appendChild($duplicator);
			}
		}


		public function checkPostFieldData($data, &$message, $entry_id = null) {
			if($this->get('required') === 'yes' && empty($data['start'][0])) {
				$message = __("‘%s’ is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			// @todo validate all dates and flag errors
			return self::__OK__;
		}

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

	/*-------------------------------------------------------------------------
		Events:
	-------------------------------------------------------------------------*/

		public function getExampleFormMarkup() {
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields['.$this->get('element_name').'][start][]'));
			$label->appendChild(Widget::Input('fields['.$this->get('element_name').'][end][]'));

			return $label;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

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
							'timestamp' => $start->getTimestamp(),
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
								'timestamp' => $end->getTimestamp(),
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
			if(!empty($data['start'][0])) {
				$wrapper->appendChild($datetime);
			}
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
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
			if((int)$this->get('time') === 1) {
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
						if((int)$this->get('time') === 1) {

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

		public function getParameterPoolValue(array $data, $entry_id=NULL) {
			return $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::VALUE, $entry_id);
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {

			// Parse dates
			$dates = array();
			foreach($data as $range) {
				$mode = $this->__getModeFromString($range);
				$result = self::parseFilter($range);

				if($result !== FieldDate::ERROR && !empty($range)) {
					$range['mode'] = $mode;
					$dates[] = $range;
				}
			}

			// Build filter SQL
			if(!empty($dates)) {
				$this->buildRangeFilterSQL($dates, $joins, $where, $andOperation);
			}

			return true;
		}

	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/

		function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC'){
			$field_id = $this->get( 'id' );
			$order    = strtolower( $order );
			
			// If we already have a JOIN to the entry table, don't create another one,
			// this prevents issues where an entry with multiple dates is returned multiple
			// times in the SQL, but is actually the same entry.
			if( !preg_match( '/`t'.$field_id.'`/', $joins ) ){
				$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$field_id."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
				$sort = 'ORDER BY ';
				
				if( in_array( $order, array('random', 'rand') ) ){
				    $sort .= 'RAND()';
				}
				elseif( $order === 'asc' ){
				    $sort .= "`ed`.`start`, `ed`.`end` $order";
				}
				else{
				    $sort .= "`ed`.`start` $order";
				}
			}
			else{
				$sort = 'ORDER BY ';
				
				if( in_array( $order, array('random', 'rand') ) ){
				    $sort .= 'RAND()';
				}
				elseif( $order === 'asc' ){
				    $sort .= "`t$field_id`.`start`, `t$field_id`.`end` $order";
				}
				else{
				    $sort .= "`t$field_id`.`start` $order";
				}
			}
		}

	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/

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

	/*-------------------------------------------------------------------------
		Importing:
	-------------------------------------------------------------------------*/

		public function getImportModes() {
			return array(
				'getPostdata' =>	ImportableField::ARRAY_VALUE,
				'getString' =>		ImportableField::STRING_VALUE
			);
		}

		/**
		 * This function prepares values for import with XMLImporter
		 *
		 * @param string|array $data
		 *  Data that should be prepared for import
		 * @param integer $entry_id
		 *  The current entry_id, if it exists, otherwise null.
		 * @return array
		 *  Return an associative array of start and end dates
		 */
		public function prepareImportValue($data, $mode, $entry_id = null) {
			$message = $status = null;
			$modes = (object)$this->getImportModes();

			if($mode === $modes->getPostdata) {
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

					$data = $datetime;
				}

				return $this->processRawFieldData($data, $status, $message, true, $entry_id);
			}
			else if($mode === $modes->getString) {
				$dates = explode(',', $data);
				$data = array();
				foreach($dates as $date_string) {
					self::parseFilter($date_string);

					$data['start'][] = $date_string['start'];
					$data['end'][] = $date_string['end'];
				}

				return $this->processRawFieldData($data, $status, $message, true, $entry_id);
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Export:
	-------------------------------------------------------------------------*/

		/**
		 * Return a list of supported export modes for use with `prepareExportValue`.
		 *
		 * @return array
		 */
		public function getExportModes() {
			return array(
				'listDateObject' =>		ExportableField::LIST_OF + ExportableField::OBJECT,
				'listDateValue'  =>		ExportableField::LIST_OF + ExportableField::VALUE,
				'getPostdata'	 =>		ExportableField::POSTDATA
			);
		}

		/**
		 * Give the field some data and ask it to return a value using one of many
		 * possible modes.
		 *
		 * @param mixed $data
		 * @param integer $mode
		 * @param integer $entry_id
		 * @return DateTime|null
		 */
		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();
			$dates = array();

			if(!is_array($data['start'])) $data['start'] = array($data['start']);
			if(!is_array($data['end'])) $data['end'] = array($data['end']);

			if ($mode === $modes->listDateObject) {
				$timezone = Symphony::Configuration()->get('timezone', 'region');

				for($i = 0; $i < count($data['start']); $i++) {
					$start = new DateTime($data['start'][$i]);
					$end = new DateTime($data['end'][$i]);

					$start->setTimezone(new DateTimeZone($timezone));
					$end->setTimezone(new DateTimeZone($timezone));

					$dates[] = array(
						'start' => $start,
						'end' => $end
					);
				}

				return $dates;
			}

			else if ($mode === $modes->listDateValue) {
				for($i = 0; $i < count($data['start']); $i++) {
					$start = new DateTime($data['start'][$i]);
					$end = new DateTime($data['end'][$i]);

					// Different dates
					if($start != $end) {
						$dates[] = $start->format('Y-m-d H:i:s') . ' to ' . $end->format('Y-m-d H:i:s');
					}

					// Same date
					else {
						$dates[] = $start->format('Y-m-d H:i:s');
					}
				}

				return $dates;
			}

			else if ($mode === $modes->getPostdata) {
				return $data;
			}

			return null;
		}

	}
