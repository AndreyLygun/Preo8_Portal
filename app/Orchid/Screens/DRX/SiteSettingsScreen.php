<?php

namespace App\Orchid\Screens\DRX;

# Страница, отображающая служебные справочники - интервал времени для завоза-вывоза, места загрузки-разгрузки, секции БЦ
# Информация считывается из JSON-файлов, хранящихся на веб-сервере
# Кнопка "Обновить" запрашивает состояние справочников с сервера DRX

use App\DRX\Databooks;
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
        $result['ParkingSites'] = Databooks::GetSites('ParkingSite');
        $result['TimeSpans'] = Databooks::GetTimeSpans();
        return $result;
    }

    public function name(): ?string
    {
        return 'Информация о БЦ';
    }

    public function layout(): iterable
    {
        $Layout = [
            Layout::rows([
                Label::make('')->title('Списке мест можно изменить в Directum RX. После изменения зайдите на эту страницу снова.'),
                ExtendedMatrix::make("PassSites")->readonly()->title("Места входа-выхода")->columns(['Название'=>'Value']),
                ExtendedMatrix::make("LoadingSites")->readonly()->title("Места загрузки-разгрузки")->columns(['Название'=>'Value']),
                ExtendedMatrix::make("SectionSites")->readonly()->title("Уровни парковки")->columns(['Название'=>'Value']),
                ExtendedMatrix::make("ParkingSites")->readonly()->title("Уровни парковки")->columns(['Название'=>'Value']),
            ])->title("Места в бизнес-центре"),
            Layout::rows([
                ExtendedMatrix::make("TimeSpans")->readonly()->title("Места")->columns(['Значение'=>'Value']),
            ])->title("Время использования лифтов"),

        ];
        return $Layout;
    }
}
