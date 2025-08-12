<?php


namespace App\DRX\Helpers;

// Класс для получения из Directum справочной информации. В основном - нечасто меняющейся (поэтому кэшируем)

use App\Models\DrxAccount;
use App\DRX\DRXClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Databooks
{
    // Список мест, отфильтрованных по типу
    public static function GetSites($type = null, $odata = null)
    {
        $odata = $odata ?? (new DRXClient());
        $Sites = Cache::rememberForever('Sites', function () use ($odata) {
            return $odata->from('IServiceRequestsSites')->where('Status', 'Active')->get();
        });
        if ($type) return collect($Sites)->where('Type', $type)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
        return collect($Sites)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
    }

    // Возвращает логин арендатора в Directum
    public static function GetRenterLogin()
    {
        return DrxAccount::find(auth()->user()->drx_account_id)->DRX_Login;
    }

//    public static function GetParkingPlaces($odata = null)
//    {
//        $odata = $odata ?? (new DRXClient());
//        $places = $odata->from('IServiceRequestsParkingPlaces')
//            ->select('Id, Name')
//            ->where('Renter/Login/LoginName', self::GetRenterLogin())
//            ->order('Index')
//            ->get();
//        return collect($places)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
//    }

    // Список периодов времени
    public static function GetTimeSpans($odata = null)
    {
        $odata = $odata ?? (new DRXClient());
        $TimeSpans = Cache::rememberForever('TimeSpans', function () use ($odata) {
            return $odata->from('IServiceRequestsTimeSpans')->where('Status', 'Active')->get();
        });
        return collect($TimeSpans)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
    }

    public static function GetYesNo()
    {
        return collect(["Yes" => "Да", "No" => "Нет"]);
    }

    public static function GetMovingDirection()
    {
        return collect([
            'MovingIn' => 'Ввоз',
            'MovingOut' => 'Вывоз',
            'CarryingIn' => 'Внос (через турникеты)',
            'CarryingOut' => 'Вынос (через турникеты)',
            'GarbageOut' => 'Вывоз мусора'
        ]);
    }


}
