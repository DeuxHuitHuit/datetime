<?php
if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

Class fieldDatetime extends Field {

    const SIMPLE = 0;
    const REGEXP = 1;
    const RANGE = 3;
    const ERROR = 4;
    private $key;

    private static $english = array(
            'yesterday', 'today', 'tomorrow', 'now',
            'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
            'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
            'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa',
            'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December',
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    );

    private $locale;

    /**
     * Initialize Datetime as unrequired field.
     */

    function __construct(&$parent) {
        // Replace relative and locale date and time strings
        foreach(self::$english as $string) {
            $locale[] = __($string);
        }
        $this->locale = $locale;

        parent::__construct($parent);
        $this->_name = __('Date/Time');
        $this->_required = false;
    }

    /**
     * Allow data source filtering.
     */

    function canFilter() {
        return true;
    }

    /**
     * Allow data source sorting.
     */

    function isSortable() {
        return true;
    }

    /**
     * Allow prepopulation of other fields.
     */

    function canPrePopulate() {
        return false;
    }

    /**
     * Allow data source output grouping.
     */

    function allowDatasourceOutputGrouping() {
        return true;
    }

    /**
     * Allow data source parameter output.
     */

    function allowDatasourceParamOutput() {
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
        $label->appendChild(
            Widget::Input('fields['.$this->get('sortorder').'][format]', $this->get('format') ? $this->get('format') : 'd MMMM yyyy, HH:mm')
        );
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

		if (Administration instanceof Symphony) {
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/jquery-ui.js', 100, true);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/datetime.js', 201, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/assets/datetime.css', 'screen', 202, false);
		}

        // title and help
        $wrapper->setValue($this->get('label') . '<i>' . __('Press <code>alt</code> to add a range') . '</i>');

        // settings
        $fieldname = 'fields['  .$this->get('element_name') . ']';
        $setting = array(
            'DATE' => __('date'),
            'FROM' => __('from'),
            'START' => __('start'),
            'END' => __('end'),
            'FORMAT' => $this->get('format'),
            'multiple' => $this->get('allow_multiple_dates'),
            'prepopulate' => $this->get('prepopulate')
        );
        $settings = Widget::Input($fieldname . '[settings]', str_replace('"', "'", json_encode($setting)), 'hidden');

        // default setup
        if($data == NULL) {
            $label = Widget::Label(NULL, NULL, 'first last');

            $span = new XMLElement('span', '<em>' . __('from') . '</em>', array('class' => 'start'));
            $span->appendChild(
                Widget::Input($fieldname . '[start][]', '', 'text')
            );
            $span->appendChild(
                new XMLElement('a', 'delete', array('class' => 'delete'))
            );
            $label->appendChild($span);

            $span = new XMLElement('span', '<em>' . __('to') . '</em>', array('class' => 'end'));
            $span->appendChild(
                Widget::Input($fieldname . '[end][]', '', 'text')
            );
            $label->appendChild($span);

            $label->appendChild($settings);
            $wrapper->appendChild($label);
        } else {
            if(!is_array($data['start'])) $data['start'] = array($data['start']);
            if(!is_array($data['end'])) $data['end'] = array($data['end']);

			$count = count($data['start']);
            for($i = 1; $i <= $count; $i++) {
                $label = Widget::Label();

                if($i == 1 && $i != $count) {
                    $label->setAttribute('class', 'first');
                } else if($i == 1 && $i == $count) {
                    $label->setAttribute('class', 'first last');
                } else if($i != 1 && $i == $count) {
                    $label->setAttribute('class', 'last');
                }

                $span = new XMLElement('span', '<em>' . __('from') . '</em>', array('class' => 'start'));
                $span->appendChild(
                    Widget::Input($fieldname . '[start][]', $data['start'][$i - 1], 'text')
                );
                $span->appendChild(
                    new XMLElement('a', 'delete', array('class' => 'delete'))
                );
                $label->appendChild($span);

                $span = new XMLElement('span', '<em>' . __('to') . '</em>', array('class' => 'end'));
                $span->appendChild(
                    Widget::Input($fieldname . '[end][]', ($data['end'][$i - 1] == '0000-00-00 00:00:00') ? '' : $data['end'][$i - 1], 'text')
                );
                $label->appendChild($span);

                if($i == 1) $label->appendChild($settings);
                $wrapper->appendChild($label);
            }
        }

        // add new
        if($this->get('allow_multiple_dates') == 'yes') {
            $wrapper->appendChild(
				new XMLElement('a', __('Add new date'), array('class' => 'new'))
			);
        }
    }

    /**
     * Prepares field values for database.
     */

    function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL) {

        $status = self::__OK__;
        if(!is_array($data) or empty($data)) return NULL;

        $result = array('entry_id' => array(), 'start' => array(), 'end' => array());
        $count = count($data['start']);
        for($i = 0; $i < $count; $i++) {
            if(!empty($data['start'][$i])) {
                $result['entry_id'][] = $entry_id;
                $result['start'][] = date('c', strtotime(str_replace($this->locale, self::$english, $data['start'][$i])));
                $result['end'][] = empty($data['end'][$i]) ? '0000-00-00 00:00:00' : date('c', strtotime(str_replace($this->locale, self::$english, $data['end'][$i])));
            }
        }
        return $result;

    }

    /**
     * Creates database field table.
     */

    function createTable() {

        return Administration::instance()->Database->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
            `id` int(11) unsigned NOT NULL auto_increment,
            `entry_id` int(11) unsigned NOT NULL,
            `start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
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

    function prepareTableValue($data, XMLElement $link=NULL) {

        $value = '';
        if(!is_array($data['start'])) $data['start'] = array($data['start']);
        if(!is_array($data['end'])) $data['end'] = array($data['end']);

        foreach($data['start'] as $id => $date) {
            if(empty($date)) continue;
            if($data['end'][$id] != "0000-00-00 00:00:00") {
                if($value != '') $value .= ', ';

				/* 	If it's not the same day
				**	from {date}{time} to {date}{time} else
				**	{date}{time} - {time}
				*/
				if(DateTimeObj::get("D M Y", strtotime($data['start'][$id])) != DateTimeObj::get("D M Y", strtotime($data['end'][$id]))) {
					$value .= '<span style="color: rgb(136, 136, 119);">' . __('from') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id]));
	                $value .= ' <span style="color: rgb(136, 136, 119);">' .__('to') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['end'][$id]));
				} else {
					$value .= '<span style="color: rgb(136, 136, 119);">' . __('from') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id]));
					$value .= ' <span style="color: rgb(136, 136, 119);">-</span> ' . DateTimeObj::get(__SYM_TIME_FORMAT__, strtotime($data['end'][$id]));
				}

            } else {
                if($value != '') $value .= ', ';
                $value .= DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id]));
            }
        }

        return str_replace(self::$english, $this->locale, $value);

    }

    /**
     * Build data source sorting sql.
     *
     * @param string $joins
     * @param string $where
     * @param string $sort
     * @param string $order
     */

    function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC') {
        $joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `dt` ON (`e`.`id` = `dt`.`entry_id`) ";
        $sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`dt`.`start` $order");
    }

    /**
     * Build data source retrival sql.
     *
     * @param array $data
     * @param string $joins
     * @param string $where
     * @param boolean $andOperation
     */

    function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {

        if (self::isFilterRegex($data[0])) {
            $field_id = $this->get('id');
            $this->_key++;
            $pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
            $joins .= "
                LEFT JOIN
                    `sym_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";
            $where .= "
                AND t{$field_id}_{$this->_key}.start REGEXP `{$pattern}` OR t{$field_id}_{$this->_key}.end REGEXP `{$pattern}`
            ";
            return true;
        }

        $parsed = array();

        foreach($data as $string) {
            $type = self::__parseFilter($string);
            if($type == self::ERROR) return false;
            if(!is_array($parsed[$type])) $parsed[$type] = array();
            $parsed[$type][] = $string;
        }

        foreach($parsed as $type => $value) {
            switch($type) {
                case self::RANGE:
                    if(!empty($value)) $this->__buildRangeFilterSQL($value, $joins, $where, $andOperation);
                    break;

                case self::SIMPLE:
                    if(!empty($value)) $this->__buildSimpleFilterSQL($value, $joins, $where, $andOperation);
                    break;
            }
        }

        return true;

    }

    /**
     * Build sql for single dates.
     *
     * @param array $data
     * @param string $joins
     * @param string $where
     * @param boolean $andOperation
     */

    protected function __buildSimpleFilterSQL($data, &$joins, &$where, $andOperation = false) {

        $field_id = $this->get('id');

        $connector = ' OR '; // filter separated with commas
        if($andOperation == 1) $connector = ' AND '; // filter conntected with plus signs

        foreach($data as $date) {
            $tmp[] = "'" . DateTimeObj::get('Y-m-d', strtotime($date)) . "' BETWEEN
                DATE_FORMAT(`t$field_id".$this->key."`.start, '%Y-%m-%d') AND
                DATE_FORMAT(`t$field_id".$this->key."`.end, '%Y-%m-%d')";
        }
        $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
        $where .= " AND (".@implode($connector, $tmp).") ";
        $this->key++;

    }

    /**
     * Build sql for dates ranges.
     *
     * @param array $data
     * @param string $joins
     * @param string $where
     * @param boolean $andOperation
     */

    protected function __buildRangeFilterSQL($data, &$joins, &$where, $andOperation=false) {

        $field_id = $this->get('id');

        $connector = ' OR '; // filter separated with commas
        if($andOperation == 1) $connector = ' AND '; // filter conntected with plus signs

        foreach($data as $date) {
            $tmp[] = "(DATE_FORMAT(`t$field_id".$this->key."`.start, '%Y-%m-%d') BETWEEN
                '" . DateTimeObj::get('Y-m-d', strtotime($date['start'])) . "' AND
                '" . DateTimeObj::get('Y-m-d', strtotime($date['end'])) . "' OR
                DATE_FORMAT(`t$field_id".$this->key."`.end, '%Y-%m-%d') BETWEEN
                '" . DateTimeObj::get('Y-m-d', strtotime($date['start'])) . "' AND
                '" . DateTimeObj::get('Y-m-d', strtotime($date['end'])) . "')";
        }
        $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
        $where .= " AND (".@implode($connector, $tmp).") ";
        $this->key++;

    }

    /**
     * Clean up date string.
     * This function is a copy from the core date field.
     *
     * @param string $string
     */

    protected static function __cleanFilterString($string) {

        $string = trim($string);
        $string = trim($string, '-/');
        return $string;

    }

    /**
     * Parse filter string for shorthand dates and ranges.
     * This function is a copy from the core date field.
     *
     * @param string $string
     */

    protected static function __parseFilter(&$string) {

        $string = self::__cleanFilterString($string);

        // Check its not a regexp
        if(preg_match('/^regexp:/i', $string)) {
            $string = str_replace('regexp:', '', $string);
            return self::REGEXP;
        }

        // Look to see if its a shorthand date (year only), and convert to full date
        elseif(preg_match('/^(1|2)\d{3}$/i', $string)) {
            $string = "$string-01-01 to $string-12-31";
        }

        elseif(preg_match('/^(earlier|later) than (.*)$/i', $string, $match)) {

            $string = $match[2];

            if(!self::__isValidDateString($string)) return self::ERROR;

            $time = strtotime($string);

            switch($match[1]){
                case 'later': $string = DateTimeObj::get('Y-m-d H:i:s', $time+1) . ' to 2038-01-01'; break;
                case 'earlier': $string = '1970-01-03 to ' . DateTimeObj::get('Y-m-d H:i:s', $time-1); break;
            }

        }

        // Look to see if its a shorthand date (year and month), and convert to full date
        elseif(preg_match('/^(1|2)\d{3}[-\/]\d{1,2}$/i', $string)) {

            $start = "$string-01";

            if(!self::__isValidDateString($start)) return self::ERROR;

            $string = "$start to $string-" . date('t', strtotime($start));
        }

        // Match for a simple date (Y-m-d), check its ok using checkdate() and go no further
        elseif(!preg_match('/to/i', $string)) {

            if(!self::__isValidDateString($string)) return self::ERROR;

            $string = DateTimeObj::get('Y-m-d H:i:s', strtotime($string));
            return self::SIMPLE;

        }

        // Parse the full date range and return an array

        if(!$parts = preg_split('/to/', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;

        $parts = array_map(array('self', '__cleanFilterString'), $parts);

        list($start, $end) = $parts;

        if(!self::__isValidDateString($start) || !self::__isValidDateString($end)) return self::ERROR;

        $string = array('start' => $start, 'end' => $end);

        return self::RANGE;
    }

    /**
     * Validate date.
     * This function is a copy from the core date field.
     *
     * @param string $string
     */

    protected static function __isValidDateString($string) {

        $string = trim($string);

        if(empty($string)) return false;

        // Its not a valid date, so just return it as is
        if(!$info = getdate(strtotime($string))) return false;
        elseif(!checkdate($info['mon'], $info['mday'], $info['year'])) return false;

        return true;
    }

    /**
     * Group records by year and month (calendar view).
     *
     * @param $wrapper
     */

    function groupRecords($records) {

        if(!is_array($records) || empty($records)) return;

        $groups = array('year' => array());

        // walk through dates
        foreach($records as $entry) {
            $data = $entry->getData($this->get('id'));
            if(!is_array($data['start'])) $data['start'] = array($data['start']);
            if(!is_array($data['end'])) $data['end'] = array($data['end']);
            // create calendar
            foreach($data['start'] as $id => $start) {
                $start = date('Y-m-01', strtotime($start));
                if($data['end'][$id] == "0000-00-00 00:00:00") $data['end'][$id] = $start;
                $end = date('Y-m-01', strtotime($data['end'][$id]));
                $starttime = strtotime($start);
                $endtime = strtotime($end);
                // find matching months
                while($starttime <= $endtime) {
                    $year = date('Y', $starttime);
                    $month[1] = date('n', $starttime);
                    $month[2] = date('m', $starttime);
                    // add entry
                    $groups['year'][$year]['attr']['value'] = $year;
                    $groups['year'][$year]['groups']['month'][$month[1]]['attr']['value'] = $month[2];
                    $groups['year'][$year]['groups']['month'][$month[1]]['records'][] = $entry;
                    // jump to next month
                    $starttime = strtotime(date('Y-m-01', $starttime) . ' +1 month');
                }
            }
        }

        // sort years and months
        ksort($groups['year']);
        foreach($groups['year'] as $year) {
            $current = $year['attr']['value'];
            ksort($groups['year'][$current]['groups']['month']);
        }

        // return calendar groups
        return $groups;

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
        if(!is_array($data['start'])) $data['start'] = array($data['start']);
        if(!is_array($data['end'])) $data['end'] = array($data['end']);
        $timeline = $data['start'];
        sort($timeline);

        // generate XML
        foreach($data['start'] as $id => $date) {
            if(empty($date)) continue;
            $date = new XMLElement('date');
            $date->setAttribute('timeline', array_search($data['start'][$id], $timeline) + 1);
            $timestamp = strtotime($data['start'][$id]);
            $date->appendChild(
                $start = new XMLElement('start', DateTimeObj::get('Y-m-d', $timestamp), array(
                        'iso' => DateTimeObj::get('c', strtotime($data['start'][$id])),
                        'time' => DateTimeObj::get('H:i', $timestamp),
                        'weekday' => DateTimeObj::get('w', $timestamp),
                        'offset' => DateTimeObj::get('O', $timestamp)
                    )
                )
            );

            if($data['end'][$id] != "0000-00-00 00:00:00") {
                $timestamp = strtotime($data['end'][$id]);

                $date->appendChild(
                    $end = new XMLElement('end', DateTimeObj::get('Y-m-d', $timestamp), array(
                            'iso' => DateTimeObj::get('c', strtotime($data['end'][$id])),
                            'time' => DateTimeObj::get('H:i', $timestamp),
                            'weekday' => DateTimeObj::get('w', $timestamp),
                            'offset' => DateTimeObj::get('O', $timestamp)
                        )
                    )
                );
                $date->setAttribute('type', 'range');
            } else {
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
     * Sample markup for the event editor.
     */

    public function getExampleFormMarkup() {

        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Input('fields['.$this->get('element_name').'][start][]'));
        return $label;

    }
}
