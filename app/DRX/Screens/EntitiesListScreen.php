<?php

namespace App\DRX\Screens;

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
    private $DRXEntityType = "IServiceRequestsBaseSRQs";
    public $pagination;
    public $error;
    public $entites;

    //Возвращает список ссылочных свойств (через запятую), которые должны быть получены в запросе
    public function ExpandFields(): string
    {
        return "Author,DocumentKind";
    }


    public function query(): iterable
    {
        $odata = new DRXClient();
        $result = $odata->getList($this->DRXEntityType, $this->ExpandFields(), '-Created', 30);
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
//                Link::make("...на разовый пропуск")->route("drx.Pass4Visitors"),
                Link::make("...на постоянный пропуск")->route("drx.PermanentPass4Employee"),
//                Link::make("...на блокировку постоянного пропуска")->route("drx.StopPermanentPass4Employee"),
//                Link::make("...на дополнительный доступ")->route("drx.Permission4Employee"),
                Link::make("...на выполнение работ")->route("drx.WorkPermission")->hr(),
                Link::make("...на гостевой автопропуск")->route("drx.Pass4VisitorCar")->hr(),
                Link::make("...на оформление постоянного автопропуска")->route("drx.PermanentPass4Car"),
                Link::make("...на блокировку постоянного автопропуска")->route("drx.StopPermanentPass4Car")->hr(),
                Link::make("...на разовый ввоз-вывоз ТМЦ")->route("drx.Pass4AssetsMoving"),
                Link::make("...на внутреннее перемещение ТМЦ")->route("drx.Pass4AssetsInternalMoving"),
//                Link::make("...на регулярное перемещение ТМЦ")->route("drx.Pass4PermanentAssetsMoving"),
            ])
        ];
    }

    public function layout(): iterable
    {
        if (isset($this->error)) {
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
                    ->render(fn($item) => __($item["RequestState"]))
                    ->cssClass(fn($item) => $item["RequestState"])->sort()
            ])
        ];
        if ($pagination = ($this->pagination ?? false) and ($pagination['last_page'] > 1)) {
           $Layout[] = Layout::view("pagination", ["pagination" => $pagination]);
        };
        return $Layout;
    }
}
