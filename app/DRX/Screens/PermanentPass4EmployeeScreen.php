<?php

namespace App\DRX\Screens;


use App\DRX\ExtendedMatrix;
use App\DRX\Helpers\ImportExcel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Attach;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use function App\DRX\Helpers\Import;


class PermanentPass4EmployeeScreen extends SecuritySRQScreen
{
    // Тип документа в сервисе интеграции, например IOfficialDocuments
    protected $EntityType = "IServiceRequestsPermanentPass4Employees";
    public $Title = "Заявка на постоянный пропуск";

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ['Employees']);
    }

    // Список полей типа "Бинарные данные", "Бинарные данные в хранилище" или "Картинка"
    public function BinaryFields(): array
    {
        return array_merge(['EmployeePhoto'], parent::BinaryFields());
    }

    public function ExpandFields()
    {
        $ExpandFields = ['Employees'];
        return array_merge(parent::ExpandFields(), $ExpandFields);
    }

    public function beforeSave()
    {
        parent::beforeSave(); // TODO: Change the autogenerated stub
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
            ->method('FillFromExcell')
            ->icon('bs.full-screen');
        $clearButton = Button::make('Очистить')
            ->icon('full-screen')
            ->method('Clear');
        $layout[] = Layout::rows([
            ExtendedMatrix::make('entity.Employees')
                ->columns(['ФИО' => 'Name', 'Должность' => 'Position'])
                ->readOnly($readonly)
                ->addButton($clearButton)
                ->addButton($modalToggeButton),
            Input::make('EmployeePhoto')->type('file')
                ->title('Файл с фотографиями сотрудников: ' . ($this->entity['EmployeePhotoFileName']??'-'))
                ->help('Прикрепите файл в формате JPEG (для одной фотографии) или ZIP-архив с фотографиями.')
                ->accept('.zip, .jpg, .jpeg')
                ->canSee(!$readonly),
        ])->title('Сведения о сотрудниках');
        $layout[] = Layout::modal('Excel', [
            Layout::rows([
                Input::make('EmployeesList')
                    ->type('file')
                    ->title('Выберите файл Excel')
                    ->accept('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel')
                    ->required(),
                Link::make('Пример файла для заполнения')->href('/assets/employees.xlsx')
            ]),
        ]);

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
        if (!$request->hasFile('EmployeesList')) return;
        try {
            $res = ImportExcel::Basic($request->file('EmployeesList'), ['Name', 'Position']);
            $this->entity['Employees'] = $res;
            Toast::info('Данные из файла импортированы. Не забудьте сохранить заявку.');
        } catch (LaravelExcelException $ex) {
            Alert::error(stripcslashes($ex->getMessage()));
        }
    }
}
