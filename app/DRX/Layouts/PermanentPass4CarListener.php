<?php

namespace App\DRX\Layouts;

use App\DRX\Databooks;
use App\DRX\ExtendedSelect;
use Illuminate\Http\Request;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class PermanentPass4CarListener extends Listener
{

    protected $targets = ['entity.Action'];

    protected function layouts(): iterable
    {
        $PermanentPass4CarActions = [
            "NewPass" => "Оформление нового пропуска",
            "ContinuePass" => "Продление пропуска",
            "StopPass" => "Блокировка пропуска"
        ];
        return [Layout::rows([
            Select::make('entity.Action')
                ->title("Требуемое действие")->horizontal()
                ->options($PermanentPass4CarActions)
                ->empty("Выберите тип действия")->required()->hr(),
            Input::make('entity.CarModel')->title('Модель автомобиля')->horizontal()->required(),
            Input::make('entity.CarNumber')->title('Номер автомобиля')->horizontal()->required(),
            ExtendedSelect::make('entity.ParkingFloor.Id')->required()->intValue(true)
                ->title('Парковка (уровень)')->horizontal()->empty('Укажите этаж')
                ->options(Databooks::GetSites('ParkingSite')),
            Input::make("entity.ParkingPlace")->required()
                ->title('Парковочное место')->horizontal(),
            Select::make("entity.NeedPrintedPass")
                ->title('Требуется ламинированный пропуск')->horizontal()->value('No')
                ->options(Databooks::GetYesNo())->canSee(($this->query->get("entity.Action")!="StopPass"))
                ->empty('')
        ])];
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        $repository->set($request->all());
        return $repository;
    }
}
