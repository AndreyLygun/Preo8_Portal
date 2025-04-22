<?php

namespace App\DRX\Screens\People;


use App\DRX\Helpers\Databooks;
use App\DRX\Helpers\Functions;
use Carbon\Carbon;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use App\DRX\Screens\SecuritySRQScreen;


class WorkPermissionScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsWorkPermissions";
    public $Title = "Заявка на проведение работ";

    public function beforeSave()
    {
        parent::beforeSave();
        $this->NormalizeDate(['FromDateTime', 'TillDateTime']);
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
//        array_pop($layout);
        $layout[] = Layout::rows([
            TextArea::make("entity.Work")
                ->title("Описание работ (состав, используемое оборудование)")
                ->horizontal()
                ->rows(3)
                ->required()
                ->disabled($readonly),
            Select::make("entity.DisableAFA")
                    ->title('Требуется выключение пожарной сигнализации')->horizontal()
                    ->empty('')->required()
                    ->options(Databooks::GetYesNo())
                    ->disabled($this->readOnly),
            TextArea::make('entity.Site')
                ->title('Место выполнения работ')
                ->horizontal()
                ->required()
                ->disabled($readonly)->help("Укажите блок, этаж, помещение"),
            DateTimer::make('entity.FromDateTime')
                ->title("Начало работ")->horizontal()
                ->format('d-m-Y H:i')->serverFormat('d-m-Y H:i')
                ->min(Carbon::today()->addDay(1))
                ->allowInput()->value(Carbon::today()->addDay(1)->addHour(9))
                ->enableTime()->format24hr()
                ->required()->disabled($readonly),
            DateTimer::make('entity.TillDateTime')->title("Окончания работ")->horizontal()
                ->title("Окончание работ")->horizontal()
                ->format('d-m-Y H:i')->serverFormat('d-m-Y H:i')
                ->min(Carbon::today()->addDay(1))
                ->allowInput()->value(Carbon::today()->addDay(1)->addHour(18))
                ->enableTime()->format24hr()
                ->required()->disabled($readonly),
        ])->title('Работы');
        $layout[] = Layout::rows([
            Input::make('entity.Contractor')->title('Организация-исполнитель')
                ->disabled($readonly)
                ->horizontal(),
            TextArea::make('entity.Visitors')->title('Сотрудники-исполнители')->rows(5)
                ->disabled($readonly)
            ->horizontal(),
        ])->title('Исполнитель');
        $layout[] = Layout::rows([
            TextArea::make('entity.Note')->title('Примечание')->rows(5)
                ->disabled($readonly)
                ->horizontal(),
        ]);
        return $layout;
    }
}
