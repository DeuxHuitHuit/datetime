<?php

	/**
	 * @package content
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/datetime/lib/class.calendar.php');

	class contentExtensionDatetimeGet extends AjaxPage {

		public function handleFailedAuthorisation(){
			$this->setHttpStatus(self::HTTP_STATUS_UNAUTHORIZED);
			$this->_Result = json_encode(array('status' => __('You are not authorised to access this page.')));
		}

		public function view(){
			$this->_Result = Calendar::formatDate(General::sanitize($_GET['date']), General::sanitize($_GET['time']), NULL, true);
		}

		public function generate($page = null){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}
