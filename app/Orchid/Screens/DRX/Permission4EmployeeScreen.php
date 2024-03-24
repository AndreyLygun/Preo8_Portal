<?php

namespace App\Orchid\Screens\DRX;


use Carbon\Carbon;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


class Permission4EmployeeScreen extends SecuritySRQScreen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermission4Employees";
    public $Title = "Заявка на временный доступ для сотрудника";

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        array_pop($layout);
        $layout[] = Layout::rows([
            Input::make("entity.Employee")->title("ФИО сотрудника")->horizontal(),
            Input::make("entity.PassNumber")->title("Номер пропуска сотрудника")->horizontal(),
            DateTimer::make('entity.ValidFrom')->title("Действует с")->horizontal()
                ->min(Carbon::today())->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            DateTimer::make('entity.ValidTill')->title("Действует до")->horizontal()
                ->min(Carbon::today())->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            Input::make('entity.Permisssion')->title("Вид доступа")->horizontal(),
        ])->title('Описание дополнительного доступа');
        return $layout;
    }
}
