<?php

namespace App\Orchid\Screens\DRX;

use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Color;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Support\Facades\Toast;



class Pass4AssetsMovingScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4AssetsMovings';
    protected $Title = 'Заявка на перемещение ТМЦ';

    public function ExpandFields()
    {
        $ExpandFields = ['LoadingSite', 'TimeSpan', 'Inventory'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ["Inventory"]);
    }

    public function beforeSave()
    {
        parent::beforeSave();
        $this->NormalizeDate(['ValidOn']);
    }

    public function layout(): iterable
    {
        $IdNameFunction = function($value) {
            return [$value['Id']=>$value['Name']];
        };
        $LoadingSites = collect($this->Sites)->where('Type', 'Loading')->mapWithKeys($IdNameFunction);
        $TimeSpans = collect($this->TimeSpans)->mapWithKeys($IdNameFunction);
        $layout = parent::layout();
        $readonly = $this->readOnly;
        $layout[] = Layout::rows([
            Select::make('entity.MovingDirection')
                ->title('Направление перемещения')
                ->options(config('srq.MovingDirection'))->empty('')
                ->required()
                ->horizontal()
                ->disabled($readonly),
            DateTimer::make('entity.ValidOn')
                ->title("Дата перемещения")
                ->format('d-m-Y')
                ->serverFormat('d-m-Y')
                ->required()
                ->horizontal()
                ->enableTime(false)
                ->min($this->EearliestDate(14))
                ->help("Заявки &laquo;на сегодня&raquo; принимаются до 14:00. Время согласования заявки - 3 часа")
                ->disabled($readonly),
            Select::make('entity.LoadingSite.Id')
                ->title('Место разгрузки')
                ->options($LoadingSites)
                ->required()
                ->horizontal()
                ->disabled($readonly),
            Input::make('entity.Floor')
                ->title('Этаж')
                ->horizontal()
                ->required()
                ->disabled($readonly),
            CheckBox::make('entity.Elevator')
                ->title('Требуется грузовой лифт')
                ->horizontal()
                ->value('true')->set('yesvalue', 'true')->set('novalue', 'false')
                ->disabled($readonly)->sendTrueOrFalse(),
            Select::make('entity.TimeSpan.Id')
                ->title('Время использования лифта')
                ->options($TimeSpans)
                ->horizontal()
                ->empty('Выберите время использование лифта')
                ->value(1)
                ->disabled($readonly),
            CheckBox::make('entity.StorageRoom')
                ->title('Через комнату временного хранения')
                ->horizontal()
                ->value('true')->set('yesvalue', 'true')->set('novalue', 'false')
                ->disabled($readonly)->sendTrueOrFalse(),
        ])->title('Сведения о перемещении');

        $layout[] = Layout::rows([
            CheckBox::make("entity.BuildingMaterials")
                ->title('Среди ТМЦ есть стройматериалы')
                ->horizontal()
                ->value('false')->set('yesvalue', 'true')->set('novalue', 'false')
                ->disabled($readonly)->sendTrueOrFalse(),
            ExtendedMatrix::make('entity.Inventory')
                ->columns(['Описание' => 'Name', 'Габариты' => 'Size', 'Количество' => 'Quantity'])
                ->readonly($readonly)
        ])->title("Описание ТМЦ");

        $layout[] = Layout::rows([
            Input::make('entity.CarModel')->title('Модель автомобиля')->horizontal(),
            Input::make('entity.CarNumber')->title('Номер автомобиля')->horizontal(),
            TextArea::make('entity.Visitors')->title('Грузчики')->horizontal(),
            Button::make(__("Save"))->type(Color::BASIC)->method('saveCarrier')->style()->canSee($this->entity['RequestState']=='Approved'),
        ]) ->title('Сведения о перевозчике');
        return $layout;
    }

    public function saveCarrier(Request $request) {
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
