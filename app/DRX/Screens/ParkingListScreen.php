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

//        dd($permanentPasses, $visitorPasses);
        return ['PermanentPasses' => $permanentPasses, 'VisitorPasses' => $visitorPasses];
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
                Link::make("...на постоянную парковку")->route("drx.PermanentPass4Car"),
                Link::make("...на блокировку постоянного автопропуска")->route("drx.StopPermanentPass4Car")->hr(),
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

        $Layout[] = Layout::table("PermanentPasses", [
            ExtendedTD::make("Id", "№")
                ->render(fn($item) => $item["Id"])
                ->cssClass(fn($item) => $item["RequestState"])
                ->width("100"),
            ExtendedTD::make("Subject", "Автомобиль")
                ->render(fn($item) => "<a href='/srq/IPermanentPass4CarDto/{$item["Id"]}'>{$item["CarModel"]} / {$item["CarNumber"]}</a>")
                ->cssClass(fn($item) => $item["RequestState"])
                ->sort()->width("50%"),
            ExtendedTD::make("ParkingPlace", "Парковочное место")
                ->render(fn($item) => $item['ParkingPlace']['Name'] ?? '')
                ->cssClass(fn($item) => $item["RequestState"])
                ->sort()->width("50%"),
            ExtendedTD::make("RequestState", "Статус")
                ->render(fn($item) => __($item["RequestState"]))
                ->cssClass(fn($item) => $item["RequestState"])->sort()
        ])->title('Постоянная парковка');
        return $Layout;
    }
}
