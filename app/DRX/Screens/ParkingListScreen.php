<?php

namespace App\DRX\Screens;

use App\Models\DrxAccount;
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
    public $entites;

    //Возвращает список ссылочных свойств (через запятую), которые должны быть получены в запросе
    public function ExpandFields(): string
    {
        return "Renter";
    }


    public function query(): iterable
    {

        $odata = new DRXClient();
        $LoginName = DrxAccount::find(auth()->user()->drx_account_id)->DRX_Login;
//        $parkingPlaces = $odata->from("IServiceRequestsSites")
//            ->where('Type', 'ParkingSite')
//            ->where('Renter/Login/LoginName', $LoginName)
//            ->order('Index')->get();
        $permanentPasses = $odata->from('IServiceRequestsPermanentPass4Cars')
            ->expand('ParkingPlace')
//            ->where('ValidTill', 'ge', Carbon::today())
            ->order('ValidTill')->get();
        $visitorPasses = $odata->from('IServiceRequestsPass4VisitorCars')
            ->expand('ParkingPlace')
            ->order('ValidTill')->get();
//        dd($permanentPasses, $visitorPasses);
        return ['PermanentPasses' => $permanentPasses, 'VisitorPasses' => $visitorPasses];
    }

    public function name(): ?string
    {
        return 'Парковочные места и автопропуска';
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        $Layout[] = Layout::table("VisitorPasses", [
                ExtendedTD::make("Id", "№")
                    ->render(fn($item) => $item["Id"])
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->width("60"),
                ExtendedTD::make("Subject", "Содержание")
                    ->render(fn($item) => "<a href='/srq/IPermanentPass4CarDto/{$item["Id"]}'>{$item["Subject"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort()->width("50%"),
            ExtendedTD::make("ValidOn", "Дата въезда")
//                ->render(fn($item) => "<a href='/srq/IPermanentPass4CarDto/{$item["Id"]}'>{$item["ValidOn"]}</a>")
                ->cssClass(fn($item) => $item["RequestState"])
                ->sort()->width("50%"),

                ExtendedTD::make("RequestState", "Статус")
                    ->render(fn($item) => __($item["RequestState"]))
                    ->cssClass(fn($item) => $item["RequestState"])->sort()
            ])->title('Гостевые пропуска на парковку');
        $Layout[] = Layout::table("PermanentPasses", [
                ExtendedTD::make("Id", "№")
                    ->render(fn($item) => $item["Id"])
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->width("60"),
                ExtendedTD::make("Subject", "Содержание")
                    ->render(fn($item) => "<a href='/srq/IPermanentPass4CarDto/{$item["Id"]}'>{$item["Subject"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort()->width("50%"),
                ExtendedTD::make("ParkingPlace", "Парковочное место")
//                    ->render(fn($item) => "<a href='/srq/IPermanentPass4CarDto/{$item["Id"]}'>{isset($item['ParkingPlace']['Name''])}</a>")
                    ->render(fn($item) => "<a href='/srq/IPermanentPass4CarDto/{$item["Id"]}'>123</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort()->width("50%"),
                ExtendedTD::make("RequestState", "Статус")
                    ->render(fn($item) => __($item["RequestState"]))
                    ->cssClass(fn($item) => $item["RequestState"])->sort()
            ])->title('Постоянные пропуска на парковку');
        return $Layout;
    }
}
