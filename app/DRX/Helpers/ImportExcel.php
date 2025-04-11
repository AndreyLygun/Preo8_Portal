<?php


namespace App\DRX\Helpers;


use Maatwebsite\Excel\Facades\Excel;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Layout;

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

    public static function MakeModalExcel($title, $sampleUrl) {
        return Layout::modal('Excel', [
            Layout::rows([
                Input::make('ExcelFile')
                    ->type('file')
                    ->title($title)
                    ->accept('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel')
                    ->required(),
                Link::make('Пример файла для заполнения')->href($sampleUrl)->download()->target('_blank')
            ])
        ]);
    }
}
