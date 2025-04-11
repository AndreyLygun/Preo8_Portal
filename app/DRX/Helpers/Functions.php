<?php

namespace App\DRX\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Orchid\Screen\Actions\Link;

// Получаем самую ранюю дату исполнения заявки (сегодня или завтра) в зависимости от текущего времени
// Если текущее время меньше указанного часа, возвращаем сегодняшнюю дату.
// Если текущее время больше указанного часа, возвращаем завтрашнюю дату.
class Functions
{
    public static function Eear_liest_Date(int $hour, int $deltaDays = 1)
    {
        if (Carbon::now()->hour < $hour)
            return Carbon::today();
        else
            return Carbon::today()->addDay(1);
    }

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

    public function MakeCopyButton($id)
    {
        return Link::make('Копировать')->route(Route::currentRouteName(), ['fromId' => $this->entity['Id']]);
    }
}
