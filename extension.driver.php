<?php

	/**
	 * @package datetime
	 */
	/**
	 * Date and Time Extension
	 */
	Class extension_datetime extends Extension {

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#__construct
		 */
		public function __construct(Array $args){
			parent::__construct($args);

			// Include Stage
			if(!class_exists('Stage')) {
				try {
					if((include_once(EXTENSIONS . '/datetime/lib/stage/class.stage.php')) === FALSE) {
						throw new Exception();
					}
				}
				catch(Exception $e) {
				    throw new SymphonyErrorPage(__('Please make sure that the Stage submodule is initialised and available at %s.', array('<code>' . EXTENSIONS . '/datetime/lib/stage/</code>')) . '<br/><br/>' . __('It\'s available at %s.', array('<a href="https://github.com/nilshoerrmann/stage">github.com/nilshoerrmann/stage</a>')), __('Stage not found'));
				}
			}
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#about
		 */
		public function about() {
			return array(
				'name' => 'Date and Time',
				'version' => '2.1.1',
				'release-date' => '2011-07-27',
				'author' => array(
					'name' => 'Nils Hörrmann',
					'website' => 'http://nilshoerrmann.de',
					'email' => 'post@nilshoerrmann.de'
				),
				'description'   => 'A field for single and multiple dates and date ranges'
			);
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#install
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
					`range` tinyint(1) DEFAULT '1',
        	  		PRIMARY KEY  (`id`),
			  		KEY `field_id` (`field_id`)
				)"
			);

			// Create stage
			$status[] = Stage::install();

			// Report status
			if(in_array(false, $status, true)) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#update
		 */
		public function update($previousVersion) {
			$status = array();

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

				// Get table columns
				$columns = Symphony::Database()->fetchCol('Field', "SHOW COLUMNS FROM `tbl_fields_datetime`");

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

				// Create stage
				$status[] = Stage::install();
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
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#uninstall
		 */
		public function uninstall() {

			// Drop related entries from stage tables
			Symphony::Database()->query("DELETE FROM `tbl_fields_stage` WHERE `context` = 'datetime'");
			Symphony::Database()->query("DELETE FROM `tbl_fields_stage_sorting` WHERE `context` = 'datetime'");

			// Drop date and time table
			Symphony::Database()->query("DROP TABLE `tbl_fields_datetime`");
		}

	}
