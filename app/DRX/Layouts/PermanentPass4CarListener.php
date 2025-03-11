<?php

namespace App\DRX\Layouts;

use App\DRX\Databooks;
use App\DRX\ExtendedSelect;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Orchid\Screen\Fields\DateTimer;
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
        ];
        $readOnly = $this->query->get('readOnly');
        $entity = $this->query->get('entity');
        $action = $entity["Action"] ?? "NewPass";
        return [
            Layout::rows([
                Input::make('entity.CarModel')->title('Модель автомобиля')
                    ->horizontal()->required()
                    ->disabled($readOnly),
                Input::make('entity.CarNumber')
                    ->title('Номер автомобиля')->horizontal()
                    ->required()->disabled($readOnly),
                Select::make('entity.ParkingPlace.Id')
                    ->title('Парковочное место')->horizontal()
                    ->disabled($readOnly)->required()
                    ->help('Если в списке отображаются не все ваши парковочные места, обратитесь в администрацию БЦ')
                    ->options(Databooks::GetParkingPlaces()),
                Select::make("entity.NeedPrintedPass")
                    ->title('Требуется ламинированный пропуск')->horizontal()
                    ->options(Databooks::GetYesNo())->canSee(($this->query->get("entity.Action") != "StopPass"))
                    ->disabled($readOnly)
            ])
        ];
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        $repository->set($request->all());
        return $repository;
    }
}
