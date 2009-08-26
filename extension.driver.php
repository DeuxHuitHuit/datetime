<?php

	Class extension_datetime extends Extension {
	
		/**
		 * Extension information
		 */
		 
		public function about() {
			return array(
				'name' => 'Field: Date and Time',
				'version' => '1.2',
				'release-date' => '2009-08-26',
				'author' => array(
					'name' => 'Nils Hörrmann',
					'website' => 'http://www.nilshoerrmann.de',
					'email' => 'post@nilshoerrmann.de'
				)
			);
		}
	
		/**
		 * Function to be executed on uninstallation
		 */
	
		public function uninstall() {
			// drop database table
			Administration::instance()->Database->query("DROP TABLE `tbl_fields_datetime`");
		}
	
		/**
		 * Function to be executed if the extension has been updated
		 *
		 * @param string $previousVersion - version number of the currently installed extension build
		 * @return boolean - true on success, false otherwise
		 */
		
		public function update($previousVersion) {
			// nothing to do yet
			return true;
		}
	
		/**
		 * Function to be executed on installation.
		 *
		 * @return boolean - true on success, false otherwise
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
		
	}
