<?php

namespace App\Orchid\Screens\DRX;

use Illuminate\Support\Facades\Log;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use App\DRX\DRXClient;
use App\DRX\ExtendedTD;
use Orchid\Support\Facades\Layout;
use Carbon\Carbon;


class EntitiesListScreen extends Screen
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
        Log::debug("Начало query() для списка");
        $odata = new DRXClient();
        $result = $odata->getList($this->DRXEntityType, $this->ExpandFields(), '-Created', 10);
        Log::debug("Конец query() для списка");
        return $result;
    }

    public function name(): ?string
    {
        return 'Все заявки';
    }

    public function commandBar(): iterable
    {
        return [
            DropDown::make("Создать заявку...")->list([
                Link::make("...на разовый пропуск")->route("drx.GuestPassSRQDto"),
                Link::make("...на разовый автопропуск")->route("drx.AutoPassSRQDto"),
                Link::make("...на перемещение ТМЦ")->route("drx.Pass4AssetsMovingSRQDto")
            ])
        ];
    }

    public function layout(): iterable
    {
        if (isset($this->query()['error'])) {
            return [
                Layout::rows([
                    Label::make('error.message'),
                    Label::make('error.errnum')
                ])->title('Ошибка!')
            ];
        }
        $Layout = [
            Layout::table("entities", [
                ExtendedTD::make("Id", "№")
                    ->render(fn($item) => $item["Id"])
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->width("60"),
                ExtendedTD::make("DocumentKind.Name", "Вид заявки")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["DocumentKind"]["Name"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort(),
                ExtendedTD::make("DocumentKind.Name", "Автор")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Creator"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort(),
                ExtendedTD::make("Subject", "Содержание")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Subject"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort()->width("50%"),
                ExtendedTD::make("Created", "Cоздан")
                    ->render(fn($item) => Carbon::parse($item["Created"])->format('d/m/y'))
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->sort(),
                ExtendedTD::make("RequestState", "Статус")
                    ->render(fn($item) => config('srq.RequestState')[$item["RequestState"]])
                    ->cssClass(fn($item) => $item["RequestState"])->sort()
            ])
        ];
        if ($pagination = ($this->query()['pagination'] ?? false) and ($pagination['last_page'] > 1)) {
            $Layout[] = Layout::view("Pagination", ["pagination" => $pagination]);
        };
        return $Layout;
    }
}
