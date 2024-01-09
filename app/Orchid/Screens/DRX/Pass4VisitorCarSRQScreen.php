<?php

namespace App\Orchid\Screens\DRX;


use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;




class Pass4VisitorCarSRQScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public $EntityType = "IServiceRequestsPass4VisitorCars";
    public $Title = "Заявка на разовый автопропуск";
    public $CollectionFields = ["Visitors"];

    public function ExpandFields() {
        $ExpandFields = ["Visitors"];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOn")->title("Дата посещения")->horizontal()->enableTime(false)->format('Y-m-d\Z'),
                Input::make("entity.CarModel")->title("Модель автомобиля")->horizontal(),
                Input::make("entity.CarNumber")->title("Номер автомобиля")->horizontal(),
                Matrix::make("entity.Visitors")->columns(['ФИО' => 'Name'])->title("Посетители")->horizontal()
            ]);
        return $layout;
    }
}
