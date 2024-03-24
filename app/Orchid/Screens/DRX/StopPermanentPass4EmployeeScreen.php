<?php

namespace App\Orchid\Screens\DRX;


use Carbon\Carbon;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


class StopPermanentPass4EmployeeScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsStopPermanentPass4Employees";
    public $Title = "Заявка на временный доступ для сотрудника";

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        array_pop($layout);
        $layout[] = Layout::rows([
            Input::make("entity.EmployeeName")->title("ФИО сотрудника")->horizontal(),
            Input::make("entity.PassNumber")->title("Номер пропуска сотрудника")->horizontal(),
        ])->title('Пропуск для блокировки');
        return $layout;
    }
}
