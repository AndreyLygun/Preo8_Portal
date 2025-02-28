<?php

namespace App\DRX\Layouts;

use App\DRX\Databooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class Pass4VisitorCarListener extends Listener
{

    protected $targets = ['entity.ParkingType'];


    protected function layouts(): iterable
    {
        $ParkingTypeOptions = [
            "PrivateParking" => "Парковка арендатора",
            "CommonParking" => "Общая парковка",
        ];
        $readOnly = $this->query->get('readOnly');
        $entity = $this->query->get('entity');
        $Private = $entity["ParkingType"] == "PrivateParking";
        return [Layout::rows([
            DateTimer::make("entity.ValidOn")
                ->title("Дата въезда.")->horizontal()
                ->format("d-m-Y")->serverFormat("d-m-Y")
                ->withQuickDates(['Завтра' => now()->addDay(), 'Послезавтра' => now()->addDay(2)])
                ->required()->disabled($readOnly),
            DateTimer::make("entity.ValidOnDateTime")
                ->title("Время въезда.")->horizontal()->placeholder('Укажите время')
                ->format("H:i")->serverFormat("d-m-Y H:i")->format24hr()
                ->noCalendar()->required()->disabled($readOnly),
            Select::make("entity.ParkingType")
                ->title("Вид парковочного места")->horizontal()
                ->help("Вы можете предоставить гостю свою парковку или запросить место на общей парковке")
                ->disabled($readOnly)
                ->options($ParkingTypeOptions),
            Select::make('entity.ParkingFloor.Id')
                ->title('Парковка (уровень)')->horizontal()
                ->options(Databooks::GetSites('ParkingSite'))
                ->required($Private)->canSee($Private)
                ->disabled($readOnly),
            Input::make("entity.ParkingPlace")->required()
                ->title('Парковочное место')->horizontal()
                ->required($Private)->canSee($Private)
                ->readonly($readOnly),
            Input::make("entity.Duration")
                ->title("Продолжительность парковки (час)")->horizontal()
                ->value(1)->type('number')->min(0)->max(8)->step(0.5)
                ->required(!$Private)->canSee(!$Private)
                ->disabled($readOnly)
                ->help("Парковка свыше 3 часов должна быть оплачена по тарифам бизнес-центра"),
        ])->title("Дата и парковка")];
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        $repository->set('entity.ParkingType', $request->input('entity.ParkingType'));
//        $repository->set('entity.ParkingFloor.Id', $request->get('entity.ParkingFloor.Id'));
//        $repository->set('entity.ParkingPlace', $request->get('entity.ParkingPlace'));
        return $repository;
    }
}
