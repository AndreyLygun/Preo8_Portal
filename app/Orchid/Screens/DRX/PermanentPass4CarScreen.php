<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\Layouts\PermanentPass4CarListener;
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
    public $Title = "Заявка на оформление/блокировку постоянного автопропуска";

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $layout[] = PermanentPass4CarListener::class;
        $layout[] = Layout::rows([TextArea::make('note')->title("Примечание")->thorizontal()->rows(10)->horizontal()]);
        return $layout;
    }
}
