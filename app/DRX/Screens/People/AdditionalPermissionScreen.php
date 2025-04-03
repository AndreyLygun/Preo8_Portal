<?php

namespace App\DRX\Screens\People;


use App\DRX\Helpers\Databooks;
use Carbon\Carbon;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use App\DRX\Screens\SecuritySRQScreen;

class AdditionalPermissionScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermission4Employees";
    public $Title = "Дополнительный доступ для сотрудника";

    protected $TestField = ['Visitors'];

    public function ExpandFields()
    {
        return array_merge(parent::ExpandFields(), ['Access($expand=Site)']);
    }

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ['Access']);
    }

    public function query(int $id = null): iterable
    {
        $query = parent::query($id);
        $query["entity"]["ValidTill"] = null;
        if (isset($query["entity"]["Access"])) {
            $query["entity"]["Access"] = array_map(fn($value) => (int)(isset($value['Site']) ? $value['Site']['Id'] : 0),
                $query["entity"]["Access"]);
        };
        return $query;
    }

    public function beforeSave()
    {
        parent::beforeSave();
        if (isset($this->entity["Access"])) {
            $this->entity["Access"] = array_map(fn($value) => ["Site" => (object)["Id" => (int)$value]], $this->entity["Access"]);
        }
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
            Input::make("entity.Employee")
                ->title("ФИО сотрудника")->horizontal()
                ->readonly($this->readOnly)->required(),
            Input::make("entity.PassNumber")
                ->title("Номер пропуска сотрудника")->horizontal()
                ->readonly($this->readOnly)->required(),
            DateTimer::make('entity.ValidTill')
                ->title("Действует до")->horizontal()
                ->min(Carbon::today())->allowEmpty()
                ->format('d-m-Y')->serverFormat('d-m-Y')->placeholder('Бессрочный пропуск')
                ->help('Не заполняйте, если нужен бессрочный пропуск'),
            Select::make('entity.Access')
                ->title('Доступ в')->horizontal()
                ->options(Databooks::GetSites('Pass'))->multiple()
                ->readonly($this->readOnly)->required(false),
        ])->title('Описание дополнительного доступа');
        return $layout;
    }
}
