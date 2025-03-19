<?php

namespace App\DRX\Helpers;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class ExcelToArray implements ToArray
{
    public function array(array $array)
    {
        return $array;
    }
}
