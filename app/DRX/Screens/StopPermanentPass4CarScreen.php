<?php

namespace App\DRX\Screens;


use App\DRX\Databooks;
use Carbon\Carbon;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


class StopPermanentPass4CarScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsStopPermanentPass4Cars";
    public $Title = "Блокировка постоянного автопропуска";

    public function ExpandFields()
    {
        $ExpandFields = ['ParkingFloor'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $readOnly = $this->readOnly;
        $layout = parent::layout();
        //array_pop($layout);
        $layout[] = Layout::rows([
            DateTimer::make('entity.ValidTill')
                ->title('Заблокировать пропуск с')->horizontal()->hr()
                ->format('Y-m-d')->serverFormat('Y-m-d')
                ->enableTime(false)->min(Carbon::today())
                ->required()->disabled($readOnly),
            Input::make('entity.CarModel')->title('Модель автомобиля')
                ->horizontal()->required()
                ->disabled($readOnly),
            Input::make('entity.CarNumber')
                ->title('Номер автомобиля')->horizontal()
                ->required()->disabled($readOnly),
            Select::make('entity.ParkingFloor.Id')->required()
                ->title('Парковка (уровень)')->horizontal()->empty('Укажите этаж')
                ->options(Databooks::GetSites('ParkingSite')),
            Input::make("entity.ParkingPlace")->required()
                ->title('Парковочное место')->horizontal()
                ->disabled($readOnly),
        ])->title('Сведения об автомобиле');
        $layout[] = Layout::rows([TextArea::make('entity.Note')->title("Примечание")->rows(10)->horizontal()]);
        return $layout;
    }
}
