<?php

namespace App\DRX\Screens;


use GuzzleHttp\Psr7\Request;
use Orchid\Attachment\File;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Attach;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Layouts\Modal;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Toast;


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
            Matrix::make('entity.Employee')->columns(['ФИО' => 'Name', 'Должность' => 'Positions']),
            ModalToggle::make('Заполнить из файла Excel')
                ->modal('Excel')
                ->method('FillFromExcell')
                ->icon('full-screen')
        ]);
        $layout[] = Layout::modal('Excel', [Layout::rows([
            Upload::make('xlsx')->title("Файл Excel")->horizontal()
            ])
        ]);
        return $layout;
    }

    public function FillFromExcell()
    {
        dd(__FILE__);
        $file = new File(request()->file('xlsx'));
        $attachment = $file->load();
//        $attachment = '123';f

        Toast::info('Успех!');
    }
}
