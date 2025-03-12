<?php

namespace App\DRX\Screens;

use App\DRX\Helpers\Databooks;
use App\DRX\Layouts\PermanentPass4CarListener;
use Carbon\Carbon;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;


class PermanentPass4CarScreen extends SecuritySRQScreen
{


    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermanentPass4Cars";
    public $Title = "Заявка на оформление/продление постоянного автопропуска";

    public function ExpandFields()
    {
        $ExpandFields = ['ParkingPlace'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function NewEntity()
    {
        $entity = parent::NewEntity();
        $entity['Action'] = "NewPass";
        $entity['ValidFrom'] = Carbon::today()->addDay();
        $entity['ValidTill'] = Carbon::today()->addDay(365);
        $entity['NeedPrintedPass'] = "No";
        return $entity;
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
            Input::make('entity.CarModel')->title('Модель автомобиля')
                ->horizontal()->required()
                ->disabled($this->readOnly),
            Input::make('entity.CarNumber')
                ->title('Номер автомобиля')->horizontal()
                ->required()->disabled($this->readOnly),
            Select::make('entity.ParkingPlace.Id')
                ->title('Парковочное место')->horizontal()
                ->disabled($this->readOnly)->required()
                ->help('Если в списке отображаются не все ваши парковочные места, обратитесь в администрацию БЦ')
                ->options(Databooks::GetParkingPlaces()),
            Select::make("entity.NeedPrintedPass")
                ->title('Требуется ламинированный пропуск')->horizontal()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly)
        ]);
        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);
        return $layout;
    }
}
