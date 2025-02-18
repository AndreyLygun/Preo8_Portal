<?php

namespace App\Orchid\Screens\DRX;


use App\DRX\Databooks;
use App\DRX\ExtendedSelect;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


class PermanentPass4CarScreen extends SecuritySRQScreen
{

    public function ExpandFields()
    {
        $ExpandFields = ['ParkingFloor'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermanentPass4Cars";
    public $Title = "Заявка на оформление/блокировку постоянного автопропуска";

    // Описывает макет экрана
    public function layout(): iterable
    {
        $PermanentPass4CarActions = [
            "NewPass" => "Оформление нового пропуска",
            "ContinuePass" => "Продление пропуска",
            "StopPass" => "Блокировка пропуска"
        ];

        $layout = parent::layout();
        $layout[] = Layout::rows([
            Select::make('entity.Action')
                ->title("Требуемое действие")->horizontal()
                ->options($PermanentPass4CarActions)
                ->empty("Выберите тип действия")->required()->hr(),
            Input::make('entity.CarModel')->title('Модель автомобиля')->horizontal()->required(),
            Input::make('entity.CarNumber')->title('Номер автомобиля')->horizontal()->required(),
            ExtendedSelect::make('entity.ParkingFloor.Id')->required()->intValue(true)
                ->title('Парковка (уровень)')->horizontal()
                ->options(Databooks::GetSites('ParkingSite')),
            Input::make("entity.ParkingPlace")->required()
                ->title('Парковочное место')->horizontal(),
            Select::make("entity.NeedPrintedPass")->title('Требуется ламинированный пропуск')->horizontal()->options(Databooks::GetYesNo())->empty(''),
            TextArea::make('note')->title("Примечание")->thorizontal()->rows(10)->horizontal()
        ]);
        return $layout;
    }
}
