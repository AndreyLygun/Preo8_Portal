<?php

namespace App\DRX\Screens;

use App\DRX\Helpers\Databooks;
use App\DRX\Layouts\PermanentPass4CarListener;
use Carbon\Carbon;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;


class PermanentPass4CarScreen extends SecuritySRQScreen
{


    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermanentPass4Cars";
    public $Title = "Заявка на оформление постоянного автопропуска";

    public function ExpandFields()
    {
        $ExpandFields = ['ParkingPlace'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function NewEntity()
    {
        $entity = parent::NewEntity();
        $entity['Action'] = "NewPass";
        $entity['ValidFrom'] = Carbon::today()->addDay();
        $entity['ValidTill'] = Carbon::today()->addDay(365);
        return $entity;
    }

    public function commandBar(): iterable
    {
        $commandBar = parent::commandBar();
        if (($this->entity["RequestState"] ?? '') === 'Approved') {
            $commandBar[] = Button::make("Заблокировать")->method("BlockPass");
        }
        return $commandBar;
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = Layout::rows([
            Input::make('entity.CarModel')->title('Модель автомобиля')
                ->horizontal()->required()
                ->disabled($this->readOnly),
            Input::make('entity.CarNumber')
                ->title('Номер автомобиля')->horizontal()
                ->required()->disabled($this->readOnly),
            Select::make('entity.ParkingPlace.Id')
                ->title('Парковочное место')->horizontal()
                ->empty()
                ->disabled($this->readOnly)->required()
                ->help('Если в списке отображаются не все ваши парковочные места, обратитесь в администрацию БЦ')
                ->options(Databooks::GetParkingPlaces()),
            Select::make("entity.NeedPrintedPass")
                ->title('Требуется ламинированный пропуск')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
            Select::make("entity.NeedNFCPass")
                ->title('Требуется электронный пропуск')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($this->readOnly),
            TextArea::make('visitors')
            ->title('Водители')->horizontal()
            ->rows(3)
        ]);
        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);
        return $layout;
    }

    public function BlockPass() {
        $this->entity['RequestState'] = 'Done';
        $this->entity['VaildTill'] = Carbon::today()->addDays(-1);
        parent::SubmitToApproval('Заявка на блокировку пропуска отправлена');
    }
}
