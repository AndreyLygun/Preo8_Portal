<?php


namespace App\DRX\Helpers;


use Maatwebsite\Excel\Facades\Excel;

class ImportExcel
{
    public static function Basic($UploadedFile, $columns) : array
    {
        $array = Excel::toArray(new ExcelToArray(), $UploadedFile)[0];
        array_shift($array); // Убираем строку с заголовками
        $res = [];
        foreach ($array as $row) {
            $item = [];
            foreach($columns as $key => $name) {
             $item[$name] = $row[$key];
            }
            $res[] = $item;
        }
        return $res;
    }
}
