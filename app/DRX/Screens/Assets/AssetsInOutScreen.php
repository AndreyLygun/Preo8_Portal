<?php

namespace App\DRX\Screens\Assets;

use App\DRX\Helpers\Databooks;
use App\DRX\Helpers\ImportExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Color;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Support\Facades\Toast;
use App\DRX\Screens\SecuritySRQScreen;


class AssetsInOutScreen extends SecuritySRQScreen
{

    public static $EntityType = 'IServiceRequestsPass4AssetsMovings';
    public static $Title = 'Разовый ввоз-вывоз ТМЦ';
    protected static $ExpandFields = ['LoadingSite', 'Inventory', 'ElevatorTimeSpan($expand=Name)'];
    protected static $CollectionFields = ["Inventory", 'ElevatorTimeSpan'];


    public function query(int $id = null): iterable
    {
        $query = parent::query($id);
        if (isset($query["entity"]["ElevatorTimeSpan"])) {
            $ElevatorTimeSpans = ($query["entity"]["ElevatorTimeSpan"]);
            if (isset($ElevatorTimeSpans[0]['Name'])) {
                $ElevatorTimeSpan = collect($ElevatorTimeSpans)->map(fn($value) => $value["Name"]["Id"]);
                $query["entity"]["ElevatorTimeSpan"] = $ElevatorTimeSpan->toArray();
            }
        }
        return $query;
    }


    public function beforeSave()
    {
        parent::beforeSave();
        $this->NormalizeDate(['ValidOn']);
        if (isset($this->entity["ElevatorTimeSpan"]))
            $this->entity["ElevatorTimeSpan"] = collect($this->entity["ElevatorTimeSpan"])->map(fn($value) => (object)["Name" => (object)["Id" => (int)$value]])->toArray();
    }


    public function commandBar(): iterable
    {
        $buttons = parent::commandBar();
        if (isset($this->entity["Id"]))
            $buttons[] = Link::make('Копировать')->route(Route::currentRouteName(), ['fromId' => $this->entity['Id']]);
        return $buttons;
    }

    public function layout(): iterable
    {
        $layout = parent::layout();
        $readonly = $this->readOnly;
        $layout[] = Layout::rows([
            Select::make('entity.MovingDirection')
                ->title('Направление перемещения')->horizontal()
                ->options(Databooks::GetMovingDirection())->empty('')
                ->required()->disabled($readonly),
            DateTimer::make('entity.ValidOn')
                ->title("Дата перемещения")->horizontal()
                ->format('d-m-Y')->serverFormat('d-m-Y')
                ->min(Carbon::now()->hour < 14?Carbon::today():Carbon::tomorrow())
                ->help("Заявки &laquo;на сегодня&raquo; принимаются до 14:00. Время согласования заявки - 3 часа")
                ->disabled($readonly)->required(),
            Select::make('entity.LoadingSite.Id')
                ->title('Место разгрузки')->horizontal()
                ->options(Databooks::GetSites('Loading'))->empty('')
                ->required()->disabled($readonly),
            Input::make('entity.Floor')
                ->title('Куда/откуда')
                ->horizontal()
                ->required()
                ->help("Укажите блок, этаж, помещение")
                ->disabled($readonly),
            Select::make("entity.Elevator")
                ->title('Через грузовой лифт')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
            Select::make('entity.ElevatorTimeSpan')
                ->title('Время использования лифта')->horizontal()
                ->options(Databooks::GetTimeSpans())
                ->empty('Выберите время использование лифта')
                ->help('Можно выбрать до трёх интервалов')
                ->multiple()->maximumSelectionLength(3)
                ->disabled($readonly),
            Select::make("entity.StorageRoom")
                ->title('Через комнату временного хранения')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
        ])->title('Сведения о перемещении');

        $modalToggeButton = ModalToggle::make('Заполнить из Excel')
            ->modal('Excel')
            ->method('FillFromExcell', ['entity' => $this->entity])
            ->icon('bs.table');
        $clearButton = Button::make('Очистить')
            ->icon('full-screen')
            ->method('ClearInventory', ['entity' => $this->entity]);

        $layout[] = Layout::rows([
            Select::make("entity.BuildingMaterials")
                ->title('Среди ТМЦ есть стройматериалы')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
            ExtendedMatrix::make('entity.Inventory')
                ->columns(['Описание' => 'Name', 'Габариты' => 'Size', 'Количество' => 'Quantity', 'Примечание' => 'Note'])
                ->readonly($readonly)
                ->addButton($clearButton)
                ->addButton($modalToggeButton),
        ])->title("Описание ТМЦ");

        $layout[] = ImportExcel::MakeModalExcel('Список ТМЦ', '/assets/inventory.xlsx');


        $layout[] = Layout::rows([
            Button::make(__("Save"))
                ->method('saveCarrier')->class('btn btn-primary')
                ->canSee(in_array($this->entity['RequestState'], ['OnReview', 'Approved'])),
            Label::make('')->value('Сведения о перевозчике можно внести или изменить в любой момент до фактического въезда')->class("small mt-0 mb-0")->canSee($this->entity['RequestState'] != 'Approved'),
            Input::make('entity.CarModel')
                ->title('Модель автомобиля')->horizontal()
            ->help("Если во время создания заявки модель и номер автомобиля неизвестны, их можно заполнить позже - до въезда"),
            Input::make('entity.CarNumber')
                ->title('Номер автомобиля')->horizontal(),
            TextArea::make('entity.Visitors')
                ->title('Грузчики (по одному человеку на строку)')->horizontal()->rows(3)
                ->help("Если во время создания заявки имена грузчиков неизвестны, их можно указать позже - до въезда"),
        ])->title('Сведения о перевозчике');

        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);

        return $layout;
    }

    public function saveCarrier(Request $request)
    {
        Toast::info("ok, saved");
        $validated = $request->validate([
            'entity.Id' => '',
            'entity.CarModel' => '',
            'entity.CarNumber' => '',
            'entity.Visitors' => ''
        ]);
        $this->SaveToDRX(false, $validated["entity"]);
    }

    public function ClearInventory(Request $request)
    {
        $this->entity = array_merge($this->entity, $request->input('entity') ?? []);
        $this->entity['Inventory'] = [];
    }

    public function FillFromExcell(Request $request)
    {
        $this->entity = array_merge($this->entity, request()->input('entity') ?? []);
        if (!$request->hasFile('ExcelFile')) return;
        try {
            $res = ImportExcel::Basic($request->file('ExcelFile'), ['Name', 'Size', 'Quantity', 'Note']);
            $this->entity['Inventory'] = $res;
            Toast::info('Данные из файла импортированы. Не забудьте сохранить заявку.');
        } catch (LaravelExcelException $ex) {
            Alert::error(stripcslashes($ex->getMessage()));
        }
    }
}
