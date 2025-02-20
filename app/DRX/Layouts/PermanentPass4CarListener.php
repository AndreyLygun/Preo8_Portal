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
        return [Layout::rows([
            Select::make('entity.Action')
                ->title("Требуемое действие")->horizontal()
                ->options($PermanentPass4CarActions)
                ->required()->disabled($readOnly),
            DateTimer::make('entity.ValidFrom')
                ->title('Действует с')->horizontal()
                ->format('Y-m-d')->serverFormat('Y-m-d')
                ->enableTime(false)->min(Carbon::today()->addDay())
                ->required($action == "NewPass")->disabled($readOnly)
                ->canSee($action == "NewPass"),
            DateTimer::make('entity.ValidTill')
                ->title('Действует до')->horizontal()->hr()
                ->format('Y-m-d')->serverFormat('Y-m-d')
                ->enableTime(false)->max(Carbon::today()->addDay(365))->min(Carbon::today())
                ->required()->disabled($readOnly)
                ->canSee($action != "StopPass"),
            Input::make('entity.CarModel')->title('Модель автомобиля')
                ->horizontal()->required()
                ->disabled($readOnly),
            Input::make('entity.CarNumber')
                ->title('Номер автомобиля')->horizontal()
                ->required()->disabled($readOnly),
            ExtendedSelect::make('entity.ParkingFloor.Id')->required()->intValue(true)
                ->title('Парковка (уровень)')->horizontal()->disabled($readOnly)
                ->options(Databooks::GetSites('ParkingSite')),
            Input::make("entity.ParkingPlace")->required()
                ->title('Парковочное место')->horizontal()
                ->disabled($readOnly),
            Select::make("entity.NeedPrintedPass")
                ->title('Требуется ламинированный пропуск')->horizontal()
                ->options(Databooks::GetYesNo())->canSee(($this->query->get("entity.Action") != "StopPass"))
                ->disabled($readOnly)
        ])];
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        $repository->set($request->all());
        return $repository;
    }
}
