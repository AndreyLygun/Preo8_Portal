<?php


namespace App\DRX\Helpers;


use Carbon\Carbon;

trait NormalizeDate
{
    // преобразовывает дату в формат, требуемый для OData
    public function NormalizeDate(string|array $fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $field)
                if (isset($this->entity[$field]))
                    $this->entity[$field] = Carbon::parse($this->entity[$field])->format('Y-m-d\TH:i:00+03:00');
        } else {
            if (isset($this->entity[$fields]))
                $this->entity[$fields] = Carbon::parse($this->entity[$fields])->format('Y-m-d\TH:i:00+03:00');;
        }
    }
}
