<?php

	/**
	 * @package datetime
	 */
	/**
	 * Date and Time Extension
	 */
	Class extension_datetime extends Extension {

		private $languages = array(
			'dutch' => 'nl, nl_BE.UTF8, nl_BE, nl_NL.UTF8, nl_NL',
			'english' => 'en, en_GB.UTF8, en_GB',
			'finnish' => 'fi, fi_FI.UTF8, fi_FI',
			'french' => 'fr, fr_FR.UTF8, fr_FR',
			'german' => 'de, de_DE.UTF8, de_DE',
			'italian' => 'it, it_IT.UTF8, it_IT',
			'norwegian' => 'no, no_NO.UTF8, no_NO',
			'romanian' => 'ro, ro_RO.UTF8, ro_RO',
			'russian' => 'ru, ru_RU.UTF8, ru_RU',
			'portuguese' => 'pt, pt_PT.UTF8, pt_PT',
			'spanish' => 'es, es_ES.UTF8, es_ES'
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

			$status[] = Symphony::Database()
				->create('tbl_fields_datetime')
				->ifNotExists()
				->charset('utf8')
				->collate('utf8_unicode_ci')
				->fields([
					'id' => [
						'type' => 'int(11)',
						'auto' => true,
					],
					'field_id' => 'int(11)',
					'prepopulate' => [
						'type' => 'tinyint(1)',
						'default' => 1,
					],
					'time' => [
						'type' => 'tinyint(1)',
						'default' => 1,
					],
					'multiple' => [
						'type' => 'tinyint(1)',
						'default' => 1,
					],
					'range' => [
						'type' => 'tinyint(1)',
						'default' => 1,
					],
				])
				->keys([
					'id' => 'primary',
					'field_id' => 'key',
				])
				->execute()
				->success();

			// Add language strings to configuration
			Symphony::Configuration()->set('english', $this->languages['english'], 'datetime');
			Symphony::Configuration()->write();

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
		public function update($previousVersion = null) {
			$status = array();

			// Get table columns
			$columns = Symphony::Database()
				->showColumns()
				->from('tbl_fields_datetime')
				->execute()
				->column('Field');

			// Prior version 2.0
			if(version_compare($previousVersion, '2.0', '<')) {

				// Update existing entries
				$fields = Symphony::Database()
					->select(['field_id'])
					->from('tbl_fields_datetime')
					->execute()
					->column('field_id');

				foreach($fields as $field) {

					// New database schema
					$status[] = Symphony::Database()
						->alter('tbl_entries_data_' . $field)
						->modify([
							'start' => 'datetime',
							'end' => 'datetime',
						])
						->execute()
						->success();

					// Don't allow empty end dates
					$status[] = Symphony::Database()
						->update('tbl_entries_data_' . $field)
						->set([
							'end' => 'start',
						])
						->where('or' => [
							['end' => 'none'],
							['end' => '0000-00-00 00:00'],
						])
						->execute()
						->success();
				}

				// Remove allow multiple setting
				if(in_array('allow_multiple_dates', $columns)) {
					$status[] = Symphony::Database()
						->alter('tbl_fields_datetime')
						->drop('allow_multiple_dates')
						->execute()
						->success();
				}

				// Add time setting
				if(!in_array('time', $columns)) {
					$status[] = Symphony::Database()
						->alter('tbl_fields_datetime')
						->add([
							'time' => [
								'type' => 'tinyint(1)',
								'default' => 1,
							],
						])
						->execute()
						->success();
				}

				// Add range setting
				if(!in_array('range', $columns)) {
					$status[] = Symphony::Database()
						->alter('tbl_fields_datetime')
						->add([
							'range' => [
								'type' => 'tinyint(1)',
								'default' => 1,
							],
						])
						->execute()
						->success();
				}

				// Modify prepopulation setting
				$status[] = Symphony::Database()
					->alter('tbl_fields_datetime')
					->modify([
						'prepopulate' => [
							'type' => 'tinyint(1)',
							'default' => 1,
						],
					])
					->execute()
					->success();

				// Correctly store old 'no' values
				$status[] = Symphony::Database()
					->update('tbl_fields_datetime')
					->set([
						'prepopulate' => 0,
					])
					->where(['prepopulate' => ['>' => 1]])
					->execute()
					->success();
			}

			// Prior version 2.4
			if(version_compare($previousVersion, '2.4', '<')) {

				// Move language codes to configuration
				Symphony::Configuration()->set('english', $this->languages['english'], 'datetime');
				Symphony::Configuration()->set('german', $this->languages['german'], 'datetime');
				Symphony::Configuration()->write();
			}

			// Prior version 3.0
			if(version_compare($previousVersion, '3.0', '<')) {

				// Add multiple setting
				if(!in_array('multiple', $columns)) {
					$status[] = Symphony::Database()
						->alter('tbl_fields_datetime')
						->add([
							'multiple' => [
								'type' => 'tinyint(1)',
								'default' => 1,
							],
						])
						->execute()
						->success();
				}

				// Transfer old Stage settings
				$constructables = Symphony::Database()
					->select(['field_id'])
					->from('tbl_fields_stage')
					->where(['constructable' => 0])
					->execute()
					->column('field_id');

				if(!empty($constructables) && is_array($constructables)) {
					$status[] = Symphony::Database()
						->update('tbl_fields_datetime')
						->set([
							'multiple' => 0,
						])
						->where(['field_id' => ['in' => $constructables]])
						->execute()
						->success();
				}

				// Remove old Stage instances
				$does_stage_exist = Symphony::Database()
					->show()
					->like('tbl_fields_stage')
					->execute()
					->rows();

				if(!empty($does_stage_exist)) {
					$status[] = Symphony::Database()
						->delete('tbl_fields_stage')
						->where(['context' => 'datetime'])
						->execute()
						->success();

					$status[] = Symphony::Database()
						->delete('tbl_fields_stage_sorting')
						->where(['context' => 'datetime'])
						->execute()
						->success();
				}
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
			$status = array();

			// Remove old Stage tables if they are empty
			$does_stage_exist = Symphony::Database()
				->show()
				->like('tbl_fields_stage')
				->execute()
				->rows();

			if(!empty($does_stage_exist)) {
				$count = Symphony::Database()
					->select(['count(*)'])
					->from('tbl_fields_stage')
					->execute()
					->rows();

				if($count == 0) {
					$status[] = Symphony::Database()
						->drop('tbl_fields_stage')
						->ifExists()
						->execute()
						->success();

					$status[] = Symphony::Database()
						->drop('tbl_fields_stage_sorting')
						->ifExists()
						->execute()
						->success();
				}
			}

			// Drop date and time table
			$status[] = Symphony::Database()
				->drop('tbl_fields_datetime')
				->ifExists()
				->execute()
				->success();

			// Remove language strings from configuration
			Symphony::Configuration()->remove('datetime');

			// Report status
			if(in_array(false, $status, true)) {
				return false;
			}
			else {
				return true;
			}
		}

	}
