<?php

namespace AdminHelpers\Importer\Concerns;

use AdminHelpers\Importer\Concerns\FormattingError;
use AdminHelpers\Importer\Utilities\FromXlsToArray;

trait HasColumnsSupport
{
    private $castedColumns;

    public function getFinalColumns()
    {
        if ( $this->castedColumns ){
            return $this->castedColumns;
        }

        $columns = $this->getColumns();

        $columnHeader = array_map(function($key) use ($columns) {
            return $this->parseKey($key);
        }, array_keys($columns));

        return $this->castedColumns = array_combine($columnHeader, array_values($columns));
    }


    public function checkColumnsFormat($errors = [])
    {
        $columns = array_filter($this->getFinalColumns(), function($column){
            // Check exact format
            if ( isset($column['format']) ){
                return true;
            }

            // Check required
            if ( ($column['required'] ?? false) === true ){
                return true;
            }

            return false;
        });

        if ( count($columns) == 0 ){
            return [];
        }

        foreach ($this->array['rows'] as $i => $row) {
            foreach ($columns as $key => $column) {
                $index = $i + 2;
                $value = $row[$key] ?? null;

                if ( !$this->isEmpty($value) && isset($column['format']) ) {
                    $formats = array_wrap($column['format']);

                    foreach ($formats as $format) {
                        if ( $this->{'isValid'.$format}($value, $row) === false ) {
                            $errors[] = 'Riadok č.'.$index.' - '.$key.' (zlý formát)';
                        }
                    }
                }

                if ( ($column['required'] ?? false) === true && (is_null($value) || $value == '') ) {
                    $errors[] = 'Riadok č.'.$index.' - '.$key.' (prázdna hodnota)';
                }
            }
        }

        if ( count($errors) ){
            throw new FormattingError(
                _('V stĺpcoch pre import '.$this->getName().' sa nachádzaju nasledovné chyby:').'<br>'.
                implode('<br>', array_unique($errors))
            );
        }
    }

    public function checkColumnsAviability($errors = [])
    {
        $columns = $this->getFinalColumns();

        foreach ($columns as $columnSheetKey => $column) {
            //If is not required, skip.
            if ( ($column['required'] ?? false) === false ){
                continue;
            }

            // Must be in header
            if ( !array_key_exists($columnSheetKey, $this->array['header']) ){
                $errors[] = $columnSheetKey;
            }
        }

        if ( count($errors) ){
            throw new FormattingError(
                _('V súbore pre import '.$this->getName().' sme nenašli potrebné stĺpce:').'<br>'.
                '<strong>'.implode(' | ', $errors).'</strong>'
            );
        }
    }

    private function parseKey($columnName)
    {
        return FromXlsToArray::parseHeaderString($columnName);
    }

    private function isEmpty($value)
    {
        return $value === null || $value === '';
    }
}