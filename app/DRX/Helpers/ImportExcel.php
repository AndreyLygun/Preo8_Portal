<?php


namespace App\DRX\Helpers;


use Maatwebsite\Excel\Facades\Excel;

class ImportExcel
{
    public static function Basic($filepath, $columns) : array
    {
        $array = Excel::toArray(new ExcelToArray(), $filepath)[0];
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
