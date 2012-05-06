<?php
 
	/**
	 * @package content
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/datetime/lib/class.calendar.php');
	
	class contentExtensionDatetimeGet extends AdministrationPage {

		/**
		 * Used to fetch subsection items via an AJAX request.
		 */
		public function __viewIndex() {
			echo Calendar::formatDate(General::sanitize($_GET['date']), General::sanitize($_GET['time']), NULL, true);
			exit;
		}

	}
 
?>