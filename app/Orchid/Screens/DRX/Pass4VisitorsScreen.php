<?php

namespace App\Orchid\Screens\DRX;

use Carbon\Carbon;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;


class Pass4VisitorsScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPass4Visitors";
    protected $Title = "Заявка на разовый пропуск";
    protected $CollectionFields = [];

    public function NewEntity()
    {
     $entity = parent::NewEntity();
     return $entity;
    }

    public function layout(): iterable
    {
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOn")->title("Дата посещения")
                    ->horizontal()->enableTime(false)->min(Carbon::today())
                    ->format('Y-m-d')->serverFormat('Y-m-d\Z')->readonly($readonly),
                TextArea::make("entity.Visitors")->columns(['ФИО' => 'Name'])->readonly($readonly)
                    ->title("Посетители")->horizontal()->rows(20)
            ]);
        return $layout;
    }
}
