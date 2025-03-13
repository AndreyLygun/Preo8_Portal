<?php

namespace App\DRX\Screens;


use App\DRX\DRXClient;
use App\DRX\Helpers\Databooks;
use Carbon\Carbon;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


class StopPermanentPass4CarScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsStopPermanentPass4Cars";
    public $Title = "Блокировка постоянного автопропуска";

    public function ExpandFields()
    {
        $ExpandFields = ['PermanentPass4Car'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $readOnly = $this->readOnly;
        $layout = parent::layout();
        $odata = new DRXClient();
        $permanentPasses = $odata->from('IServiceRequestsPermanentPass4Cars')
            ->expand('ParkingPlace')
//            ->where('ValidTill', '>', date('y-m-d'))
            ->order('ValidTill', 'desc')
            ->get()->where('ValidTill', '>', Carbon::today()->addDays(-1))
            ->mapWithKeys(fn($v) => [$v['Id'] => $v['Subject']]);

        //array_pop($layout);
        $actions = [
            'StopPass' => 'Заблокировать пропуск',
            'NewPaper' => 'Изготовить бумажный пропуск',
            'NewNFC' => 'Оформить новый электронный пропуск'
        ];
        $layout[] = Layout::rows([
            DateTimer::make('entity.ValidTill')
                ->title('Заблокировать пропуск с')->horizontal()->hr()
                ->format('Y-m-d')->serverFormat('Y-m-d')
                ->enableTime(false)->min(Carbon::today())
                ->required()->disabled($readOnly),
            Select::make('entity.PermanentPass4Car.Id')
                ->title('Пропуск для блокировки')->horizontal()->empty('Выберите пропуск, который нужно заблокировать')
                ->options($permanentPasses)
        ])->title('Сведения об автомобиле');
        $layout[] = Layout::rows([TextArea::make('entity.Note')->title("Примечание")->rows(10)->horizontal()]);
        return $layout;
    }
}
