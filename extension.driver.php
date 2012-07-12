<?php

	/**
	 * @package datetime
	 */
	/**
	 * Date and Time Extension
	 */
	Class extension_datetime extends Extension {
	
		private $languages = array(
			'english' => 'en, en_GB.UTF8, en_GB',
			'finnish' => 'fi, fi_FI.UTF8, fi_FI',
			'french' => 'fr, fr_FR.UTF8, fr_FR',
			'german' => 'de, de_DE.UTF8, de_DE',
			'italian' => 'it, it_IT.UTF8, it_IT',
			'norwegian' => 'no, no_NO.UTF8, no_NO',
			'romanian' => 'ro, ro_RO.UTF8, ro_RO',
			'russian' => 'ru, ru_RU.UTF8, ru_RU',
			'portuguese' => 'pt, pt_PT.UTF8, pt_PT'
		);

		/**
		 * @see http://symphony-cms.com/learn/api/2.3/toolkit/extension/#getSubscribedDelegates
		 */
		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => '__addPreferences'
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => '__savePreferences'
				),
			);
		}

		/**
		 * Add site preferences
		 */
		public function __addPreferences($context) {
					
			// Get selected languages
			$selection = Symphony::Configuration()->get('datetime');
			if(empty($selection)) $selection = array();
		
			// Build default options
			$options = array();
			foreach($this->languages as $name => $codes) {
				$options[$name] = array($name . '::' . $codes, (array_key_exists($name, $selection) ? true : false), __(ucfirst($name)));
			}
			
			// Add custom options
			foreach(array_diff_key($selection, $this->languages) as $name => $codes) {
				$options[$name] = array($name . '::' . $codes, true, __(ucfirst($name)));
			}
			
			// Sort options
			ksort($options);			
			
			// Add fieldset
			$group = new XMLElement('fieldset', '<legend>' . __('Date and Time') . '</legend>', array('class' => 'settings'));
			$select = Widget::Select('settings[datetime][]', $options, array('multiple' => 'multiple'));
			$label = Widget::Label(__('Languages included in the Date and Time Data Source'), $select);
			$group->appendChild($label);
			$help = new XMLElement('p', __('You can add more languages in your configuration file.'), array('class' => 'help'));
			$group->appendChild($help);
			$context['wrapper']->appendChild($group);
		}

		/**
		 * Save preferences
		 */
		public function __savePreferences($context) {
	
			// Remove old selection
			Symphony::Configuration()->remove('datetime');	

			// Get selection
			$selection = $context['settings']['datetime'];
			
			// Prepare preferences
			$context['settings']['datetime'] = array();
			if(is_array($selection)) {
				foreach($selection as $language) {
					$settings = explode('::', $language);
					$context['settings']['datetime'][$settings[0]] = $settings[1];
				}
			}
		}
				
		/**
		 * @see http://symphony-cms.com/learn/api/2.3/toolkit/extension/#install
		 */
		public function install() {
			$status = array();

			// Create database field table
			$status[] = Symphony::Database()->query(
				"CREATE TABLE `tbl_fields_datetime` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`prepopulate` tinyint(1) DEFAULT '1',
					`time` tinyint(1) DEFAULT '1',
					`multiple` tinyint(1) DEFAULT '1',
					`range` tinyint(1) DEFAULT '1',
        	  		PRIMARY KEY  (`id`),
			  		KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);

			// Add language strings to configuration			
			Symphony::Configuration()->set('english', $this->languages['english'], 'datetime');
			Administration::instance()->saveConfig();

			// Report status
			if(in_array(false, $status, true)) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.3/toolkit/extension/#update
		 */
		public function update($previousVersion) {
			$status = array();

			// Get table columns
			$columns = Symphony::Database()->fetchCol('Field', "SHOW COLUMNS FROM `tbl_fields_datetime`");

			// Prior version 2.0
			if(version_compare($previousVersion, '2.0', '<')) {

				// Update existing entries
				$fields = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` from `tbl_fields_datetime`");
				foreach($fields as $field) {

					// New database schema
					$status[] = Symphony::Database()->query(
						"ALTER TABLE `tbl_entries_data_$field`
						 MODIFY `start` datetime NOT NULL,
						 MODIFY `end` datetime NOT NULL"
					);

					// Don't allow empty end dates
					$status[] = Symphony::Database()->query(
						"UPDATE `tbl_entries_data_$field`
						 SET `end` = `start`
						 WHERE `end` = 'none'
						 OR `end` = '0000-00-00 00:00'"
					);
				}

				// Remove allow multiple setting
				if(in_array('allow_multiple_dates', $columns)) {
					$status[] = Symphony::Database()->query(
						"ALTER TABLE `tbl_fields_datetime` DROP `allow_multiple_dates`"
					);
				}

				// Add time setting
				if(!in_array('time', $columns)) {
					$status[] = Symphony::Database()->query(
						"ALTER TABLE `tbl_fields_datetime` ADD `time` tinyint(1) DEFAULT '1'"
					);
				}

				// Add range setting
				if(!in_array('range', $columns)) {
					$status[] = Symphony::Database()->query(
						"ALTER TABLE `tbl_fields_datetime` ADD `range` tinyint(1) DEFAULT '1'"
					);
				}

				// Modify prepopulation setting
				$status[] = Symphony::Database()->query(
					"ALTER TABLE `tbl_fields_datetime` MODIFY `prepopulate` tinyint(1) DEFAULT '1'"
				);

				// Correctly store old 'no' values
				$status[] = Symphony::Database()->query(
					"UPDATE tbl_fields_datetime
					 SET `prepopulate` = 0 WHERE `prepopulate` > 1"
				);
			}

			// Prior version 2.4
			if(version_compare($previousVersion, '2.4', '<')) {
				
				// Move language codes to configuration
				Symphony::Configuration()->set('english', $this->languages['english'], 'datetime');
				Symphony::Configuration()->set('german', $this->languages['german'], 'datetime');
				Administration::instance()->saveConfig();
			}
			
			// Prior version 3.0
			if(version_compare($previousVersion, '3.0', '<')) {
			
				// Add multiple setting
				if(!in_array('multiple', $columns)) {
					$status[] = Symphony::Database()->query(
						"ALTER TABLE `tbl_fields_datetime` ADD `multiple` tinyint(1) DEFAULT '1'"
					);
				}
				
				// Transfer old Stage settings
				$constructables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM  `tbl_fields_stage` WHERE  `constructable` = 0");
				if(!empty($constructables) && is_array($constructables)) {
					Symphony::Database()->query("UPDATE `tbl_fields_datetime` SET `multiple` = 0 WHERE `field_id` IN (" . implode(',', $constructables) . ")");
				}
				
				// Remove old Stage instances
				Symphony::Database()->query("DELETE FROM `tbl_fields_stage` WHERE `context` = 'datetime'");
				Symphony::Database()->query("DELETE FROM `tbl_fields_stage_sorting` WHERE `context` = 'datetime'");
			}
			
			// Report status
			if(in_array(false, $status, true)) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.3/toolkit/extension/#uninstall
		 */
		public function uninstall() {

			// Remove old Stage tables if they are empty
			$count = Symphony::Database()->query("SELECT COUNT(*) FROM `tbl_fields_stage`");
			if($count == 0) {
				Symphony::Database()->query("DROP TABLE `tbl_fields_stage`");
				Symphony::Database()->query("DROP TABLE `tbl_fields_stage_sorting`");
			}
			
			// Drop date and time table
			Symphony::Database()->query("DROP TABLE `tbl_fields_datetime`");
			
			// Remove language strings from configuration
			Symphony::Configuration()->remove('datetime');
		}

	}
