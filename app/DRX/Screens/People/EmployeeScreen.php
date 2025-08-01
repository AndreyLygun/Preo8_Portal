<?php

namespace App\DRX\Screens\People;


use App\DRX\ExtendedMatrix;
use App\DRX\Helpers\ImportExcel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use App\DRX\Screens\SecuritySRQScreen;
use Orchid\Screen\Fields\Label;


class EmployeeScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    public static $EntityType = "IServiceRequestsPermanentPass4Employees";
    public static $Title = "Постоянный пропуск для сотрудника";
    protected static $ExpandFields = ['Employees'];
    protected static $CollectionFields = ['Employees'];

    // Список полей типа "Бинарные данные", "Бинарные данные в хранилище" или "Картинка"
    public function BinaryFields(): array
    {
        return array_merge(['EmployeePhoto'], parent::BinaryFields());
    }

    public function beforeSave()
    {
        parent::beforeSave();
        if (request()->hasFile('EmployeePhoto')) {
            $file = \request()->file('EmployeePhoto');
            $this->entity['EmployeePhotoFileName'] = $file->getClientOriginalName();
        }
    }

    // Описывает макет экрана
    public function layout(): iterable
    {
        $layout = parent::layout();
        $readonly = $this->entity['RequestState'] != 'Draft';
        $modalToggeButton = ModalToggle::make('Заполнить из Excel')
            ->modal('Excel')
            ->method('FillFromExcell');
        $clearButton = Button::make('Очистить')
            ->icon('full-screen')
            ->method('Clear');
        $layout[] = Layout::rows([
            ExtendedMatrix::make('entity.Employees')
                ->columns(['ФИО' => 'Name', 'Должность' => 'Position'])
                ->readOnly($readonly)
                ->addButton($clearButton)
                ->addButton($modalToggeButton),
//            Label::make('')->value('Файл с фотографиями сотрудников отправьте на почту reception.a@preo8.ru с указанием номера заявки'),
            Input::make('EmployeePhoto')->type('file')
                ->title('Файл с фотографиями сотрудников: ' . ($this->entity['EmployeePhotoFileName']??'-'))
                ->help('Прикрепите файл в формате JPEG (для одной фотографии) или ZIP-архив с фотографиями.')
                ->accept('.zip, .jpg, .jpeg')
                ->canSee(!$readonly),
        ])->title('Сведения о сотрудниках');
        $layout[] = ImportExcel::MakeModalExcel('Выберите файл Excel', '/assets/employees.xlsx');

        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($this->readOnly)]);

        return $layout;
    }

    public function Clear(Request $request)
    {
        $this->entity = array_merge($this->entity, $request->input('entity') ?? []);
        $this->entity['Employees'] = [];
    }

    public function FillFromExcell(Request $request)
    {
        $this->entity = array_merge($this->entity, request()->input('entity') ?? []);
        if (!$request->hasFile('ExcelFile')) return;
        try {
            $res = ImportExcel::Basic($request->file('ExcelFile'), ['Name', 'Position']);
            $this->entity['Employees'] = $res;
            Toast::info('Данные из файла импортированы. Не забудьте сохранить заявку.');
        } catch (LaravelExcelException $ex) {
            Alert::error(stripcslashes($ex->getMessage()));
        }
    }
}
