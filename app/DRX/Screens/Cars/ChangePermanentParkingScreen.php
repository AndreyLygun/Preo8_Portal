<?php

namespace App\DRX\Screens\Cars;

use App\DRX\DRXClient;
use App\DRX\Helpers\Databooks;
use App\DRX\Layouts\PermanentPass4CarListener;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use App\DRX\Screens\SecuritySRQScreen;


class ChangePermanentParkingScreen extends SecuritySRQScreen
{


    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsChangePermanentParkings";
    public $Title = "Внесение изменений на парковочное место";

    public function ExpandFields()
    {
        $ExpandFields = ['Cars'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function CollectionFields()
    {
        return array_merge(['Cars'], parent::CollectionFields()) ; // TODO: Change the autogenerated stub
    }

    public function NewEntity()
    {
        $entity = parent::NewEntity();
        $entity['ValidFrom'] = Carbon::today();
        $entity['ValidTill'] = Carbon::today()->addYear(100);
        if ($parkingPlaceId = request()->input('parkingplace')) {
            $parkingInfo = $this->GetParkingInfo($parkingPlaceId);
            $entity['Cars'] = $parkingInfo['Cars'];
            array_walk($entity['Cars'], fn(&$item, $key) => $item['NeedPrintedPass']='No');
            $entity['NeedNfc'] = $parkingInfo['NfcNumber']?'No':'Yes';
            $entity['Visitors'] = $parkingInfo['Drivers'];
            $entity['ParkingPlace']['Id'] = $parkingPlaceId;
        }
//        dd($entity);
        return $entity;
    }

    private function GetParkingInfo(int $parkingPlaceId) {
        $odata = new DRXClient();
        $parkingInfo = $odata->from('IServiceRequestsParkingPlaces')
            ->expand(['Cars', 'Drivers'])
            ->where(['Id'=>$parkingPlaceId, 'Renter/Login/LoginName'=>Databooks::GetRenterLogin()])
            ->get(0)->first();
        if ($parkingInfo == null) return;
        $parkingInfo['Drivers'] = join("\r", array_map(fn($driver) => $driver["Name"], $parkingInfo['Drivers']));
        return $parkingInfo;
    }

    // Описывает макет экрана
    public function layout(): iterable
    {

        $layout = parent::layout();
        $layout[] = Layout::rows([
            Select::make('entity.ParkingPlace.Id')
                ->title('Парковочное место')->horizontal()
                ->disabled(true)->required()
                ->options(Databooks::GetParkingPlaces()),
            Select::make('entity.NeedNfc')
                ->title('Изготовить электронный пропуск')->horizontal()
                ->disabled($this->readOnly)->required()
                ->options(Databooks::GetYesNo()),
            Matrix::make('entity.Cars')
                ->title('Добавьте или удалите автомобили, на которые оформлена постоянная парковка')->horizontal()
                ->columns(['Модель'=>'Model', 'Номер'=>'Number', 'Изготовить пропуск' => 'NeedPrintedPass'])
                ->fields([
                    'NeedPrintedPass' => Select::make('NeedPrintedPass')->options(Databooks::GetYesNo())])
                ->disabled($this->readOnly)->required(),
            TextArea::make('entity.Visitors')
                ->title('Водители')->horizontal()
                ->rows(3)
        ]);
        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);
        return $layout;
    }

    public function BlockPass()
    {
        $this->entity['RequestState'] = 'Done';
        $this->entity['VaildTill'] = Carbon::today()->addDays(-1);
        parent::SubmitToApproval('Заявка на блокировку пропуска отправлена');
    }
}
