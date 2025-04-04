<?php

namespace App\DRX\Screens\Assets;

use App\DRX\Helpers\Databooks;
use App\DRX\Helpers\Functions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Color;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Support\Facades\Toast;
use App\DRX\Screens\SecuritySRQScreen;


class AssetsInOutScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4AssetsMovings';
    protected $Title = 'Заявка на перемещение ТМЦ';

    public function ExpandFields()
    {
        $ExpandFields = ['LoadingSite', 'Inventory', 'ElevatorTimeSpan($expand=Name)'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ["Inventory", 'ElevatorTimeSpan']);
    }

    public function query(int $id = null): iterable
    {
        $query = parent::query($id);
        if (isset($query["entity"]["ElevatorTimeSpan"])) {
            $ElevatorTimeSpan = collect($query["entity"]["ElevatorTimeSpan"])->map(fn($value) => $value["Name"]["Id"]);
            $query["entity"]["ElevatorTimeSpan"] = $ElevatorTimeSpan->toArray();
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

    public function layout(): iterable
    {
        //dd($this->entity);
        $IdNameFunction = function ($value) {
            return [$value['Id'] => $value['Name']];
        };
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
                ->multiple(true)->maximumSelectionLength(3)
                ->disabled($readonly),
            Select::make("entity.StorageRoom")
                ->title('Через комнату временного хранения')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
        ])->title('Сведения о перемещении');

        $layout[] = Layout::rows([
            Select::make("entity.BuildingMaterials")
                ->title('Среди ТМЦ есть стройматериалы')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
            ExtendedMatrix::make('entity.Inventory')
                ->columns(['Описание' => 'Name', 'Габариты' => 'Size', 'Количество' => 'Quantity', 'Примечание' => 'Note'])
                ->readonly($readonly)
        ])->title("Описание ТМЦ");

        $layout[] = Layout::rows([
            Button::make(__("Save"))->method('saveCarrier')->class('btn btn-primary')->canSee($this->entity['RequestState'] == 'Approved'),
            Label::make('')->value('Сведения о перевозчике можно заполнить после согласования пропуска')->class("small mt-0 mb-0")->canSee($this->entity['RequestState'] != 'Approved'),
            Input::make('entity.CarModel')->title('Модель автомобиля')->horizontal(),
            Input::make('entity.CarNumber')->title('Номер автомобиля')->horizontal(),
            TextArea::make('entity.Visitors')->title('Грузчики (по одному человеку на строку)')->horizontal()->rows(3)  ,
        ])->title('Сведения о перевозчике');
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
}
