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



class Pass4AssetsInternalMovingScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4AssetsInternalMovings';
    protected $Title = 'Заявка на внутреннее перемещение ТМЦ';

    public function ExpandFields()
    {
        $ExpandFields = ['TimeSpan', 'Inventory'];
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
        $TimeSpans = collect($this->TimeSpans)->mapWithKeys($IdNameFunction);
        $layout = parent::layout();
        $readonly = $this->readOnly;
        $layout[] = Layout::rows([
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
            Input::make('entity.From')
                ->title('Откуда')
                ->horizontal()
                ->required()
                ->disabled($readonly),
            Input::make('entity.To')
                ->title('Куда')
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
