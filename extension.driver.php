<?php

	/**
	 * Date and Time Extension
	 */
	Class extension_datetime extends Extension {

		/**
		 * The about method allows an extension to provide
		 * information about itself as an associative array.
		 *
		 * @return array
		 *  An associative array describing this extension.
		 */
		public function about() {
			return array(
				'name' => 'Date and Time',
				'version' => '2.0dev',
				'release-date' => NULL,
				'author' => array(
					'name' => 'Nils Hörrmann',
					'website' => 'http://nilshoerrmann.de',
					'email' => 'post@nilshoerrmann.de'
				),
				'description'   => 'A field for single dates, multiple dates and date ranges'
			);
		}

		public function install() {
			$status = array();
		
			// Create database field table
			$status[] = Symphony::Database()->query(
				"CREATE TABLE `tbl_fields_datetime` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`prepopulate` tinyint(1) DEFAULT '1',
					`time` tinyint(1) DEFAULT '1',
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
		
		public function update($previousVersion) {
			$status = array();
			
			// Prior version 2.0
			if(version_compare($previousVersion, '2.0', '<')) {
			
				// Update existing entries
				$fields = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` from `tbl_fields_datetime`");
				foreach($fields as $field) {
					$status[] = Symphony::Database()->query(
						"ALTER TABLE `tbl_entries_data_$field`
						 MODIFY `start` varchar(255) NOT NULL,
						 MODIFY `end` varchar(255)'"
					);
				}
				
				// Change fields
				$status[] = Symphony::Database()->query(
					"ALTER TABLE `tbl_fields_datetime`
					 DROP `allow_multiple_dates`,
					 MODIFY `prepopulate` tinyint(1) DEFAULT '1',
					 ADD `time` tinyint(1) DEFAULT '1'"
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

		public function uninstall() {
		
			// Drop related entries from stage tables
			Symphony::Database()->query("DELETE FROM `tbl_fields_stage` WHERE `context` = 'datetime'");
			Symphony::Database()->query("DELETE FROM `tbl_fields_stage_sorting` WHERE `context` = 'datetime'");
			
			// Drop date and time table
			Symphony::Database()->query("DROP TABLE `tbl_fields_datetime`");
		}

	}
