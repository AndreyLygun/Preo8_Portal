<?php

namespace App\DRX\Screens;

use Illuminate\Support\Facades\Auth;
use App\DRX\Helpers\Functions;
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
        $commands = [];
        foreach (config('srq.requests') as $kind) {
            if (!Functions::UserHasAccessTo($kind)) continue;
            $properties = get_class_vars($kind);
            $commandTitle = "...на " . mb_lcfirst($properties['Title']);
            $commands[] = Link::make($commandTitle)->route('drx.' . class_basename($kind));
        }
        return [
            DropDown::make("Создать заявку...")->list($commands)
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
        $DocumentKinds = [
            'InOutAssets' => 'Разовый ввоз-вывоз ТМЦ',
            'InternalMovingAssets' => 'Внутреннее перемещение ТМЦ',
        ];
        $Layout = [
            Layout::table("entities", [
                ExtendedTD::make("Id", "№")
                    ->render(fn($item) => $item["Id"])
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->width("100"),
                ExtendedTD::make("DocumentKind.Name", "Вид заявки")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["DocumentKind"]["Name"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
//                    ->filter(Select::make()->options($DocumentKinds))
                    ->sort(),
                ExtendedTD::make("Creator", "Автор")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Creator"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
//                    ->filter(ServiceRequestsFilter::class)
                    ->sort(),
                ExtendedTD::make("Subject", "Содержание")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Subject"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
//                    ->filter()
                    ->sort()->width("50%"),
                ExtendedTD::make("Created", "Cоздан")
                    ->render(fn($item) => Carbon::parse($item["Created"])->format('d/m/y'))
                    ->cssClass(fn($item) => $item["RequestState"])
//                    ->filter()
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
