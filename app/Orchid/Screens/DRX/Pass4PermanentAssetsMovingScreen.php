<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;


class Pass4PermanentAssetsMovingScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4PermanentAssetsMovings';
    protected $Title = 'Заявка на регулярное перемещение ТМЦ';

    public function ExpandFields()
    {
        return array_merge(parent::ExpandFields(), ['Cars']);
    }

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ["Cars"]);
    }

    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->entity['Assets'] == null) $this->entity['Assets'] = '';
        if (!isset($this->entity['Cars'])) $this->entity['Cars'] = [];
        //dd($this->entity);
    }

    public function layout(): iterable
    {
        Log::debug("Начало layout()");
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        $layout[] = Layout::rows([
            DateTimer::make('entity.ValidFrom')->title("Действует с")->horizontal()
                ->min(Carbon::today())->max(Carbon::today()->addYear(1))->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            DateTimer::make('entity.ValidTill')->title("Действует до")->horizontal()
                ->min(Carbon::today())->max(Carbon::today()->addYear(1))->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            TextArea::make('entity.Assets')->title('Описание ТМЦ')->horizontal()->rows(5)->required(),
            ExtendedMatrix::make('entity.Cars')
                ->columns(['Модель' => 'Model', 'Гос.гомер' => 'Number', 'Примечание' => 'Note'])->title('Сведения об автомобилях')->readonly($readonly)
        ])->title("Описание ТМЦ");
        $layout[] = Layout::rows([
                TextArea::make('')->rows(5)->readonly(true)
                    ->placeholder('При перещении ТМЦ обязуемся убирать за собой упаковочную тару, не загромождать проходы в зонах общего пользования. Сохранность оборудования, интерьера Здания по маршруту движения гарантируем. В случае порчи или нанесения повреждений обязуемся возместить ущерб.')
            ]
        );
        Log::debug("Конец layout()");
        return $layout;
    }
}
