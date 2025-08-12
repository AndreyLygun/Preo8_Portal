<?php

namespace App\DRX\Screens;

# Страница, отображающая служебные справочники - интервал времени для завоза-вывоза, места загрузки-разгрузки, секции БЦ
# Информация считывается из JSON-файлов, хранящихся на веб-сервере
# Кнопка "Обновить" запрашивает состояние справочников с сервера DRX

use App\DRX\Helpers\Databooks;
use App\DRX\ExtendedMatrix;
use Illuminate\Support\Facades\Cache;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;


class SiteSettingsScreen extends Screen
{
    public function query(): iterable
    {
        Cache::clear();
        $result['PassSites'] = Databooks::GetSites('Pass');
        $result['LoadingSites'] = Databooks::GetSites('Loading');
        $result['SectionSites'] = Databooks::GetSites('RenterSite');
//        $result['ParkingSites'] = Databooks::GetSites('ParkingSite');
        $result['TimeSpans'] = Databooks::GetTimeSpans();
        return $result;
    }

    public function name(): ?string
    {
        return 'Информация о БЦ';
    }

    public function permission(): ?iterable
    {
        return ['platform.portal.renters'];
    }

    public function layout(): iterable
    {
        $Layout = [
            Layout::rows([
                Label::make('')->title('Список мест можно изменить в Directum RX. После изменения зайдите на эту страницу снова.'),
                ExtendedMatrix::make("SectionSites")->readonly()->columns(['Этажи здания'=>'Value']),
                ExtendedMatrix::make("PassSites")->readonly()->columns(['Места входа-выхода'=>'Value']),
                ExtendedMatrix::make("LoadingSites")->readonly()->columns(['Места загрузки-разгрузки'=>'Value']),
//                ExtendedMatrix::make("ParkingSites")->readonly()->columns(['Уровни парковки'=>'Value']),
            ])->title("Места в бизнес-центре"),
            Layout::rows([
                Label::make('')->title('Список интервалов можно изменить в Directum RX. После изменения зайдите на эту страницу снова.'),
                ExtendedMatrix::make("TimeSpans")->readonly()->columns(['Интервал'=>'Value']),
            ])->title("Время использования лифтов"),

        ];
        return $Layout;
    }
}
