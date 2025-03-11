<?php


namespace App\DRX;

// Класс для получения из Directum справочной информации. В основном - нечасто меняющейся (поэтому кэшируем)

use App\Models\DrxAccount;
use Illuminate\Support\Facades\Cache;

class Databooks
{
    // Список мест, отфильтрованных по типу
    public static function GetSites($type = null, $odata = null)
    {
        $odata = $odata ?? (new DRXClient());
        $Sites = Cache::rememberForever('Sites', function () use ($odata) {
            return $odata->from('IServiceRequestsSites')->get();
        });
        if ($type) return collect($Sites)->where('Type', $type)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
        return collect($Sites)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
    }

    // Возвращает логин арендатора в Directum
    public static function GetLogin()
    {
        return DrxAccount::find(auth()->user()->drx_account_id)->DRX_Login;
    }

    public static function GetParkingPlaces($odata = null)
    {
        $odata = $odata ?? (new DRXClient());
        $places = $odata->from('IServiceRequestsSites')
            ->select('Id, Name')
            ->where('Renter/Login/LoginName', self::GetLogin())
            ->where('Type', 'ParkingSite')
            ->order('Index')
            ->get();
        return collect($places)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
    }

    // Список периодов времени
    public static function GetTimeSpans($odata = null)
    {
        $odata = $odata ?? (new DRXClient());
        $TimeSpans = Cache::rememberForever('TimeSpans', function () use ($odata) {
            return $odata->from('IServiceRequestsTimeSpans')->get();
        });
        return collect($TimeSpans)->mapWithKeys(fn($v) => [$v['Id'] => $v['Name']]);
    }

    public static function GetYesNo()
    {
        return collect(["Yes" => "Да", "No" => "Нет"]);
    }

}
