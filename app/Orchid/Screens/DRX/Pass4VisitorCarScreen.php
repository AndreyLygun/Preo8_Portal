<?php

namespace App\Orchid\Screens\DRX;


use Carbon\Carbon;
use DateTimeZone;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\DateTimer;





class Pass4VisitorCarScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPass4VisitorCars";
    public $Title = "Заявка на разовый автопропуск";

    public function NewEntity()
    {
        $entity = parent::NewEntity();
        $entity['PrivateParking'] = false;
        return $entity;
    }


    // Описывает макет экрана
    public function layout(): iterable
    {
        $tz = new DateTimeZone('Europe/Moscow');

        $layout = parent::layout();
        $layout[] = Layout::rows([
                DateTimer::make("entity.ValidOnDateTime")->title("Дата и время въезда")->horizontal()
                    ->format("Y-m-d\TH:i:00+03:00")
//                    ->serverformat('Y-m-d\TH:i:00+03:00')
                    ->enableTime(true)->format24hr()->allowInput()->required()->disabled($this->readOnly),
                Input::make("entity.CarModel")->title("Модель автомобиля")->horizontal()->required()->disabled($this->readOnly),
                Input::make("entity.CarNumber")->title("Номер автомобиля")->horizontal()->required()->readonly($this->readOnly),
                CheckBox::make("entity.PrivateParking")->title("На парковку арендатора")->horizontal()->disabled($this->readOnly)
                    ->set('yesvalue', 'true')->set('novalue', 'false')->sendTrueOrFalse(),
                TextArea::make("entity.Visitors")->title("Посетители")->horizontal()->rows(5)->readonly($this->readOnly)
                    ->help('Один посетитель (фамилия, имя, отчество) на одну строку. Разовые пропуска на них будут оформлены после согласования заявки.')
            ]);
        return $layout;
    }
}
