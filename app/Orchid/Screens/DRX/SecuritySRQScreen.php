<?php

namespace App\Orchid\Screens\DRX;

use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Carbon\Carbon;



class SecuritySRQScreen extends BaseSRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsSecuritySRQScreen";
    public $entity;

    public function ExpandFields() {
        return parent::ExpandFields();
    }

    public function NewEntity() {
        $entity = parent::NewEntity();
        $entity["ResponsibleName"] = Auth()->user()->name;
        $entity["ValidTill"] = $entity["ValidFrom"] = $entity["ValidOn"] = Carbon::tomorrow();
        return $entity;
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $layout = Layout::rows([
                Input::make("entity.ResponsibleName")->title("Ответственный сотрудник")->horizontal()->readonly($this->readOnly),
                Input::make("entity.ResponsiblePhone")->title("Телефон сотрудника")->horizontal()->readonly($this->readOnly)->mask('+7 (999) 999-99-99'),
            ]);
        return array_merge(parent::layout(), [$layout]);
    }
}
