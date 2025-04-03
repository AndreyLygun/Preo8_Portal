<?php

namespace App\DRX\Screens\Assets;

use Carbon\Carbon;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;
use App\DRX\Screens\SecuritySRQScreen;


class AssetsPermanentScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4AssetsPermanentMovings';
    protected $Title = 'Заявка на регулярное перемещение ТМЦ';

    public function ExpandFields()
    {
        return array_merge(parent::ExpandFields(), ['Cars', 'Assets']);
    }

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ["Cars", "Assets"]);
    }

    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->entity['Assets'] == null) $this->entity['Assets'] = '';
        if (!isset($this->entity['Cars'])) $this->entity['Cars'] = [];
        $this->NormalizeDate(['ValidFrom', 'ValidTill']);
        //dd($this->entity);
    }

    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
            DateTimer::make('entity.ValidFrom')
                ->title("Действует с")->horizontal()
                ->disabled($this->readOnly)
                ->enableTime(false)->format('d-m-Y')->serverFormat('d-m-Y')
                ->min(Carbon::today())->max(Carbon::today()->addYears(1)),
            DateTimer::make('entity.ValidTill')
                ->title("Действует до")->horizontal()
                ->disabled($this->readOnly)
                ->enableTime(false)->format('d-m-Y')->serverFormat('d-m-Y')
                ->min(Carbon::today())->max(Carbon::today()->endOfYear())
        ]);
        $layout[] = Layout::rows([
            ExtendedMatrix::make('entity.Assets')
                ->columns(['Описание' => 'Name', 'Габариты' => 'Size', 'Количество' => 'Quantity', 'Примечание' => 'Note'])
                ->readonly($this->readOnly)])
            ->title('Описание ТМЦ');
        $layout[] = Layout::rows([
            Input::make('Carrier')
                ->title('Поставщик/перевозчик')->horizontal()
                ->readonly($this->readOnly),
            ExtendedMatrix::make('entity.Cars')
                ->title('Автомобили')
                ->columns(['Модель' => 'Model', 'Гос.гомер' => 'Number', 'Примечание' => 'Note'])
                ->readonly($this->readOnly),
            TextArea::make('entity.Visitors')
                ->title('Сотрудники')
                ->readonly($this->readOnly)
                ->rows(5)
        ])->title('Сведения о перевозчике');
        $layout[] = Layout::rows([TextArea::make('entity.Note')->title("Примечание")->rows(10)]);
        $layout[] = Layout::rows([
                TextArea::make('')->rows(5)->readonly(true)
                    ->placeholder('При перещении ТМЦ обязуемся убирать за собой упаковочную тару, не загромождать проходы в зонах общего пользования. Сохранность оборудования, интерьера Здания по маршруту движения гарантируем. В случае порчи или нанесения повреждений обязуемся возместить ущерб.')
            ]
        );
        return $layout;
    }
}
