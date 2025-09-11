<?php

namespace AdminHelpers\Importer\Utilities;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FromXlsToArray
{
    protected $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    private function getReader()
    {
        $extension = $this->file->extension;

        if ( $extension == 'xls' ) {
            return IOFactory::createReader('Xls');
        } else if ( $extension == 'xlsx' ) {
            return IOFactory::createReader('Xlsx');
        } else if ( $extension == 'csv' ) {
            return IOFactory::createReader('Csv');
        }
    }

    public function toArray()
    {
        $reader = $this->getReader();
        $reader->setReadDataOnly(true);

        if ( method_exists($reader, 'loadSpreadsheetFromString') ) {
            $spreadsheet = $reader->loadSpreadsheetFromString(
                $this->file->get()
            );
        } else {
            $spreadsheet = $reader->load(
                $this->file->basepath
            );
        }

        $worksheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $header = [];
        $i = 0;
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                try {
                    $value = $cell->getCalculatedValue();
                } catch (Exception $e){
                    $value = null;
                }

                $rowData[] = $value;
            }

            //We want bind header
            if ( $i == 0 ) {
                foreach ($rowData as $name) {
                    $header[self::parseHeaderString($name)] = $name;
                }

                $header = array_filter($header);
            }

            //Add row
            else {
                $trimmedRowData = array_map(function($value){
                    return trim($value);
                }, array_slice($rowData, 0, count($header)));

                if ( count(array_filter($trimmedRowData)) ) {
                    $rows[] = array_combine(array_keys($header), $trimmedRowData);
                }
            }

            $i++;
        }

        return compact('header', 'rows');
    }

    public static function parseHeaderString($string)
    {
        $string = preg_replace("/{\s| |\.|\-|\_}/", '-', $string);
        $string = mb_strtolower($string);
        $string = str_slug($string);
        $string = str_replace('-', '_', $string);

        return $string;
    }
}
