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
class EntryQueryDatetimeAdapter extends EntryQueryDateAdapter
{
	public function isFilterRange($filter)
    {
        return preg_match('/^(start|end|strict|extended): ?\s*/', $filter);
    }

    public function createFilterRange($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $matches = [];
        preg_match('/^(start|end|strict|extended): ?\s*/', $filter, $matches);

        $filter = trim(array_pop(explode(':', $filter, 2)));
        $filter = $this->field->cleanValue($filter);

        $conditions = [];

        if ($matches[1] == 'start') {
            $conditions[] = [$this->formatColumn('start', $field_id) => ['<=' => $filter]];
        }
        if ($matches[1] == 'end') {
            $conditions[] = [$this->formatColumn('end', $field_id) => ['>=' => $filter]];
        }
        if ($matches[1] == 'strict') {
            $conditions[] = [$this->formatColumn('start', $field_id) => ['<=' => $filter]];
            $conditions[] = [$this->formatColumn('end', $field_id) => ['>=' => $filter]];
        }
        if ($matches[1] == 'extended') {
            $conditions[] = [$this->formatColumn('start', $field_id) => ['<=' => $filter]];
        }

        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

	/**
     * @see EntryQueryFieldAdapter::filterSingle()
     *
     * @param EntryQuery $query
     * @param string $filter
     * @return array
     */
    protected function filterSingle(EntryQuery $query, $filter)
    {
        General::ensureType([
            'filter' => ['var' => $filter, 'type' => 'string'],
        ]);
        if ($this->isFilterRegex($filter)) {
            return $this->createFilterRegexp($filter, $this->getFilterColumns());
        } elseif ($this->isFilterRange($filter)) {
            return $this->createFilterRange($filter, $this->getFilterColumns());
        }
        return $this->createFilterEquality($filter, $this->getFilterColumns());
    }

    public function getFilterColumns()
    {
        return ['start', 'end'];
    }

    public function getSortColumns()
    {
        return ['start'];
    }
}
