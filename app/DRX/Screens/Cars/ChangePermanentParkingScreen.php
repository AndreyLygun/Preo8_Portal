<?php

namespace App\DRX\Screens\Cars;

use App\DRX\DRXClient;
use App\DRX\ExtendedMatrix;
use App\DRX\Helpers\Databooks;
use Carbon\Carbon;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use App\DRX\Screens\SecuritySRQScreen;


class ChangePermanentParkingScreen extends SecuritySRQScreen
{

    public static $EntityType = "IServiceRequestsChangePermanentParkings";
    public static $Title = "Внесение изменений на парковочное место";
    protected static $ExpandFields = ['Cars'];
    protected static $CollectionFields = ['Cars'];


    public function NewEntity()
    {
        $entity = parent::NewEntity();
        $entity['ValidFrom'] = Carbon::today();
        $entity['ValidTill'] = Carbon::today()->addYears(100);
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
        $actions = [
            'noAction' => 'Не менять',
            'remove' => 'Исключить',
            'add' => 'Добавить и изготовить ламинат',
            'makeLaminate' => 'Перевыпустить ламинат'
        ];
        $layout = parent::layout();
        $layout[] = Layout::rows([
            Select::make('entity.ParkingPlace.Id')
                ->title('Парковочное место')->horizontal()
                ->disabled($this->readOnly)->required()
                ->options(Databooks::GetParkingPlaces()),
            Select::make('entity.NeedNfc')
                ->title('Изготовить электронный пропуск')->horizontal()
                ->disabled($this->readOnly)->required()
                ->options(Databooks::GetYesNo()),
            ExtendedMatrix::make('entity.Cars')->readonly($this->readOnly)->removableRows(false)
                ->title('Добавьте или удалите автомобили, на которые оформлена постоянная парковка')->horizontal()
                ->columns(['Модель'=>'Model', 'Номер'=>'Number', 'Изготовить ламинат' => 'NeedPrintedPass'])
                ->fields([
                    'NeedPrintedPass' => Select::make('NeedPrintedPass')->options(Databooks::GetYesNo())->disabled($this->readOnly)])
//                ->disabled()
                ->required(),
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
