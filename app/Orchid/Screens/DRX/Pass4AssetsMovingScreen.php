<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;


class Pass4AssetsMovingScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4AssetsMovings';
    protected $Title = 'Заявка на перемещение ТМЦ';
    protected $CollectionFields = ["Loaders", "Inventory"];
    public $LoadingSites = null; // места разгрузки-загрузки
    public $SectionSites = null; // секции здания
    public $TimeSpans = null;

    public function ExpandFields()
    {
        $ExpandFields = ['LoadingSite', 'TimeSpan', 'Inventory'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ["Inventory"]);
    }

    public function query($id = null):iterable {
        $IdNameFunction = function(array $value) {
            return [$value['Id']=>$value['Name']];
        };
        try {
            Log::debug("Начало query($id) ");
            $result = parent::query($id);
            Log::debug("Середина query($id)");
            $odata = new DRXClient();
            if (isset($result['error'])) return $result;
            $result['LoadingSites'] = $odata->from('IServiceRequestsSites')->where('Type', 'Loading')->get()->mapWithKeys($IdNameFunction)->toArray();
            $result['TimeSpans'] = $odata->from('IServiceRequestsTimeSpans')->get()->mapWithKeys($IdNameFunction);
        } catch (GuzzleException $ex) {
            return [
                'error' => [
                    'message' => $ex->getMessage()
                ]
            ];
        }
        Log::debug("Конец query($id)");
        return $result;
    }

    public function commandBar(): iterable
    {
        $buttons = parent::commandBar();
        switch ($this->entity["RequestState"]) {
            case 'Approved':
                $buttons[] = Button::make("Сохранить")->method("Save");
                break;
        }
        return $buttons;
    }

    public function layout(): iterable
    {
        Log::debug("Начало layout()");
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        $layout[] = Layout::rows([
            Select::make('entity.MovingDirection')
                ->title('Направление перемещения')
                ->options(config('srq.MovingDirection'))->empty('')
                ->required()
                ->horizontal()
                ->disabled($readonly),
            DateTimer::make('entity.ValidOn')
                ->title("Дата перемещения")
                ->format('Y-m-d')
                ->serverFormat('Y-m-d\Z')
                ->required()
                ->horizontal()
                ->enableTime(false)
                ->min(Carbon::today())
                ->disabled($readonly),
            Select::make('entity.LoadingSite.Id')
                ->title('Место разгрузки')
                ->options($this->LoadingSites)
                ->required()
                ->horizontal()
                ->disabled($readonly),
            Select::make('entity.TimeSpan.Id')
                ->title('Время ввоза-вывоза')
                ->options($this->TimeSpans)
                ->horizontal()
                ->required()
                ->empty('Выберите время использование лифта')
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
            CheckBox::make('entity.StorageRoom')
                ->title('Через комнату временного хранения')
                ->horizontal()
                ->value('true')->set('yesvalue', 'true')->set('novalue', 'false')
                ->disabled($readonly)->sendTrueOrFalse(),
        ])->title('Сведения о перемещении');
        $layout[] = Layout::rows([
            ExtendedMatrix::make('entity.Inventory')
                ->columns(['Описание' => 'Name', 'Габариты' => 'Size', 'Количество' => 'Quantity'])
                ->readonly($readonly)
        ])->title("Описание ТМЦ");
        $layout[] = Layout::rows([
            Input::make('entity.CarModel')->title('Модель автомобиля')->horizontal(),
            Input::make('entity.CarNumber')->title('Номер автомобиля')->horizontal(),
            TextArea::make('entity.Visitors')->title('Грузчики')->horizontal(),
        ])->title('Сведения о перевозчике');
        Log::debug("Конец layout()");
        return $layout;
    }
}
