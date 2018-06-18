<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * a datetime Field.
 * @see FieldDateTime
 * @since Symphony 3.0.0
 */
class EntryQueryDatetimeAdapter extends EntryQueryFieldAdapter
{
    public function getFilterColumns()
    {
        return ['start', 'end'];
    }

    public function getSortColumns()
    {
        return ['start', 'end'];
    }
}
