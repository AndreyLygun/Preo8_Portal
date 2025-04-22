<?php

namespace App\DRX\Screens\Cars;


use App\DRX\DRXClient;
use App\DRX\Helpers\Databooks;
use App\DRX\Layouts\PermanentPass4EmployeeListener;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use App\DRX\Screens\SecuritySRQScreen;


class ChangePermanentPass4CarScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsStopPermanentPass4Cars";
    public $Title = "Изменение постоянного автопропуска";

    public function query(int $id = null): iterable
    {
        $query = parent::query($id);
        $query['entity']['PermanentPass4Car']['Id'] = request()->input('pass');
        return $query;
    }

    public function ExpandFields()
    {
        $ExpandFields = ['PermanentPass4Car'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $readOnly = $this->readOnly;
        $layout = parent::layout();
        $odata = new DRXClient();
        $permanentPasses = $odata->from('IServiceRequestsPermanentPass4Cars')
            ->expand('ParkingPlace')
//            ->where('ValidTill', '>', date('y-m-d'))
            ->order('ValidTill', 'desc')
            ->get()->where('ValidTill', '>', Carbon::today()->addDays(-1))
            ->mapWithKeys(fn($v) => [$v['Id'] => $v['Subject']]);

        //array_pop($layout);
        $Actions = [
            'Add' => 'Добавить',
            'Remove' => 'Убрать',
            'NoChange' => 'Без изменений',
        ];

        $layout = parent::layout();
        $layout[] = Layout::rows([
            Select::make('entity.PermanentPass4Car.Id')
                ->title('Изменяемый пропуск')->horizontal()->empty('Выберите пропуск, который нужно заблокировать')
                ->options($permanentPasses),
            Select::make("entity.NeedNFCPass")
                ->title('Изготовить электронный пропуск')->horizontal()
                ->help('Вместо утерянного или неисправного пропуска')
                ->Value('No')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
           ]);
        $layout[] = PermanentPass4EmployeeListener::class;
        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);
        return $layout;    }
}
