<?php

namespace AdminHelpers\Importer\Imports;

use Admin\Eloquent\AdminModel;
use Admin\Core\Helpers\Storage\AdminFile;
use AdminHelpers\Importer\Concerns\HasCastsSupport;
use AdminHelpers\Importer\Utilities\FromXlsToArray;
use AdminHelpers\Importer\Concerns\HasColumnsSupport;

class BaseImport
{
    use HasCastsSupport,
        HasColumnsSupport;

    public AdminModel $import;

    public AdminFile $file;

    public $array;

    public function getColumns()
    {
        return [];
    }

    public function __construct($import)
    {
        $this->import = $import;

        if ( $import->file ) {
            $this->file = $import->file;

            $this->array = (new FromXlsToArray($this->file))->toArray();
        }
    }

    public function getName()
    {
        return $this->import->getSelectOption('type')['name'] ?? 'Import';
    }

    public function import(AdminModel $importRow)
    {
        //..
    }

    public function getRows()
    {
        $rows = $this->array['rows'];

        $rows = $this->cleanRows($rows);

        $rows = $this->castRows($rows);

        return collect($rows);
    }

    private function cleanRows($rows)
    {
        $columns = $this->getFinalColumns();

        foreach ($rows as $k => $row) {
            $row = array_intersect_key($row, $columns);

            $rows[$k] = $row;
        }

        return $rows;
    }
}
