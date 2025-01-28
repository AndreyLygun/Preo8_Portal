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
    public $Title = "Заявка на работы";

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
        dd($this->sites);
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        array_pop($layout);
        $layout[] = Layout::rows([
            TextArea::make("entity.Work")
                ->title("Наименование работ")
                ->horizontal()
                ->rows(3)
                ->disabled($readonly),
            CheckBox::make("DisableAFA")
                ->title("Требуется выключение пожарной сигнализации")
                ->value('true')->set('yesvalue', 'true')->set('novalue', 'false')
                ->disabled($readonly)->sendTrueOrFalse()->horizontal(),
            DateTimer::make('entity.FromDateTime')
                ->title("Действует с")->horizontal()
                ->min(Carbon::today())->enableTime(true)
                ->format('d-m-Y H:i')->format24hr(true)
                ->disabled($readonly),
            DateTimer::make('entity.TillDateTime')->title("Действует до")->horizontal()
                ->min(Carbon::today())->enableTime(true)->format('d-m-Y H:i')->format24hr(true)
                ->disabled($readonly),
            TextArea::make('entity.Visitors')->title('Работники')->rows(5)
                ->disabled($readonly),
            Select::make('entity.TimeSpan.Id')
                ->title('Место выполнения работ')
                ->options([1=>"А", 2=>"Б", 3=>"В", 4=>"Г"])
                ->horizontal()
                ->required()->multiple()
                ->empty('!!!!')
                ->disabled($readonly),
        ])->title('Описание дополнительного доступа');
        return $layout;
    }
}
