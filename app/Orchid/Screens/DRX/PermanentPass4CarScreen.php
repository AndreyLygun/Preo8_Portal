<?php

namespace App\Orchid\Screens\DRX;


use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;



class PermanentPass4CarScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermanentPass4Cars";
    public $Title = "Постоянная заявка на перемещение ТМЦ";

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
                Input::make("entity.CarModel")->title("Модель автомобиля")->horizontal(),
                Input::make("entity.CarNumber")->title("Номер автомобиля")->horizontal(),
            ]);
        return $layout;
    }
}
