<?php

namespace App\Orchid\Screens\DRX;


use Carbon\Carbon;
use DateTimeZone;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;





class Pass4VisitorCarScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPass4VisitorCars";
    public $Title = "Заявка на разовый автопропуск";

    // Описывает макет экрана
    public function layout(): iterable
    {
        $tz = new DateTimeZone('Europe/Moscow');

        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOnDateTime")->title("Дата/время въезда")->horizontal()
                    ->format("Y-m-d\TH:i:00+03:00")
//                    ->serverformat('Y-m-d\TH:i:00+03:00')
                    ->enableTime(true)->format24hr()->position("auto left"),
                Input::make("entity.CarModel")->title("Модель автомобиля")->horizontal(),
                Input::make("entity.CarNumber")->title("Номер автомобиля")->horizontal(),
                TextArea::make("entity.Visitors")->title("Посетители")->horizontal()->rows(5)
            ]);
        return $layout;
    }
}
