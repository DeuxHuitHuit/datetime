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
				'type' => 'Field, Interface',
				'repository'    => 'http://github.com/nilshoerrmann/datetime/',
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

		/**
		 * Any logic that assists this extension in being installed such as
		 * table creation, checking for dependancies etc.
		 *
		 * @see toolkit.ExtensionManager#install
		 * @return boolean
		 *  True if the install completely successfully, false otherwise
		 */
		public function install() {
		
			// Create database table and fields.
			return Administration::instance()->Database->query(
				"CREATE TABLE `tbl_fields_datetime` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`format` text,
					`prepopulate` enum('yes','no') NOT NULL default 'yes',
					`allow_multiple_dates` enum('yes','no') NOT NULL default 'yes',
        	  		PRIMARY KEY  (`id`),
			  		KEY `field_id` (`field_id`)
				)"
			);
		}
		

		/**
		 * Logic that should take place when an extension is to be been updated
		 * when a user runs the 'Enable' action from the backend. The currently
		 * installed version of this extension is provided so that it can be
		 * compared to the current version of the extension in the file system.
		 * This is commonly done using PHP's version_compare function. Common
		 * logic done by this method is to update differences between extension
		 * tables.
		 *
		 * @see toolkit.ExtensionManager#update
		 * @param string $previousVersion
		 *  The currently installed version of this extension from the
		 *  tbl_extensions table. The current version of this extension is
		 *  provided by the about() method.
		 * @return boolean
		 */
		public function update($previousVersion) {
			
			// Go through all the existing datetime tables converting start and end to datetime
			if(version_compare($previousVersion, '1.3', '<')){
				$fields = Administration::instance()->Database->fetchCol("field_id", "SELECT `field_id` from `tbl_fields_datetime`");

				foreach($fields as $field) {
					Administration::instance()->Database->query(
						sprintf("
							ALTER TABLE `tbl_entries_data_%d`
							MODIFY `end` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
							MODIFY `start` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
						", $field)
					);
				}
			}

			return true;
		}

		/**
		 * Any logic that should be run when an extension is to be uninstalled
		 * such as the removal of database tables.
		 *
		 * @see toolkit.ExtensionManager#uninstall
		 * @return boolean
		 */
		public function uninstall() {
			Administration::instance()->Database->query("DROP TABLE `tbl_fields_datetime`");
		}

	}
