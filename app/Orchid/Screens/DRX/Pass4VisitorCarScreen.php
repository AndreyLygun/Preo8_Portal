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

    public function beforeSave()
    {
        parent::beforeSave();
        $this->NormalizeDate('ValidOnDateTime');
    }


    // Описывает макет экрана
    public function layout(): iterable
    {
        $tz = new DateTimeZone('Europe/Moscow');

        $layout = parent::layout();
        $layout[] = Layout::rows([
            DateTimer::make("entity.ValidOn")->title("Дата въезда.")->horizontal()
                ->format("d-m-Y")->serverFormat("d-m-Y H:i")->allowInput(true)
                ->withQuickDates(['Завтра' => now()->addDay(), 'Послезавтра' => now()->addDay(2)])
                ->min($this->EearliestDate(14))
                ->enableTime(false)->required()->disabled($this->readOnly),
            DateTimer::make("entity.ValidOnDateTime")->title("Время въезда.")->horizontal()
                ->format("H:i")->serverFormat("d-m-Y H:i")->format24hr()
                ->noCalendar()->required()->disabled($this->readOnly),
            Input::make("entity.Duration")
                ->title("Продолжительность парковки (час)")->horizontal()->value(1)
                ->type('number')->min(0)->max(8)->step(0.5)
                ->required()->disabled($this->readOnly)
                ->help("Парковка свыше 3 часов должна быть оплачена по тарифам бизнес-центра"),
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
