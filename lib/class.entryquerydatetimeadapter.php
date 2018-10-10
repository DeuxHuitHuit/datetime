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
    public function createFilterDateRange($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $matches = [];
        preg_match('/^(start|end|strict|extended): ?\s*/', $filter, $matches);

        $filter = trim(array_pop(explode(':', $filter, 2)));
        $filter = $this->field->cleanValue($filter);
        $range = (new DateRangeParser($filter))->parse();

        $conditions = [];

        if ($matches[1] == 'start') {
            $conditions[] = [$this->formatColumn('start', $field_id) => ['between' => [$range['start'], $range['end']]]];
        } elseif ($matches[1] == 'end') {
            $conditions[] = [$this->formatColumn('end', $field_id) => ['between' => [$range['start'], $range['end']]]];
        } elseif ($matches[1] == 'strict') {
            $conditions[] = ['and' => [
                [$this->formatColumn('start', $field_id) => ['between' => [$range['start'], $range['end']]]],
                [$this->formatColumn('end', $field_id) => ['between' => [$range['start'], $range['end']]]]
            ]];
        } elseif ($matches[1] == 'extended') {
            $conditions[] = ['or' => [
                [$this->formatColumn('start', $field_id) => ['between' => [$range['start'], $range['end']]]],
                [$this->formatColumn('end', $field_id) => ['between' => [$range['start'], $range['end']]]],
                ['and' => [
                    [$this->formatColumn('start', $field_id) => ['<' => $range['start']]],
                    [$this->formatColumn('end', $field_id) => ['>' => $range['end']]],
                ]],
                ['and' => [
                    [$this->formatColumn('start', $field_id) => ['<' => $range['start']]],
                    [$this->formatColumn('end', $field_id) => '$'.$this->formatColumn('start', $field_id)],
                ]]
            ]];
        } else {
            $conditions = parent::createFilterDateRange($filter, $columns);
        }

        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

    public function getFilterColumns()
    {
        return ['start'];
    }

    public function getSortColumns()
    {
        return ['start'];
    }
}
