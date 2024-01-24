<?php

namespace App\Orchid\Screens\DRX;



use App\DRX\DRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Select;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;
use Illuminate\Support\Collection;



class Pass4AssetsMovingSRQScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4AssetsMovings';
    protected $Title = 'Заявка на перемещение ТМЦ';
    protected $CollectionFields = ["Loaders", "Inventory"];
    public $LoadingSites = null; // места разгрузки-загрузки
    public $SectionSites = null; // секции здания
    public $TimeSpans = null;

    public function ExpandFields()
    {
        $ExpandFields = ["Loaders", "Cars", 'LoadingSite', 'TimeSpan', 'Inventory', 'Floor'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function query($id = null):iterable {
        $IdNameFunction = function(array $value) {
            return [$value['Id']=>$value['Name']];
        };
        try {
            $result = parent::query($id);
            $odata = new DRXClient();
            if (isset($result['error'])) return $result;
            $result['LoadingSites'] = $odata->from('IServiceRequestsSites')->where('Type', 'Loading')->get()->mapWithKeys($IdNameFunction)->toArray();
            $result['SectionSites'] = $odata->from('IServiceRequestsSites')->where('Type', 'Section')->get()->mapWithKeys($IdNameFunction)->toArray();
            $result['TimeSpans'] = $odata->from('IServiceRequestsTimeSpans')->get()->mapWithKeys($IdNameFunction);
        } catch (GuzzleException $ex) {
            return [
                'error' => [
                    'message' => $ex->getMessage()

                ]
            ];
        }
        return $result;
    }

    public function layout(): iterable
    {
//        dd($this->entity, $this->LoadingSites, $this->TimeSpans, $this->SectionSites, config('srq.MovingDirection'));
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        $layout[] = Layout::rows([
            Select::make('entity.MovingDirection')
                ->options([
                    "MoveIn" => "Ввоз",
                    "MoveOut" => "Вывоз",
                    "CarryIn" => "Внос",
                    "CarryOut" => "Вынос",
                    166 => 'wrong',
                    167 => 'ok',
                ])->title('Проверка')->horizontal(),
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
                ->disabled($readonly),
            Select::make('entity.Floor.Id')
                ->title('Этаж')
                ->options($this->SectionSites)
                ->horizontal()
                ->required()
                ->disabled($readonly),
            CheckBox::make('entity.Elevator')
                ->title('Требуется грузовой лифт')
                ->horizontal()
                ->value('true')->set('yesvalue', 'true')->set('novalue', 'false')
                ->readonly($readonly)->sendTrueOrFalse(),
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
            Matrix::make('entity.Loaders')->columns(['ФИО' => 'Name'])->title('Персонал')->horizontal(),
        ])->title('Сведения о перевозчике');
        return $layout;
    }
}
