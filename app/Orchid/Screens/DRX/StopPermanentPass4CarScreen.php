<?php

namespace App\Orchid\Screens\DRX;


use Carbon\Carbon;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


class StopPermanentPass4CarScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsStopPermanentPass4Employees";
    public $Title = "Блокировка постоянного автопропуска";

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        //array_pop($layout);
        $layout[] = Layout::rows([
            Input::make("entity.CarNumber")->title("Госномер")
                ->horizontal()->help('Госномер автомобиля, пропуск для которого нужно заблокировать.'),
        ])->title('Сведения об автомобиле');
        return $layout;
    }
}
