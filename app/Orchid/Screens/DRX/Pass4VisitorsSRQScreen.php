<?php

namespace App\Orchid\Screens\DRX;

use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;


class Pass4VisitorsSRQScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsPass4Visitors";
    public $Title = "Заявка на разовый пропуск";
    public $CollectionFields = ['Visitors'];

    public function ExpandFields() {
        $ExpandFields = parent::ExpandFields();
        $ExpandFields[] = "Visitors";
        return $ExpandFields;
    }

    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOn")->title("Дата посещения")->horizontal()->enableTime(false)->format('Y-m-d\Z'),
                Matrix::make("entity.Visitors")->columns(['ФИО' => 'Name'])->title("Посетители")->horizontal()
            ]);
        return $layout;
    }
}
