<?php

namespace App\DRX\Screens;

use App\DRX\NewDRXClient;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Auth;
use App\DRX\Helpers\Functions;
use Illuminate\Support\Facades\Request;
use Mockery\Exception;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Repository;
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

        $drx = new NewDRXClient('IServiceRequestsBaseSRQs');
        // Получаем сортировку и фильтр из параметров запроса
        if ($sort = Request::input('sort'))
            $drx->order($drx->OrchidToOdataOrder($sort));
        else
            $drx->order('Id', 'desc');
        $where = [];
        if ($filter = Request::input('filter')) {
            if (isset($filter['Id']))
                $where[] = ['Id', '=', (int)$filter['Id']];
            if (isset($filter['Creator']))
                $where[] = ['Creator', 'contains', $filter['Creator']];
            if (isset($filter['DocumentKind.Name']))
                $where[] = ['DocumentKind/Name', '=', $filter['DocumentKind.Name']];
            if (isset($filter['Subject']))
                $where[] = ['Subject', 'contains', $filter['Subject']];
        }
//        if (!Auth::user()->hasAccess('platform.renter.acccessAllRequests'))
//            $where[] = ['CreatorMail', '=', Auth::user()->email];
        $where[] = ['Renter/Login/LoginName', '=', Auth::user()->DrxAccount['DRX_Login']];
        $drx->where($where);
        try {
            $list = $drx->with('Author,DocumentKind')->paginate(20)->get();
        } catch (ClientException $ex) {
          return ['error' => $ex->getMessage()];
        }
        return [
            'entities' => $list,
            'pagination' => $drx->GetPaginator()
        ];
    }

    public
    function name(): ?string
    {
        return 'Все заявки';
    }

    public
    function commandBar(): iterable
    {
        $commands = [];
        foreach (config('srq.requests') as $kind) {
            if (Functions::UserHasAccessTo($kind)) {
                $properties = get_class_vars($kind);
                $commandTitle = "...на " . mb_lcfirst($properties['Title']);
                $commands[] = Link::make($commandTitle)->route('drx.' . class_basename($kind));
            }
        }
        if ($commands)
            return [DropDown::make("Создать заявку...")->list($commands)];
        else
            return  [];
    }

    public
    function layout(): iterable
    {
        if (isset($this->error)) {
            return [
                Layout::rows([
                    Label::make('error'),
                    Label::make('error.errnum')
                ])->title('Ошибка!')
            ];
        }
        $DocumentKindNames = [];
        foreach (config('srq.requests') as $kind) {
            if (Functions::UserHasAccessTo($kind)) {
                $properties = get_class_vars($kind);
                $commandTitle = "...на " . mb_lcfirst($properties['Title']);
                $DocumentKindNames[$properties['Title']] = $properties['Title'];
            }
        }

        $Layout = [
            Layout::table("entities", [
                ExtendedTD::make("Id", "№")
                    ->render(fn($item) => $item["Id"])
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->width("100")
                    ->sort()
                    ->filter(ExtendedTD::FILTER_NUMERIC),
                ExtendedTD::make("DocumentKind.Name", "Вид заявки")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["DocumentKind"]["Name"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->filter(Select::make()->options($DocumentKindNames)->empty())
                    ->sort(),
                ExtendedTD::make("Creator", "Автор")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Creator"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->filter(ServiceRequestsFilter::class)
                    ->sort(),
                ExtendedTD::make("Subject", "Содержание")
                    ->render(fn($item) => "<a href='/srq/{$item["@odata.type"]}/{$item["Id"]}'>{$item["Subject"]}</a>")
                    ->cssClass(fn($item) => $item["RequestState"])
                    ->filter()
                    ->sort()->width("50%"),
                ExtendedTD::make("Created", "Cоздан")
                    ->render(fn($item) => Carbon::parse($item["Created"])->format('d/m/y'))
                    ->cssClass(fn($item) => $item["RequestState"])
//                    ->filter()
                    ->sort(),
                ExtendedTD::make("RequestState", "Статус")
                    ->render(fn($item) => __($item["RequestState"]))
                    ->cssClass(fn($item) => $item["RequestState"])
            ])
        ];
        if ($pagination = ($this->pagination ?? false) and ($pagination['last_page'] > 1)) {
            $Layout[] = Layout::view("pagination", ["pagination" => $pagination]);
        };
        return $Layout;
    }
}
