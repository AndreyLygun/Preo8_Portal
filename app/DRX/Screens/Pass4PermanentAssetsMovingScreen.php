<?php

namespace App\DRX\Screens;

use Carbon\Carbon;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
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
        $this->NormalizeDate(['ValidFrom', 'ValidTill']);
        //dd($this->entity);
    }

    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
            DateTimer::make('entity.ValidFrom')->title("Действует с")->horizontal()->disabled($this->readOnly)
                ->min(Carbon::today())->max(Carbon::today()->addYear(1))->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            DateTimer::make('entity.ValidTill')->title("Действует до")->horizontal()->disabled($this->readOnly)
                ->min(Carbon::today())->max(Carbon::today()->addYear(1))->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            TextArea::make('entity.Assets')->title('Описание ТМЦ')->horizontal()->rows(5)->required()->readonly($this->readOnly),
            ExtendedMatrix::make('entity.Cars')
                ->columns(['Модель' => 'Model', 'Гос.гомер' => 'Number', 'Примечание' => 'Note'])->title('Сведения об автомобилях')->readonly($this->readOnly)
        ])->title("Описание ТМЦ");
        $layout[] = Layout::rows([
                TextArea::make('')->rows(5)->readonly(true)
                    ->placeholder('При перещении ТМЦ обязуемся убирать за собой упаковочную тару, не загромождать проходы в зонах общего пользования. Сохранность оборудования, интерьера Здания по маршруту движения гарантируем. В случае порчи или нанесения повреждений обязуемся возместить ущерб.')
            ]
        );
        $layout[] = Layout::rows([TextArea::make('entity.Note')->title("Примечание")->rows(10)->horizontal()]);
        return $layout;
    }
}
