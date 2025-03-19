<?php

namespace App\DRX\Screens;

# Страница, отображающая служебные справочники - интервал времени для завоза-вывоза, места загрузки-разгрузки, секции БЦ
# Информация считывается из JSON-файлов, хранящихся на веб-сервере
# Кнопка "Обновить" запрашивает состояние справочников с сервера DRX

use App\DRX\ExtendedMatrix;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Label;
use App\DRX\DRXClient;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;


class SiteSettingsScreen extends Screen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $DRXEntityType = "IServiceRequestsBaseSRQs";

    //Возвращает список ссылочных свойств (через запятую), которые должны быть получены в запросе
    public function ExpandFields(): string
    {
        return "Author,DocumentKind";
    }


    public function query(): iterable
    {
//        $sites = Cache::rememberForever('Sites', function() use ($odata) {
//            return $odata->from('IServiceRequestsSites')->get();
//        });
    }

    public function name(): ?string
    {
        return 'Информация о БЦ';
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Обновить данные')->method('Update')
        ];
    }

    public function Update() {
        $IdNameFunction = function(array $value) {
            return [$value['Id']=>$value['Name']];
        };
        $odata = new DRXClient();
        $result['LoadingSites'] = $odata->from('IServiceRequestsSites')->where('Type', 'Loading')->get()->mapWithKeys($IdNameFunction)->toArray();
        $result['SectionSites'] = $odata->from('IServiceRequestsSites')->where('Type', 'Section')->get()->mapWithKeys($IdNameFunction)->toArray();
        $result['TimeSpans'] = $odata->from('IServiceRequestsTimeSpans')->get()->mapWithKeys($IdNameFunction);
        Storage::write('settings.json', json_encode($result));
    }

    public function layout(): iterable
    {
//        dd($this->query());
        if (isset($this->query()['error'])) {
            return [
                Layout::rows([
                    Label::make('error.message'),
                    Label::make('error.errnum')
                ])->title('Ошибка!')
            ];
        }
        $Layout = [
            Layout::rows([
                ExtendedMatrix::make("LoadingSites")->readonly()->title("Места разгрузки")->columns(['Значение'=>'Value']),
                ExtendedMatrix::make("SectionSites")->readonly()->title("Секции здания"),
                ExtendedMatrix::make("TimeSpans")->readonly()->title("Периоды перемещения крупногабаритных грузов")
            ])
        ];
        return $Layout;
    }
}
