<?php

namespace App\DRX\Screens;

use App\DRX\Layouts\PermanentPass4CarListener;
use Carbon\Carbon;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;


class PermanentPass4CarScreen extends SecuritySRQScreen
{



    public function ExpandFields()
    {
        $ExpandFields = ['ParkingFloor'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermanentPass4Cars";
    public $Title = "Заявка на оформление/продление постоянного автопропуска";

    public function NewEntity()
    {
        $entity = parent::NewEntity();
        $entity['Action'] = "NewPass";
        $entity['ValidFrom'] = Carbon::today()->addDay();
        $entity['ValidTill'] = Carbon::today()->addDay(365);
        $entity['NeedPrintedPass'] = "No";
        return $entity;
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = PermanentPass4CarListener::class;
        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);
        return $layout;
    }
}
