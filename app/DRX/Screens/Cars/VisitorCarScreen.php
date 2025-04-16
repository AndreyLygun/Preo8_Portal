<?php

namespace App\DRX\Screens\Cars;


use App\DRX\Layouts\Pass4VisitorCarListener;
use Carbon\Carbon;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use App\DRX\Screens\SecuritySRQScreen;


class VisitorCarScreen extends SecuritySRQScreen
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
        $entity["ParkingType"] = "CommonParking";
        return $entity;
    }

    public function beforeSave()
    {
        parent::beforeSave();
        $Date = Carbon::parse($this->entity["ValidOn"]);
        $Time = Carbon::parse($this->entity["ValidOnDateTime"]);
        $DateAndTime = Carbon::create($Date->year, $Date->month, $Date->day, $Time->hour, $Time->minute);
        $this->entity["ValidOnDateTime"] = $DateAndTime->format('Y-m-d\TH:i:00+03:00');
    }


    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Pass4VisitorCarListener::class;
        $layout[] = Layout::rows([Input::make("entity.CarModel")
            ->title("Модель автомобиля")->horizontal()
            ->required()->readonly($this->readOnly),
            Input::make("entity.CarNumber")
                ->title("Номер автомобиля")->horizontal()
                ->required()->readonly($this->readOnly),
//            TextArea::make("entity.Visitors")
//                ->title("Посетители")->horizontal()
//                ->rows(5)->readonly($this->readOnly)
//                ->help('Один посетитель (фамилия, имя, отчество) на одну строку. Разовые пропуска на них будут оформлены после согласования заявки.')
        ])
            ->title("Автомобиль");
        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);
        return $layout;
    }
}
