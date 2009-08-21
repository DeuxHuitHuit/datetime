<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class fieldDatetime extends Field {
	
		/**
		 * Initialize Mediathek as unrequired field
		 */

		function __construct(&$parent) {
			parent::__construct($parent);
			$this->_name = __('Date/Time');
			$this->_required = false;
		}
		
		/**
		 * Allow data source filtering
		 */
		
		function canFilter(){
			return false;
		}		
		
		/**
		 * Allow data source parameter output
		 */
		
		function allowDatasourceParamOutput(){
			return true;
		}	

		/**
		 * Displays setting panel in section editor. 
		 * 
		 * @param XMLElement $wrapper - parent element wrapping the field
		 * @param array $errors - array with field errors, $errors['name-of-field-element']
		 */
						
		function displaySettingsPanel(&$wrapper, $errors=NULL) {
		
			// initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);
			$this->appendShowColumnCheckbox($wrapper);
			
			// format
			$label = new XMLElement('label', __('Date format') . '<i>' . __('Use comma to separate date and time') . '</i>');
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][format]', $this->get('format') ? $this->get('format') : 'd MMMM yyyy, HH:mm'));
			$wrapper->appendChild($label);
			
			// prepopulate
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][prepopulate]', 'yes', 'checkbox');
			if($this->get('prepopulate') != 'no') {
				$input->setAttribute('checked', 'checked');	
			}		
			$label->setValue(__('%s Pre-populate this field with today\'s date', array($input->generate())));
			$wrapper->appendChild($label);
			
			// allow multiple
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_dates]', 'yes', 'checkbox');
			if($this->get('allow_multiple_dates') != 'no') {
				$input->setAttribute('checked', 'checked');	
			}		
			$label->setValue(__('%s Allow multiple dates', array($input->generate())));
			$wrapper->appendChild($label);
						
		}

		/**
		 * Checks fields for errors in section editor.
		 * 
		 * @param array $errors
		 * @param boolean $checkForDuplicates
		 */
		
		function checkFields(&$errors, $checkForDuplicates=true) {
		
			parent::checkFields($errors, $checkForDuplicates);
			
		}

		/**
		 * Save fields settings in section editor.
		 */
		
		function commit() {
			
			// prepare commit
			if(!parent::commit()) return false;
			$id = $this->get('id');			
			if($id === false) return false;
			
			// set up fields
			$fields = array();
			$fields['field_id'] = $id;		
			$fields['format'] = $this->get('format');
			if(empty($fields['format'])) $fields['format'] = 'd MMMM yyyy, HH:mm';
			$fields['prepopulate'] = ($this->get('prepopulate') ? $this->get('prepopulate') : 'no');
			$fields['allow_multiple_dates'] = ($this->get('allow_multiple_dates') ? $this->get('allow_multiple_dates') : 'no');
							
			// delete old field settings
			Administration::instance()->Database->query(
				"DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1"
			);
			
			// save new field setting 
			return Administration::instance()->Database->insert($fields, 'tbl_fields_' . $this->handle());

		}

		/**
		 * Displays publish panel in content area.
		 * 
		 * @param XMLElement $wrapper
		 * @param $data
		 * @param $flagWithError
		 * @param $fieldnamePrefix
		 * @param $fieldnamePostfix
		 */
		
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL) {
		
			$this->_engine->Page->addScriptToHead(URL . '/extensions/datetime/assets/jquery-ui.js', 100, true);
			$this->_engine->Page->addScriptToHead(URL . '/extensions/datetime/assets/datetime.js', 200, false);
			$this->_engine->Page->addStylesheetToHead(URL . '/extensions/datetime/assets/datetime.css', 'screen', 201, false);	
			
			// title and help
			$wrapper->setValue($this->get('label') . '<i>' . __('Click <code>alt</code> to add a range') . '</i>');

			// settings
			$fieldname = 'fields['  .$this->get('element_name') . ']';
			$setting = array();
			$setting['DATE'] = __('date');
			$setting['FROM'] = __('from');
			$setting['START'] = __('start');
			$setting['END'] = __('end');
			$setting['FORMAT'] = $this->get('format');
			$setting['multiple'] = $this->get('allow_multiple_dates');
			$setting['prepopulate'] = $this->get('prepopulate');					
			$settings = Widget::Input($fieldname . '[settings]', str_replace('"', "'", json_encode($setting)), 'hidden');

			// default setup			
			if($data == NULL) {
				$label = Widget::Label(NULL, NULL, 'first last');
				$span = new XMLElement('span', '<em>' . __('from') . '</em>', array('class' => 'start'));
				$start = Widget::Input($fieldname . '[start][]', '', 'text');
				$delete = new XMLElement('a', 'delete', array('class' => 'delete'));
				$span->appendChild($start);
				$span->appendChild($delete);
				$label->appendChild($span);
				$span = new XMLElement('span', '<em>' . __('to') . '</em>', array('class' => 'end'));
				$end = Widget::Input($fieldname . '[end][]', '', 'text');
				$span->appendChild($end);
				$label->appendChild($span);
				$label->appendChild($settings);
				$wrapper->appendChild($label);
			}
			else {
				$count = count($data['start']);
				if($count == 1) {
					$data['start'] = array($data['start']);
					$data['end'] = array($data['end']);
				}

				for($i = 1; $i <= $count; $i++) {
					$label = Widget::Label();
					if($i == 1 && $i != $count) { 
						$label->setAttribute('class', 'first');
					}
					elseif($i == 1 && $i == $count) {
						$label->setAttribute('class', 'first last');					
					}
					elseif($i != 1 && $i == $count) {
						$label->setAttribute('class', 'last');
					}
					$span = new XMLElement('span', '<em>' . __('from') . '</em>', array('class' => 'start'));
					$start = Widget::Input($fieldname . '[start][]', $data['start'][$i - 1], 'text');
					$delete = new XMLElement('a', 'delete', array('class' => 'delete'));
					$span->appendChild($start);
					$span->appendChild($delete);
					$label->appendChild($span);
					$span = new XMLElement('span', '<em>' . __('to') . '</em>', array('class' => 'end'));
					$end = Widget::Input($fieldname . '[end][]', $data['end'][$i - 1], 'text');
					$span->appendChild($end);
					$label->appendChild($span);
					if($i == 1) $label->appendChild($settings);
					$wrapper->appendChild($label);
				}
			}	
		
			// add new
			if($this->get('allow_multiple_dates') == 'yes') {
				$new = new XMLElement('a', 'Add new date', array('class' => 'new'));
				$wrapper->appendChild($new);
			}
		
		}
		
 		/**
		 * Prepares field values for database.
		 */
		
		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;
			if(!is_array($data)) return NULL;
			if(empty($data)) return NULL;
		
			$result = array('entry_id' => array(), 'start' => array(), 'end' => array());		
			$count = count($data['start']);
			for($i = 0; $i < $count; $i++) {
				$result['entry_id'][] = $entry_id;
				$result['start'][] = date('c', strtotime($data['start'][$i]));
				$result['end'][] = empty($data['end'][$i]) ? NULL : date('c', strtotime($data['end'][$i]));
			}

			return $result;

		}
		
 		/**
		 * Creates database field table.
		 */
		
		function createTable(){
				
			return Administration::instance()->Database->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`entry_id` int(11) unsigned NOT NULL,
				`start` varchar(80) NOT NULL,
				`end` varchar(80) NOT NULL,
				PRIMARY KEY (`id`),
			  	KEY `entry_id` (`entry_id`)
				);"
			);
			
		}	
		
 		/**
		 * Prepare value for the content overview table.
		 *
		 * @param array $data
		 * @param XMLElement $link
		 */

		function prepareTableValue($data, XMLElement $link=NULL){
		
			$value = '';
			foreach($data['start'] as $id => $date) {
				if(!empty($data['end'][$id])) {
					if($value != '') $value .= ', <br />'; 
					$value .= '<span style="color: rgb(136, 136, 119);">' . __('from') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id])). ' <span style="color: rgb(136, 136, 119);">' .__('to') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['end'][$id]));			
				}
				else {
					if($value != '') $value .= ', <br />'; 
					$value .= DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id]));			
				}
			}
			return $value;
		
		}
		
 		/**
		 * Generate data source output.
		 *
		 * @param XMLElement $wrapper
		 * @param array $data
		 * @param boolean $encode
		 * @param string $mode
		 */
			
		public function appendFormattedElement(&$wrapper, $data, $encode = false) {
				
			// create date and time element
			$datetime = new XMLElement($this->get('element_name'));			

			// get timeline 
			$timeline = $data['start'];
			sort($timeline);

			// generate XML
			foreach($data['start'] as $id => $date) {
				$date = new XMLElement('date');
				$date->setAttribute('timeline', array_search($data['start'][$id], $timeline) + 1);
				$timestamp = strtotime($data['start'][$id]);
				$start = new XMLElement('start', DateTimeObj::get('Y-m-d', $timestamp), array(
						'iso' => $data['start'][$id], 
						'time' => DateTimeObj::get('H:i', $timestamp), 
						'weekday' => DateTimeObj::get('w', $timestamp), 
						'offset' => DateTimeObj::get('O', $timestamp)
					)
				);
				$date->appendChild($start);
				if(!empty($data['end'][$id])) {
					$timestamp = strtotime($data['end'][$id]);
					$end = new XMLElement('end', DateTimeObj::get('Y-m-d', $timestamp), array(
							'iso' => $data['end'][$id], 
							'time' => DateTimeObj::get('H:i', $timestamp), 
							'weekday' => DateTimeObj::get('w', $timestamp), 
							'offset' => DateTimeObj::get('O', $timestamp)
						)
					);
					$date->appendChild($end);
					$date->setAttribute('type', 'range');				
				}
				else {
					$date->setAttribute('type', 'exact');
				}
				$datetime->appendChild($date);
			}
			
			// append date and time to data source
			$wrapper->appendChild($datetime);
			
		}
		
 		/**
		 * Generate parameter pool values.
		 *
		 * @param array $data
		 */
		
		public function getParameterPoolValue($data) {
		
			$start = array();
			foreach($data['start'] as $date) {
				$start[] = DateTimeObj::get('Y-m-d H:i:s', strtotime($date));
			}
		
     		return implode(',', $start);
		
		}
			
 		/**
		 * Sample markup for the event editor!
		 */
		
		public function getExampleFormMarkup(){

			parent::getExampleFormMarkup();

		}

	}
	