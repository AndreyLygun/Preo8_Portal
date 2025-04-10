<?php

namespace App\DRX\Screens;

use App\DRX\Helpers\Databooks;
use App\Models\DrxAccount;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\DropDown;
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
//        $LoginName = Databooks::GetRenterLogin();
//        $parkingPlaces = $odata->from("IServiceRequestsSites")
//            ->where('Type', 'ParkingSite')
//            ->where('Renter/Login/LoginName', $LoginName)
//            ->order('Index')->get();
        $visitorPasses = $odata->from('IServiceRequestsPass4VisitorCars')
            ->expand('ParkingPlace')
//            ->where('Creator', '=', "Андрей Лыгун")
//            ->where('ValidTill', '>', Carbon::today())
            ->order('ValidTill', 'desc')
            ->get()->where('ValidTill', '>', Carbon::today()->addDays(-1));
        $permanentPasses = $odata->from('IServiceRequestsPermanentPass4Cars')
            ->expand('ParkingPlace')
//            ->where('ValidTill', '>', date('y-m-d'))
            ->order('ValidTill', 'desc')
            ->get()->where('ValidTill', '>', Carbon::today()->addDays(-1));;

        $parkingPlaces = $odata->from('IServiceRequestsParkingPlaces')
            ->expand('Renter,Cars,Drivers')
            ->where('Renter/Login/LoginName', '=', Databooks::GetRenterLogin())
            ->order('Index')
            ->get()->toArray();
        foreach($parkingPlaces as $key => $place) {
            $parkingPlaces[$key]['CarsString'] = join("<br>", array_map(fn($car) => $car['Model'] . ' / ' . $car['Number'], $place["Cars"]));
            $parkingPlaces[$key]['DriversString'] = join("<br>", array_map(fn($driver) => $driver['Name'], $place["Drivers"]));
        }
        //dd($parkingPlaces);

        return ['PermanentPasses' => $permanentPasses, 'VisitorPasses' => $visitorPasses, 'ParkingPlaces' => $parkingPlaces];

    }

    public function name(): ?string
    {
        return 'Парковочные места и автопропуска';
    }

    public function commandBar(): iterable
    {
        return [
            DropDown::make("Создать заявку...")->list([
                Link::make("...на гостевую парковку")->route("drx.Pass4VisitorCar")->hr(),
//                Link::make("...на постоянную парковку")->route("drx.PermanentPass4Car"),
            ])
        ];
    }


    public function layout(): iterable
    {

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
        ])->title('Гостевая парковка (сегодня и на последующие дни)');

        $Layout[] = Layout::table('ParkingPlaces', [
            ExtendedTD::make("Name", "Название/<br>электронный пропуск")
                ->render(fn($item) => $item["Name"] . '<br>' . ($item['NfcNumber']?'№ ' . $item['NfcNumber'] : 'Пропуск не оформлен'))
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
                    ? Link::make('Внести изменения')->route('drx.ChangePermanentParking', ['parkingplace' => $item['Id']])->class('btn btn-primary')
                    : Link::make('Оформить пропуск')->route('drx.ChangePermanentParking', ['parkingplace' => $item['Id']])->class('btn btn-primary'))
                ->width("10%")
        ])->title('Парковочные места');

//        $Layout[] = Layout::table("PermanentPasses", [
//            ExtendedTD::make("Id", "№")
//                ->render(fn($item) => $item["Id"])
//                ->cssClass(fn($item) => $item["RequestState"])
//                ->width("100"),
//            ExtendedTD::make("ParkingPlace", "Парковочное место")
//                ->render(fn($item) => $item['ParkingPlace']['Name'] ?? '')
//                ->cssClass(fn($item) => $item["RequestState"])
//                ->sort()->width("50%"),
//            ExtendedTD::make("Subject", "Автомобили")
//                ->render(fn($item) => $item["CarsString"])
//                ->cssClass(fn($item) => $item["RequestState"])
//                ->sort()->width("50%"),
//            ExtendedTD::make("RequestState", "Статус")
//                ->render(fn($item) => __($item["RequestState"]))
//                ->cssClass(fn($item) => $item["RequestState"])->sort()
//        ])->title('Постоянная парковка');
        return $Layout;
    }
}
