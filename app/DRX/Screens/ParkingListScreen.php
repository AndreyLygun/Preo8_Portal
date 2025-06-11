<?php

namespace App\DRX\Screens;

use App\DRX\Helpers\Databooks;
use App\DRX\Helpers\Functions;
use App\DRX\Screens\Cars\ChangePermanentParkingScreen;
use App\DRX\Screens\Cars\VisitorCarScreen;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use App\DRX\DRXClient;
use App\DRX\ExtendedTD;
use Orchid\Support\Facades\Layout;
use Carbon\Carbon;


class ParkingListScreen extends Screen
{
    //Возвращает список ссылочных свойств (через запятую), которые должны быть получены в запросе
    public function ExpandFields(): string
    {
        return "Renter";
    }

    public function query(): iterable
    {
        $odata = new DRXClient();
        $visitorPasses = $odata->from('IServiceRequestsPass4VisitorCars')
            ->expand('ParkingPlace')
            ->order('ValidTill', 'desc')
            ->get()->where('ValidTill', '>', Carbon::today()->addDays(-1));

        $parkingPlaces = $odata->from('IServiceRequestsParkingPlaces')
            ->expand('Renter,Cars,Drivers')
            ->where('Renter/Login/LoginName', '=', Databooks::GetRenterLogin())
            ->order('Index')
            ->get()->toArray();
        foreach ($parkingPlaces as $key => $place) {
            $parkingPlaces[$key]['CarsString'] = join("<br>", array_map(fn($car) => $car['Model'] . ' / ' . $car['Number'], $place["Cars"]));
            $parkingPlaces[$key]['DriversString'] = join("<br>", array_map(fn($driver) => $driver['Name'], $place["Drivers"]));
        }
        //dd($parkingPlaces);

        return ['VisitorPasses' => $visitorPasses, 'ParkingPlaces' => $parkingPlaces];

    }

    public function name(): ?string
    {
        return 'Парковочные места и автопропуска';
    }

    public function commandBar(): iterable
    {
        $commands = [];
        if ((Auth::user()->hasAccess('platform.requests.' . class_basename(VisitorCarScreen::class))))
            $commands[] = Link::make("Создать заявку на гостевую парковку")->route("drx.VisitorCarScreen")->class('btn btn-primary');
        return $commands;
    }


    public function layout(): iterable
    {
        $Layout = [];
        if (Functions::UserCanCreateRequest(VisitorCarScreen::class))
            $Layout[] = Layout::table("VisitorPasses", [
                ExtendedTD::make("Id", "№")
                    ->render(fn($item) => $item["Id"])
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->width("100"),
                ExtendedTD::make("Subject", "Автомобиль")
                    ->render(fn($item) => "<a href='/srq/IPass4VisitorCarDto/{$item["Id"]}'>{$item["Subject"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort()->width("40%"),
                ExtendedTD::make("Creator", "Автор")
                    ->render(fn($item) => $item["Creator"])
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort(),
                ExtendedTD::make("ValidOn", "Дата въезда")
                    ->render(fn($item) => Carbon::parse($item["ValidOn"])->format('d-m-Y'))
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort()->width("30%"),
                ExtendedTD::make("ParkingPlace", "Парковочное место")
                    ->render(fn($item) => $item['ParkingType'] == 'PrivateParking' ? ($item['ParkingPlace']['Name'] ?? "") : 'Гостевая стоянка')
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort()->width("20%"),
                ExtendedTD::make("RequestState", "Статус")
                    ->render(fn($item) => __($item["RequestState"]))
                    ->cssClass(fn($item) => $item["RequestState"])->sort()
            ])->title('Заявки на гостевую парковку (сегодня и на последующие дни)');

        if (Functions::UserCanCreateRequest(ChangePermanentParkingScreen::class))
            $Layout[] = Layout::table('ParkingPlaces', [
                ExtendedTD::make("Name", "Название/<br>электронный пропуск")
                    ->render(fn($item) => $item["Name"] . '<br>' . ($item['NfcNumber'] ? '№ ' . $item['NfcNumber'] : 'Пропуск не оформлен'))
//                ->cssClass(fn($item) => $item["RequestState"])
                    ->width("30%"),
                ExtendedTD::make("Сars", "Автомобили")
                    ->render(fn($item) => $item['CarsString'])
                    ->width("30%"),
                ExtendedTD::make("Drivers", "Водители")
                    ->render(fn($item) => $item['DriversString'])
                    ->width("30%"),
                ExtendedTD::make("", "")
                    ->render(fn($item) => $item['NfcNumber']
                        ? Link::make('Внести изменения')->route('drx.ChangePermanentParkingScreen', ['parkingplace' => $item['Id']])->class('btn btn-primary')
                        : Link::make('Оформить пропуск')->route('drx.ChangePermanentParkingScreen', ['parkingplace' => $item['Id']])->class('btn btn-primary'))
                    ->width("10%")
            ])->title('Парковочные места');

        return $Layout;
    }
}
