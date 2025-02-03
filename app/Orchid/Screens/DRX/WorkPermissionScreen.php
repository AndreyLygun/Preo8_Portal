<?php

namespace App\Orchid\Screens\DRX;


use Carbon\Carbon;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


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

    public function ExpandFields()
    {
        $ExpandFields = ['Sites'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

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
            CheckBox::make("entity.DisableAFA")
                ->title("Требуется выключение пожарной сигнализации")
                ->value('true')->set('yesvalue', 'true')->set('novalue', 'false')
                ->disabled($readonly)->sendTrueOrFalse()->horizontal(),
            TextArea::make('entity.Site')
                ->title('Место выполнения работ')
                ->horizontal()
                ->required()
                ->disabled($readonly)->help("Укажите блок, этаж, помещение"),
            DateTimer::make('entity.FromDateTime')
                ->title("Начало работ")
                ->format('d-m-Y H:i')
                ->serverFormat('d-m-Y H:i')
                ->min($this->EearliestDate(9))
                ->allowInput()->value(Carbon::today()->addDay(1)->addHour(9))
                ->required()
                ->horizontal()
                ->enableTime()->format24hr()
                ->min($this->EearliestDate(14))
                ->disabled($readonly),
            DateTimer::make('entity.TillDateTime')->title("Окончания работ")->horizontal()
                ->title("Окончание работ")
                ->format('d-m-Y H:i')
                ->serverFormat('d-m-Y H:i')
                ->min($this->EearliestDate(9))
                ->allowInput()->value(Carbon::today()->addDay(1)->addHour(18))
                ->required()
                ->horizontal()
                ->enableTime()->format24hr()
                ->min($this->EearliestDate(14))
                ->disabled($readonly),
        ])->title('Работы');
        $layout[] = Layout::rows([
            Input::make('entity.Contractor')->title('Организация-исполнитель')
                ->disabled($readonly)
                ->horizontal(),
            TextArea::make('entity.Visitors')->title('Сотрудники-исполнители')->rows(5)
                ->disabled($readonly)
            ->horizontal(),
        ])->title('Исполнитель');
        return $layout;
    }
}
