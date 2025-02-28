<?php

namespace App\DRX\Screens;


use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;


class PermanentPass4EmployeeScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermanentPass4Employees";
    public $Title = "Заявка на постоянный пропуск";




    // Описывает макет экрана
    public function layout(): iterable
    {
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        array_pop($layout);
        $layout[] = Layout::rows([
            Input::make("entity.EmployeeName")->title("ФИО Сотрудника")->horizontal()->disabled($readonly),
            Input::make("entity.EmployeePosition")->title("Должность сотрудника")->horizontal()->disabled($readonly),
        ]);
        return $layout;
    }
}
