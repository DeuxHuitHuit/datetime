<?php

	Class extension_datetime extends Extension {

		/**
		 * Extension information
		 */

		public function about() {
			return array(
				'name' => 'Date and Time',
				'type' => 'Field, Interface',
				'repository'    => 'http://github.com/nilshoerrmann/datetime/',
				'version' => '1.4',
				'release-date' => '2010-08-27',
				'author' => array(
					'name' => 'Nils Hörrmann',
					'website' => 'http://nilshoerrmann.de',
					'email' => 'post@nilshoerrmann.de'
				),
				'description'   => 'A field for single dates, multiple dates and date ranges',
				'compatibility' => array(
				    '2.0.6' => true,
				    '2.0.7' => true
				)
			);
		}

		/**
		 * Add callback functions to backend delegates
		 */

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => '__addDateJS'
				)
			);
		}

		/**
		 * Add international dateJS
		 */

		public function __addDateJS() {
			// get current language
			$lang = Administration::instance()->Configuration->get('lang', 'symphony');
			// check dateJS language
			if(file_exists(EXTENSIONS . '/datetime/assets/international/datejs.' . $lang . '.js')) {
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/international/datejs.' . $lang . '.js', 200, false);
			}
			else {
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/international/datejs.en.js', 200, false);
			}
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
			/*	Go through all the existing datetime tables converting the start/end to datetime */
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
