<?php

namespace App\DRX\Screens\People;

use Carbon\Carbon;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\DateTimer;
use App\DRX\Screens\SecuritySRQScreen;


class VisitorsScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public static $EntityType = "IServiceRequestsPass4Visitors";
    public static $Title = "Гостевой пропуск";


    public function NewEntity()
    {
     $entity = parent::NewEntity();
     return $entity;
    }

    public function beforeSave()
    {
        parent::beforeSave(); // TODO: Change the autogenerated stub
        $this->NormalizeDate(['ValidOn']);
    }

    public function layout(): iterable
    {
                $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOn")
                    ->title("Дата посещения")->horizontal()
                    ->format('d-m-Y')->serverFormat('d-m-Y')
                    ->min(Carbon::today())
                    ->withQuickDates(['Сегодня' => today(), 'Завтра' => today()->addDay(), 'Послезавтра' => today()->addDays(2)])
                    ->readonly($readonly),
                TextArea::make("entity.Visitors")->columns(['ФИО' => 'Name'])->readonly($readonly)
                    ->title("Посетители")->horizontal()->rows(20)
            ]);
        return $layout;
    }
}
