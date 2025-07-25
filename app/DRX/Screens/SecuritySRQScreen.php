<?php

namespace App\DRX\Screens;

use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Carbon\Carbon;



class SecuritySRQScreen extends BaseSRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public static $EntityType = "IServiceRequestsSecuritySRQScreen";
    protected static $ExpandFields = ['ParkingPlace'];
    protected $TestField = ['BaseSRQ'];
    public $entity;


    /// Возвращает атрибуты заявки, заполненные значениями по умолчанию.
    public function NewEntity() {
        $entity = parent::NewEntity();
        $entity["ResponsibleName"] = $entity["ResponsibleName"]??Auth()->user()->name;
        $entity["ResponsiblePhone"] = $entity["ResponsiblePhone"]??Auth()->user()->phone;
        //$entity["ValidTill"] = $entity["ValidFrom"] = $entity["ValidOn"] = Carbon::tomorrow();
        return $entity;
    }

    public function beforeSave()
    {
        parent::beforeSave();
        parent::NormalizeDate(['ValidFrom', 'ValidTill', 'ValidOn']);
    }


    public function layout(): iterable
    {
        $layout = Layout::rows([
                Input::make("entity.ResponsibleName")
                    ->title("Ответственный сотрудник")->horizontal()
                    ->help("Сотрудник, который является контактным лицом по данной заявке")
                    ->readonly($this->readOnly)->required(),
                Input::make("entity.ResponsiblePhone")
                    ->title("Телефон сотрудника")->horizontal()
                    ->help("Телефон требуется для обсуждения вопросов, которые могут возникнут при согласовании и исполнении заявки")
                    ->readonly($this->readOnly)->required()
                    ->mask('+7 (999) 999-99-99'),
            ]);
        return array_merge(parent::layout(), [$layout]);
    }
}
