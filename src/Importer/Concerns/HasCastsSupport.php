<?php

namespace AdminHelpers\Importer\Concerns;

trait HasCastsSupport
{
    public function castRows($rows)
    {
        $casts = $this->getCasts();

        foreach ($rows as &$row) {
            foreach ($row as $key => $value) {
                $cast = $casts[$key]['cast'] ?? null;

                $row[$key] = $this->castValue($value, $cast);
            }
        }

        return $rows;
    }

    private function getCasts()
    {
        $columns = $this->getFinalColumns();

        $columns = array_filter($columns, function($column) {
            return $column['cast'] ?? false;
        });

        return $columns;
    }

    private function castValue($value, $cast = null)
    {
        // Cast empty values.
        if ( $value === '' ) {
            return null;
        }

        // Cast value if cast is set.
        if ( $cast ) {
            $value = $this->{'cast'.$cast}($value);
        }

        return $value;
    }
}